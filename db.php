<?php
/**
 * ==========================================
 * DATABASE CONNECTION FILE
 * ==========================================
 * Credential resolution order:
 *   1. config.local.php (git-ignored — used on real servers; see README)
 *   2. environment variables (DB_HOST / DB_USER / DB_PASS / DB_NAME)
 *   3. XAMPP defaults below (safe for local development)
 * No real credentials are ever stored in Git.
 */

$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "boarding_school";

if (is_file(__DIR__ . '/config.local.php')) {
    /* A deployment may override any subset: $servername, $username, $password, $database */
    require __DIR__ . '/config.local.php';
} else {
    $envOrDefault = function (string $key, string $current): string {
        $v = getenv($key);
        if ($v === false || $v === '') {
            $v = $_SERVER[$key] ?? $_ENV[$key] ?? null;
        }
        return ($v !== null && $v !== '') ? (string) $v : $current;
    };
    $servername = $envOrDefault('DB_HOST', $servername);
    $username   = $envOrDefault('DB_USER', $username);
    $password   = $envOrDefault('DB_PASS', $password);
    $database   = $envOrDefault('DB_NAME', $database);
}

// Create connection (graceful failure on PHP 8.1+ where mysqli throws on error)
try {
    $conn = new mysqli($servername, $username, $password, $database);
} catch (mysqli_sql_exception $e) {
    error_log('DB connection failed: ' . $e->getMessage());
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
