<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * CSRF Protection Helpers
 * Usage: require_once __DIR__ . '/csrf.php';
 * ==========================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ============================
   Get (or create) CSRF Token
============================ */

function csrf_token() {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/* ============================
   Hidden Field For Forms
============================ */

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/* ============================
   Verify Submitted Token
============================ */

function csrf_verify($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/* ============================
   Reject Request (403 Page)
   Consistent with auth_check.php
============================ */

function csrf_reject() {
    http_response_code(403);
    echo "
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Request Blocked</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gradient-to-br from-red-500 to-pink-500 flex items-center justify-center min-h-screen'>
        <div class='bg-white rounded-2xl shadow-2xl p-8 text-center max-w-md'>
            <div class='text-6xl mb-4' style='line-height:1;'><svg width='56' height='56' viewBox='0 0 24 24' fill='none' stroke='#C7473A' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'><path d='m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z'/><path d='M12 9v4'/><path d='M12 17h.01'/></svg></div>
            <h1 class='text-4xl font-bold text-slate-800 mb-2'>403</h1>
            <h2 class='text-2xl font-bold text-slate-700 mb-4'>Request Blocked (CSRF)</h2>
            <p class='text-slate-600 mb-6'>
                Your session token was missing or invalid for this action.
                Please go back, refresh the page, and try again.
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
