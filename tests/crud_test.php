<?php
/**
 * E2E CRUD sweep with diagnostics
 */
require_once __DIR__ . '/bootstrap.php';

$base = ($argv[1] ?? null) ?: $TEST_BASE_URL;
$cookie = '';
$pass = 0; $fail = 0;
$cleanupSqls = [];

function http($url, $method = 'GET', $post = null) {
    global $cookie;
    $opts = ['http' => [
        'method' => $method,
        'header' => "Cookie: {$cookie}\r\n" . ($post !== null ? "Content-Type: application/x-www-form-urlencoded\r\n" : ""),
        'follow_location' => 0, 'ignore_errors' => true,
    ]];
    if (is_array($post)) $opts['http']['content'] = http_build_query($post);
    $res = @file_get_contents($url, false, stream_context_create($opts));
    $code = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $h, $m)) $code = (int)$m[1];
        if (preg_match('#^Set-Cookie: (PHPSESSID=[^;]+)#i', $h, $m)) $cookie = $m[1];
    }
    return [$code, $res ?? ''];
}

function check($name, $ok, $diag = '') {
    global $pass, $fail;
    echo ($ok ? "PASS" : "FAIL") . "  {$name}" . (!$ok && $diag ? "   << " . $diag : '') . "\n";
    $ok ? $pass++ : $fail++;
}

function errFrom($body) {
    preg_match_all('/class="[^"]*(bg-rose|alert-error|error)[^"]*"[^>]*>([^<]{5,180})/', $body, $m);
    return trim(implode(' || ', $m[2]));
}

function tokenOf($url) {
    global $base;
    [, $body] = http($GLOBALS['base'] . $url);
    preg_match('#name="csrf_token" value="([^"]+)"#', $body, $m);
    return $m[1] ?? '';
}

/* Pre-cleanup */
$db = $TEST_DB_HANDLE();
$db->query("DELETE FROM users WHERE email = 'e2e-crud@example.com'");
$db->query("DELETE FROM meal_records WHERE notes = 'E2E-CRUD-MARKER'");
$db->query("DELETE FROM complaints WHERE complaint_title = 'E2E CRUD Complaint'");
$db->query("DELETE FROM leave_applications WHERE reason = 'E2E-CRUD-MARKER'");
$db->query("DELETE FROM notices WHERE title = 'E2E CRUD Notice'");

/* Login as admin */
[,$body] = http("$base/auth/login.php");
preg_match('#name="csrf_token" value="([^"]+)"#', $body, $tm);
http("$base/auth/login.php", 'POST', ['email' => 'admin@boarding.com', 'password' => 'admin123', 'csrf_token' => $tm[1]]);
$t = tokenOf('/meals/list.php');

$testStudent = (int)$db->query("SELECT id FROM students WHERE status='Active' LIMIT 1")->fetch_assoc()['id'];

/* ---------- MEALS ---------- */
[$code, $body] = http("$base/meals/add.php", 'POST', [
    'student_id' => (string)$testStudent, 'meal_date' => date('Y-m-d'), 'meal_type' => 'Breakfast',
    'served' => '1', 'quantity' => '1', 'notes' => 'E2E-CRUD-MARKER', 'csrf_token' => $t
]);
$row = $db->query("SELECT id FROM meal_records WHERE notes='E2E-CRUD-MARKER'")->fetch_assoc();
check('meal record created', is_array($row), "http=$code err=" . errFrom($body));
$mealId = (int)($row['id'] ?? 0);

[$code, $body] = http("$base/meals/edit.php", 'POST', [
    'meal_id' => $mealId, 'student_id' => (string)$testStudent, 'meal_date' => date('Y-m-d'),
    'meal_type' => 'Dinner', 'served' => 1, 'quantity' => '2', 'notes' => 'E2E-CRUD-MARKER', 'csrf_token' => $t
]);
$meal = $mealId ? $db->query("SELECT meal_type, quantity FROM meal_records WHERE id=$mealId")->fetch_assoc() : null;
check('meal edited (Dinner, qty 2)', $meal && $meal['meal_type'] === 'Dinner' && (float)$meal['quantity'] === 2.0, "http=$code err=" . errFrom($body));

if ($mealId) {
    http("$base/meals/delete.php", 'POST', ['meal_id' => $mealId, 'csrf_token' => $t]);
    $meal = $db->query("SELECT id FROM meal_records WHERE id=$mealId")->fetch_assoc();
    check('meal deleted', $meal === null);
}

/* ---------- COMPLAINTS ---------- */
[,$body] = http("$base/complaints/list.php");
preg_match('#name="csrf_token" value="([^"]+)"#', $body, $tc);
$t2 = $tc[1] ?? '';

[$code, $body] = http("$base/complaints/add.php", 'POST', [
    'student_id' => (string)$testStudent, 'complaint_title' => 'E2E CRUD Complaint',
    'complaint_description' => 'Investigate plumbing', 'category' => 'Maintenance',
    'priority' => 'High', 'csrf_token' => $t2
]);
$cmpl = $db->query("SELECT id FROM complaints WHERE complaint_title='E2E CRUD Complaint'")->fetch_assoc();
check('complaint created', is_array($cmpl), "http=$code err=" . errFrom($body));
$cid = (int)($cmpl['id'] ?? 0);

/* pick a real, active staff member for assignment (Admin / Hostel Supervisor) */
$staffRow = $db->query("SELECT id FROM users WHERE role IN ('Admin','Hostel Supervisor') AND status = 1 ORDER BY id LIMIT 1")->fetch_assoc();
$staffId = (string)($staffRow['id'] ?? '');

[$code, $body] = http("$base/complaints/update.php", 'POST', [
    'complaint_id' => (string)$cid, 'status' => 'Resolved', 'assigned_to' => $staffId,
    'resolution_notes' => 'Fixed by E2E test', 'csrf_token' => $t2
]);
$c2 = $cid ? $db->query("SELECT status, resolution_notes FROM complaints WHERE id=$cid")->fetch_assoc() : null;
check('complaint updated to Resolved', $c2 && $c2['status'] === 'Resolved' && $c2['resolution_notes'] === 'Fixed by E2E test', "http=$code err=" . errFrom($body));
/* ---------- LEAVES ---------- */
$leaveType = (int)$db->query("SELECT id FROM leave_types LIMIT 1")->fetch_assoc()['id'];
[$code, $body] = http("$base/leaves/apply.php", 'POST', [
    'student_id' => (string)$testStudent, 'leave_type_id' => (string)$leaveType,
    'from_date' => '2026-12-01', 'to_date' => '2026-12-03', 'reason' => 'E2E-CRUD-MARKER', 'csrf_token' => $t2
]);
$lapp = $db->query("SELECT id, number_of_days FROM leave_applications WHERE reason='E2E-CRUD-MARKER'")->fetch_assoc();
check('leave application created', is_array($lapp), "http=$code err=" . errFrom($body));
check('leave days computed = 3', $lapp && (int)$lapp['number_of_days'] === 3);
$lid = (int)($lapp['id'] ?? 0);

[$code, $body] = http("$base/leaves/approve.php", 'POST', [
    'leave_id' => (string)$lid, 'action' => 'approve', 'approval_notes' => 'ok', 'csrf_token' => $t2
]);
$adminRow = $db->query("SELECT id FROM users WHERE email='admin@boarding.com'")->fetch_assoc();
$adminId = (int)($adminRow['id'] ?? 0);
$l2 = $lid ? $db->query("SELECT status, approved_by FROM leave_applications WHERE id=$lid")->fetch_assoc() : null;
check('leave approved', $l2 && $l2['status'] === 'Approved' && $adminId > 0 && (int)$l2['approved_by'] === $adminId, "http=$code err=" . errFrom($body));

/* ---------- NOTICES ---------- */
$t3 = tokenOf('/notices/add.php');
[$code, $body] = http("$base/notices/add.php", 'POST', [
    'title' => 'E2E CRUD Notice', 'description' => 'test notice body',
    'publish_date' => date('Y-m-d'), 'status' => 'Active', 'csrf_token' => $t3
]);
$n1 = $db->query("SELECT id FROM notices WHERE title='E2E CRUD Notice'")->fetch_assoc();
check('notice created', is_array($n1), "http=$code err=" . errFrom($body));
$nid = (int)($n1['id'] ?? 0);

$t3b = tokenOf('/notices/edit.php');
if ($t3b === '') { $t3b = $t3; }
[$code, $body] = http("$base/notices/edit.php", 'POST', [
    'action' => 'secure_update', 'id' => (string)$nid, 'title' => 'E2E CRUD Notice',
    'description' => 'updated body', 'priority' => 'High', 'csrf_token' => $t3b
]);
$n2 = $nid ? $db->query("SELECT description, priority FROM notices WHERE id=$nid")->fetch_assoc() : null;
check('notice updated', $n2 && $n2['description'] === 'updated body', "http=$code err=" . errFrom($body));
$cleanupSqls[] = "DELETE FROM notices WHERE id = $nid";

/* ---------- SETTINGS ---------- */
$t4 = tokenOf('/settings/index.php');
http("$base/settings/index.php", 'POST', [
    'school_name' => 'E2E Test School', 'school_email' => 'e2e@test.school', 'school_phone' => '0123',
    'school_address' => 'Test Address', 'room_fee' => '5000', 'meal_fee' => '3000',
    'session_timeout' => '3600', 'backup_enabled' => '0', 'email_notifications' => '1',
    'academic_year' => '2026-2027', 'csrf_token' => $t4
]);
$st = $db->query("SELECT school_name FROM system_settings LIMIT 1")->fetch_assoc();
check('settings saved', $st && $st['school_name'] === 'E2E Test School');

/* ---------- USERS ---------- */
$t5 = tokenOf('/users/add.php');
[$code, $body] = http("$base/users/add.php", 'POST', [
    'action' => 'provision_user', 'full_name' => 'E2E CRUD User', 'email' => 'e2e-crud@example.com',
    'password' => 'crudpass123', 'role' => 'Teacher', 'status' => '1', 'csrf_token' => $t5
]);
$u1 = $db->query("SELECT id, role, status FROM users WHERE email='e2e-crud@example.com'")->fetch_assoc();
check('user provisioned as Teacher status=1', $u1 && $u1['role'] === 'Teacher' && (int)$u1['status'] === 1, "http=$code err=" . errFrom($body));
$uid = (int)($u1['id'] ?? 0);

[$code, $body] = http("$base/users/edit.php", 'POST', [
    'action' => 'sync_user', 'user_id' => (string)$uid, 'full_name' => 'E2E CRUD User',
    'email' => 'e2e-crud@example.com', 'role' => 'Accountant', 'status' => '1',
    'password' => '', 'csrf_token' => $t5
]);
$u2 = $uid ? $db->query("SELECT role FROM users WHERE id=$uid")->fetch_assoc() : null;
check('user role changed to Accountant', $u2 && $u2['role'] === 'Accountant', "http=$code err=" . errFrom($body));
[$code, $body] = http("$base/users/edit.php", 'POST', [
    'action' => 'sync_user', 'user_id' => (string)$uid, 'full_name' => 'E2E CRUD User',
    'email' => 'e2e-crud@example.com', 'role' => 'Accountant', 'status' => '0',
    'password' => '', 'csrf_token' => $t5
]);
$u3 = $uid ? $db->query("SELECT status FROM users WHERE id=$uid")->fetch_assoc() : null;
check('user suspended (status 0)', $u3 && (int)$u3['status'] === 0, "http=$code err=" . errFrom($body));

$cleanupSqls[] = "DELETE FROM users WHERE id = $uid";
$cleanupSqls[] = "DELETE FROM complaints WHERE id = $cid";
$cleanupSqls[] = "DELETE FROM leave_applications WHERE id = $lid";

foreach ($cleanupSqls as $sql) { $db->query($sql); }
$db->close();

echo "\n==========\nRESULT: {$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);

