<?php
/**
 * ==========================================
 * DATABASE CONNECTION FILE
 * ==========================================
 */

$servername = "localhost";
$username = "root";
$password = "";
$database = "boarding_school";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die("Database Connection Failed");
}

// Set charset
$conn->set_charset("utf8mb4");

/* ============================
   Base URL (auto-detected)
   Works from any folder name,
   any subdirectory depth, and
   from within module folders.
============================ */

if (PHP_SAPI === 'cli' && empty($_SERVER['HTTP_HOST'])) {
    define('BASE_URL', '/');
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https://' : 'http://';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

    /*
     * Find the project root by walking up from the script's real
     * directory until we reach the folder that contains db.php.
     * Reliable for: docroot root installs, htdocs subfolders,
     * nested module folders, and one or two levels of depth.
     */
    $scriptDir = str_replace('\\', '/', (string) realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? '.')));
    $rootFsPath = null;
    $cursor = $scriptDir;
    for ($steps = 0; $cursor && $steps < 5; $steps++) {
        if (is_file($cursor . '/db.php')) { $rootFsPath = $cursor; break; }
        $parent = dirname($cursor);
        if ($parent === $cursor) break;
        $cursor = $parent;
    }

    if ($rootFsPath !== null) {
        $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $rootDirRel = ($docRoot !== '' && str_starts_with($rootFsPath, $docRoot))
            ? substr($rootFsPath, strlen($docRoot))   // install under a subfolder of docroot
            : '';                                     // vhost/alias/docroot mapped to the project
        $rootDirRel = rtrim($rootDirRel, '/');
    } else {
        // Fallback: strip the current module dir from the URL
        $urlDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $rootDirRel = ($urlDir === '/' || $urlDir === '.')
            ? ''
            : rtrim(preg_replace('#/[^/]*$#', '', $urlDir), '/');
    }

    define('BASE_URL', $protocol . $host . $rootDirRel . '/');
}

?>
