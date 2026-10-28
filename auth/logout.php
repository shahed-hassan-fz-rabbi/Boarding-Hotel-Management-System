<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Logout - Destroy Session & Redirect
 * (Previously this file was a misnamed,
 *  broken duplicate of the login page.)
 * ==========================================
 */

session_start();

/* Unset all session variables */
$_SESSION = [];

/* Delete the session cookie from the browser */
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

/* Destroy the session */
session_destroy();

/* Back to login page */
header('Location: login.php');
exit();
