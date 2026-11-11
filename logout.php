<?php
/**
 * Online Farm Market - Logout Handler
 *
 * Destroys user session and redirects back to home page.
 */

require_once __DIR__ . '/db.php';

// Unset user session data and clear session array
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Start a fresh session to deliver the logout notice
session_start();
flash('You have been successfully logged out.');
redirect('login.php');
