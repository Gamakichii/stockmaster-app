<?php
// public/logout.php

// Ensure session is started to manage it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load functions potentially needed for logging
// Need to handle potential DB connection dependency carefully
try {
    require_once __DIR__ . '/../includes/db.php'; // This loads functions too
} catch (Exception $e) {
    error_log("Error loading db.php during logout: " . $e->getMessage());
    // Continue with logout even if DB fails, but logging might not work
}


// Log the logout action *before* destroying the session data, if possible
if(isset($_SESSION['user_id']) && function_exists('log_action')) {
    log_action('logout');
}

// Unset all of the session variables.
$_SESSION = array();

// If session cookies are used, expire the cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Set expiry in the past
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to login page with a status message
header("Location: /login.php?status=loggedout");
exit; // Ensure script terminates after redirect
?>