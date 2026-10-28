<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Authentication Middleware
 * Usage: require_once 'includes/auth_check.php';
 * ==========================================
 */

session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/csrf.php';

/* ============================
   User Must Be Logged In
============================ */

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . (strpos($_SERVER['REQUEST_URI'], 'auth/') !== false 
        ? '../auth/login.php' 
        : 'auth/login.php'));
    exit();
}

/* ============================
   Session Timeout: 30 Minutes
============================ */

$timeout = 1800; // 30 minutes

if (isset($_SESSION['LAST_ACTIVITY'])) {
    if ((time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
        /* Session expired */
        session_unset();
        session_destroy();
        
        header('Location: ' . (strpos($_SERVER['REQUEST_URI'], 'auth/') !== false 
            ? '../auth/login.php?timeout=1' 
            : 'auth/login.php?timeout=1'));
        exit();
    }
}

$_SESSION['LAST_ACTIVITY'] = time();

/* ============================
   CSRF Protection: Verify Every
   State-Changing POST Request
============================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
    csrf_reject();
}

/* ============================
   Get User Information from Session
============================ */

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Unknown';
$user_email = $_SESSION['user_email'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';

/* ============================
   Refresh User Data from Database
============================ */

$refreshSql = "SELECT id, full_name, email, role, status, last_login 
               FROM users 
               WHERE id = ? 
               AND status = 1 
               LIMIT 1";

$refreshStmt = $conn->prepare($refreshSql);

if ($refreshStmt) {
    $refreshStmt->bind_param('i', $user_id);
    $refreshStmt->execute();
    $refreshResult = $refreshStmt->get_result();

    if ($refreshResult->num_rows !== 1) {
        /* User not found or inactive - force logout */
        session_destroy();
        header('Location: ' . (strpos($_SERVER['REQUEST_URI'], 'auth/') !== false 
            ? '../auth/login.php' 
            : 'auth/login.php'));
        exit();
    }

    $currentUser = $refreshResult->fetch_assoc();

    /* Update session with latest data */
    $_SESSION['user_name'] = $currentUser['full_name'];
    $_SESSION['user_email'] = $currentUser['email'];
    $_SESSION['user_role'] = $currentUser['role'];

    $refreshStmt->close();
} else {
    /* Database error */
    die('Database error: ' . $conn->error);
}

/* ============================
   Helper Function: Check Role
============================ */

function hasRole($role) {
    return isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === $role;
}

/* ============================
   Helper Function: Require Roles
   Usage: requireRoles(['Admin', 'Warden']);
============================ */

function requireRoles($roles = []) {
    if (!in_array($_SESSION['user_role'], $roles)) {
        http_response_code(403);
        echo "
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Access Denied</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gradient-to-br from-red-500 to-pink-500 flex items-center justify-center min-h-screen'>
            <div class='bg-white rounded-2xl shadow-2xl p-8 text-center max-w-md'>
                <div class='text-6xl mb-4'>🚫</div>
                <h1 class='text-4xl font-bold text-slate-800 mb-2'>403</h1>
                <h2 class='text-2xl font-bold text-slate-700 mb-4'>Access Denied</h2>
                <p class='text-slate-600 mb-6'>
                    You don't have permission to access this page.
                </p>
                <p class='text-sm text-slate-500 mb-6'>
                    Required Role: <strong>" . implode(', ', $roles) . "</strong><br>
                    Your Role: <strong>" . htmlspecialchars($_SESSION['user_role']) . "</strong>
                </p>
                <a href='../index.php' class='inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg font-600 hover:bg-indigo-700 transition'>
                    Go Back to Dashboard
                </a>
            </div>
        </body>
        </html>
        ";
        exit();
    }
}

/* ============================
   Helper Function: Get User Info
============================ */

function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? 'Unknown',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? ''
    ];
}

?>