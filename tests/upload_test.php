<?php
/* Students-photo upload security test harness (outside webroot)
 * Usage: php test_upload.php <base>
 * Verifies: valid PNG accepted + stored; .php upload rejected; no public link
 */
require_once __DIR__ . '/bootstrap.php';

$TEST_UPLOADS = $TEST_DOCROOT . '/uploads/students';

$base = ($argv[1] ?? null) ?: $TEST_BASE_URL;
$cookie = '';
$fail = function (string $m) { fwrite(STDERR, "FAIL  $m\n"); exit(1); };
$ok = function (bool $c, string $m) { echo ($c ? 'PASS' : 'FAIL') . "  $m\n"; if (!$c) exit(1); };

function http($url, $post = null, $multipart = null) {
    global $cookie;
    $headers = "Cookie: {$cookie}\r\n";
    if ($multipart !== null) $headers .= "Content-Type: multipart/form-data; boundary={$multipart[0]}\r\n";
    elseif (is_array($post)) $headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $opts = ['http' => ['method' => ($post !== null || $multipart !== null) ? 'POST' : 'GET', 'header' => $headers, 'follow_location' => 0, 'ignore_errors' => true]];
    if ($multipart !== null) $opts['http']['content'] = $multipart[1];
    elseif (is_array($post)) $opts['http']['content'] = http_build_query($post);
    $res = @file_get_contents($url, false, stream_context_create($opts));
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^Set-Cookie: (PHPSESSID=[^;]+)#i', $h, $m)) $cookie = $m[1];
    }
    return $res ?? '';
}

/* cleanup any previous runs */
$db = $TEST_DB_HANDLE();
$db->query("DELETE s, g FROM students s LEFT JOIN guardians g ON s.guardian_id = g.id WHERE s.email='upload-test@example.com'");
/* remove leftovers in uploads dir */
foreach (glob("$TEST_UPLOADS . '/'*") ?: [] as $f) { /* leave: checked below */ }

/* login */
[, $b] = [$base, http("$base/auth/login.php")];
preg_match('#name="csrf_token" value="([^"]+)"#', $b, $m) || $fail('no csrf on login');
http("$base/auth/login.php", ['email' => 'admin@boarding.com', 'password' => 'admin123', 'csrf_token' => $m[1]]);
$b = http($base . '/students/add.php');
preg_match('#name="csrf_token" value="([^"]+)"#', $b, $m) || $fail('no csrf on students/add');
$token = $m[1];

$common = ['csrf_token' => $token, 'first_name' => 'Upload', 'last_name' => 'Tester',
    'email' => 'upload-test@example.com', 'phone' => '555-0100', 'gender' => 'Male',
    'dob' => '2010-05-05', 'blood_group' => 'O+', 'address' => 'Test Address',
    'admission_date' => '2026-10-28'];

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

function multipart(string $boundary, array $fields, array $files): array {
    $body = '';
    foreach ($fields as $k => $v) {
        $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"$k\"\r\n\r\n$v\r\n";
    }
    foreach ($files as $name => [$fname, $mime, $data]) {
        $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"$name\"; filename=\"$fname\"\r\nContent-Type: $mime\r\n\r\n$data\r\n";
    }
    $body .= "--$boundary--\r\n";
    return [$boundary, $body];
}

/* --- CASE 1: valid PNG accepted --- */
[$boundary, $body] = multipart('sepTEST123', $common, ['photo' => ['portrait.png', 'image/png', $png]]);
$b = http($base . '/students/add.php', null, [$boundary, $body]);
$row = $db->query("SELECT id, photo FROM students WHERE email='upload-test@example.com'")->fetch_assoc();
$ok(is_array($row) && !empty($row['photo']), 'valid PNG upload: student created with photo path');
$photoPath = 'C:/xampp/htdocs/Boarding-Hotel-Management-System/' . ($row['photo'] ?? '');
$ok(preg_match('#^uploads/students/student_[0-9a-f.]+\.(png|jpg|jpeg|webp|gif)$#i', (string)$row['photo']) === 1, 'stored filename is randomised with whitelist extension: ' . ($row['photo'] ?? '(none)'));
$ok(is_file($photoPath) && hash('sha256', file_get_contents($photoPath)) === hash('sha256', $png), 'stored file bytes match uploaded PNG');

/* --- CASE 2: .php file rejected --- */
$phpPayload = "<?php echo 'pwned'; ?>";
[$boundary2, $body2] = multipart('sepTEST456', array_merge($common, ['email' => 'upload-test2@example.com']),
    ['photo' => ['shell.php', 'image/png', $phpPayload]]);
$b2 = http($base . '/students/add.php', null, [$boundary2, $body2]);
$row2 = $db->query("SELECT id FROM students WHERE email='upload-test2@example.com'")->fetch_assoc();
$ok($row2 === null, 'fake .php upload: no student row created');
$ok(strpos($b2, 'not a valid image') !== false || strpos($b2, 'GIF image') !== false, 'form renders validation error');
$badFiles = array_filter(glob($TEST_UPLOADS . '/*') ?: [], fn($f) => !preg_match('/\.(png|jpe?g|webp|gif)$/i', $f));
$ok(count($badFiles) === 0, 'no .php file written to uploads/students');

/* --- CASE 3: 3MB oversized valid-ish image rejected --- */
$bigBmp = str_repeat('A', 3 * 1024 * 1024);
[$boundary3, $body3] = multipart('sepTEST789', array_merge($common, ['email' => 'upload-test3@example.com']),
    ['photo' => ['bigger.png', 'image/png', $bigBmp]]);
$b3 = http($base . '/students/add.php', null, [$boundary3, $body3]);
$row3 = $db->query("SELECT id FROM students WHERE email='upload-test3@example.com'")->fetch_assoc();
$ok($row3 === null, 'oversized upload: no student row created');

/* --- CLEANUP --- */
$db->query("DELETE s, g FROM students s LEFT JOIN guardians g ON s.guardian_id = g.id WHERE s.email IN ('upload-test@example.com','upload-test2@example.com','upload-test3@example.com')");
if (isset($row['photo']) && str_starts_with((string)$row['photo'], 'uploads/students/student_')) {
    @unlink($photoPath);
}
$db->close();
echo "=== upload tests done ===\n";

function is_executable_check($f) { return !preg_match('/\.(png|jpe?g|webp|gif)$/i', $f); }

