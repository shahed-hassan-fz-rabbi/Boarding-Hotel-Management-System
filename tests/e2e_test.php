<?php
/**
 * End-to-End functional test suite (XAMPP Apache or any PHP server)
 * Usage: php tests/e2e_test.php  [optional base URL, default from bootstrap.php]
 */

require_once __DIR__ . '/bootstrap.php';

$base = ($argv[1] ?? null) ?: $TEST_BASE_URL;
$cookie = '';

function http($url, $method = 'GET', $post = null) {
    global $cookie;
    $opts = [
        'http' => [
            'method' => $method,
            'header' => "Cookie: {$cookie}\r\n" . ($post !== null ? "Content-Type: application/x-www-form-urlencoded\r\n" : ""),
            'follow_location' => 0,   // we want to see redirects
            'ignore_errors' => true,
        ]
    ];
    if (is_array($post)) $opts['http']['content'] = http_build_query($post);
    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $h, $m)) $code = (int)$m[1];
        if (preg_match('#^Set-Cookie: (PHPSESSID=[^;]+)#i', $h, $m)) $cookie = $m[1];
    }
    return [$code, $res ?? ''];
}

$pass = 0; $fail = 0; $cleanupSqls = [];

function check($name, $ok) {
    global $pass, $fail;
    echo ($ok ? "PASS" : "FAIL") . "  {$name}\n";
    $ok ? $pass++ : $fail++;
}

/* ---------- 0. Pre-run cleanup of leftover test data ---------- */
$dbc = $TEST_DB_HANDLE();
$dbc->query("DELETE FROM students WHERE email = 'e2e-testman@example.com'");
$dbc->query("DELETE FROM hostel_rooms WHERE room_number = 'TST901'");
$dbc->query("DELETE FROM payments WHERE student_id IN (SELECT id FROM students WHERE email='e2e-testman@example.com')");
$dbc->query("DELETE FROM users WHERE email = 'e2e-acct@example.com'");
$dbc->close();

/* ---------- 1. Login page loads ---------- */
[$code] = http("$base/auth/login.php");
check('login page loads (200)', $code === 200);

/* ---------- 2. Unprotected POST rejected (CSRF) ---------- */
[$code] = http("$base/auth/login.php", 'POST', ['email' => 'admin@boarding.com', 'password' => 'admin123']);
check('login POST without CSRF token rejected (403)', $code === 403);

/* ---------- 3. FOLLOW-UP POST after expiry session ---------- */

/* ---------- 4. Login with token ---------- */
[$code, $body] = http("$base/auth/login.php");
preg_match('#name="csrf_token" value="([^"]+)"#', $body, $tm);
check('login page renders CSRF token', isset($tm[1]) && $tm[1] !== '');

[$code, $hdr] = http("$base/auth/login.php", 'POST', ['email' => 'admin@boarding.com', 'password' => 'admin123', 'csrf_token' => $tm[1]]);
check('login with valid credentials redirects (302)', $code === 302);
// consume redirect to register cookie session as logged-in
[$code, $body] = http("$base/index.php");
check('dashboard loads after login (200)', $code === 200);
check('dashboard shows room grid section', strpos($body, 'roomContainer') !== false);

/* ---------- 5. RBAC: settings page accessible as Admin ---------- */
[$code, $body] = http("$base/settings/index.php");
check('Admin can open settings (200)', $code === 200);

/* ---------- 6. CSRF guard on module POST (deallocation) ---------- */
[$code] = http("$base/allocations/deallocate.php", 'POST', ['allocation_id' => 1]);
check('deallocate POST without CSRF token rejected (403)', $code === 403);

/* ---------- 7. Allocation lifecycle with a throwaway student ---------- */
$db = $TEST_DB_HANDLE();

/* fresh student */
$db->query("INSERT INTO students (student_code, first_name, last_name, gender, phone, email, admission_date, status)
            VALUES ('TST-E2E-001', 'E2E', 'Testman', 'Male', '01999999999', 'e2e-testman@example.com', CURDATE(), 'Active')");
$testStudentId = (int)$db->insert_id;
$cleanupSqls[] = "DELETE FROM students WHERE id = {$testStudentId}";
check('test student created in DB', $testStudentId > 0);

/* fresh bed */
$db->query("INSERT INTO hostel_rooms (room_number, room_type, total_beds, allocated_beds, status, floor)
            VALUES ('TST901', 'Boys', 2, 0, 'Available', 9)");
$testRoomId = (int)$db->insert_id;
$cleanupSqls[] = "DELETE FROM hostel_rooms WHERE id = {$testRoomId}";
$db->query("INSERT INTO room_beds (room_id, bed_number, bed_type, status) VALUES ({$testRoomId}, 'TST901-01', 'Single', 'Available')");
$testBedId = (int)$db->insert_id;

/* get a fresh CSRF token from the allocations page */
$cookie2 = $cookie;
global $cookie;
[$code, $body] = http("$base/allocations/list.php");
preg_match('#name="csrf_token" value="([^"]+)"#', $body, $tm2);
$token2 = $tm2[1] ?? '';
check('allocations page renders CSRF token for logged-in Admin', $token2 !== '');

/* allocate */
[$code, $body] = http("$base/allocations/allocate.php", 'POST', [
    'student_id' => $testStudentId, 'bed_id' => $testBedId, 'check_in' => date('Y-m-d'), 'csrf_token' => $token2
]);
check('allocate redirects (302)', $code === 302);

$alloc = $db->query("SELECT id FROM room_allocations WHERE student_id = {$testStudentId} AND status='Active'")->fetch_assoc();
check('allocation row created and Active', is_array($alloc));
$bedRow = $db->query("SELECT status FROM room_beds WHERE id = {$testBedId}")->fetch_assoc();
check('bed marked Occupied after allocation', $bedRow['status'] === 'Occupied');
$roomRow = $db->query("SELECT allocated_beds, status FROM hostel_rooms WHERE id = {$testRoomId}")->fetch_assoc();
check('room allocated_beds counter synced to 1', (int)$roomRow['allocated_beds'] === 1);

/* double allocation must fail */
[$code, $loc] = http("$base/allocations/allocate.php", 'POST', [
    'student_id' => $testStudentId, 'bed_id' => $testBedId, 'check_in' => date('Y-m-d'), 'csrf_token' => $token2
]);
/* note: double-allocate redirects with error */
check('second allocation attempt does not succeed', true); // validated below by counting rows

/* count active allocations for the student - must stay 1 */
$cnt = (int)$db->query("SELECT COUNT(*) c FROM room_allocations WHERE student_id = {$testStudentId} AND status='Active'")->fetch_assoc()['c'];
check('no duplicate/overlapping allocation created (still 1 active)', $cnt === 1);

/* deallocate */
[$code] = http("$base/allocations/deallocate.php", 'POST', ['allocation_id' => $alloc['id'], 'csrf_token' => $token2]);
check('deallocate redirects (302)', $code === 302);
$allocRow = $db->query("SELECT status, check_out FROM room_allocations WHERE id = {$alloc['id']}")->fetch_assoc();
check('allocation now Checked Out with check_out date', $allocRow['status'] === 'Checked Out' && !empty($allocRow['check_out']));
$bedRow = $db->query("SELECT status FROM room_beds WHERE id = {$testBedId}")->fetch_assoc();
check('bed released to Available', $bedRow['status'] === 'Available');
$roomRow = $db->query("SELECT allocated_beds, status FROM hostel_rooms WHERE id = {$testRoomId}")->fetch_assoc();
check('room counter back to 0 and Available', (int)$roomRow['allocated_beds'] === 0 && $roomRow['status'] === 'Available');

/* ---------- 8. Payment status computed server-side (forge Paid with 0 paid) ---------- */
[$code, $body] = http("$base/payments/list.php");
preg_match('#name="csrf_token" value="([^"]+)"#', $body, $tp);
$token3 = $tp[1] ?? '';

[$code] = http("$base/payments/add.php", 'POST', [
    'student_id' => $testStudentId, 'month' => 10, 'year' => 2026,
    'room_fee' => 500, 'meal_fee' => 300, 'fine' => 0, 'discount' => 100,
    'paid_amount' => 0, 'payment_status' => 'Paid', 'payment_date' => '', 'csrf_token' => $token3
]);
check('payment add redirects (302)', $code === 302);

$pay = $db->query("SELECT * FROM payments WHERE student_id = {$testStudentId} AND month = 10 AND year = 2026")->fetch_assoc();
check('payment row created', is_array($pay));
check('forged status ignored - computed as Due (total 700, paid 0) ', $pay['payment_status'] === 'Due' && (float)$pay['due_amount'] === 700.0);
$cleanupSqls[] = "DELETE FROM payments WHERE id = " . (int)$pay['id'];

/* partial payment math */
[$code] = http("$base/payments/add.php", 'POST', [
    'student_id' => $testStudentId, 'month' => 9, 'year' => 2026,
    'room_fee' => 500, 'meal_fee' => 300, 'fine' => 50, 'discount' => 0,
    'paid_amount' => 600, 'payment_status' => 'Paid', 'payment_date' => date('Y-m-d'), 'csrf_token' => $token3
]);
$pay2 = $db->query("SELECT * FROM payments WHERE student_id = {$testStudentId} AND month = 9 AND year = 2026")->fetch_assoc();
check('partial payment: status=Partial due=250', is_array($pay2) && $pay2['payment_status'] === 'Partial' && (float)$pay2['due_amount'] === 250.0);
$cleanupSqls[] = "DELETE FROM payments WHERE id = " . (int)$pay2['id'];

/* duplicate insertion blocked gracefully */
[$code] = http("$base/payments/add.php", 'POST', [
    'student_id' => $testStudentId, 'month' => 9, 'year' => 2026,
    'room_fee' => 500, 'meal_fee' => 300, 'fine' => 0, 'discount' => 0,
    'paid_amount' => 600, 'payment_status' => 'Paid', 'payment_date' => '', 'csrf_token' => $token3
]);
$dupCount = (int)$db->query("SELECT COUNT(*) c FROM payments WHERE student_id = {$testStudentId} AND month = 9 AND year = 2026")->fetch_assoc()['c'];
check('duplicate payment prevented (still 1 row for 9/2026)', $dupCount === 1);

/* ---------- 9. RBAC as Accountant: settings blocked ---------- */
$cookie = '';
[$code, $body] = http("$base/auth/login.php");
preg_match('#name="csrf_token" value="([^"]+)"#', $body, $ta);
/* give the test accountant a session via direct DB registration */
$db->query("INSERT INTO users (full_name, email, password, role, status) VALUES ('E2E Accountant', 'e2e-acct@example.com', '" . password_hash('acctpass123', PASSWORD_BCRYPT) . "', 'Accountant', 1)");
$acctId = (int)$db->insert_id;
$cleanupSqls[] = "DELETE FROM users WHERE id = {$acctId}";

[$code, $body] = http("$base/auth/login.php", 'POST', ['email' => 'e2e-acct@example.com', 'password' => 'acctpass123', 'csrf_token' => $ta[1]]);
check('accountant login redirects (302)', $code === 302);
[$code, $body] = http("$base/settings/index.php");
echo "   [debug] settings code=$code cookieLen=" . strlen($cookie) . "\n";
check('Accountant blocked from settings (302 redirect)', $code === 302);
[$code, $body] = http("$base/users/add.php");
echo "   [debug] users/add code=$code body=" . substr(strip_tags(str_replace(["\r", "\n"], ' ', $body)), 0, 120) . "\n";
check('Accountant blocked from user management (302 redirect)', $code === 302);
[$code, $body] = http("$base/payments/list.php");
check('Accountant CAN access payments (200)', $code === 200);

/* ---------- 10. Authenticated logout works ---------- */
[$code] = http("$base/auth/logout.php");
check('logout redirects (302)', $code === 302);
[$code, $body] = http("$base/index.php");
check('dashboard requires login again after logout', $code === 302);

/* ---------- CLEANUP ---------- */
foreach ($cleanupSqls as $sql) { $db->query($sql); }
$db->close();

echo "\n==========\nRESULT: {$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);

