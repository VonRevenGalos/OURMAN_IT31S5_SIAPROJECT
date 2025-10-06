<?php
/**
 * Admin Logout
 * Secure admin logout with proper session cleanup
 */

// Set secure session cookie parameters BEFORE starting session
if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log admin logout if user is logged in
if (isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_email'])) {
    error_log("Admin logout: User {$_SESSION['admin_email']} (ID: {$_SESSION['admin_user_id']}) logged out from IP: " . $_SERVER['REMOTE_ADDR']);

    // Log to audit system
    require_once 'includes/audit_logger.php';
    auditLogout();
}

// Clear all admin session variables (keep user session intact)
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_user_id']);
unset($_SESSION['admin_role']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_first_name']);
unset($_SESSION['admin_last_name']);
unset($_SESSION['admin_login_time']);
unset($_SESSION['admin_last_activity']);

// Clear any admin-related session data
$adminKeys = array_filter(array_keys($_SESSION), function($key) {
    return strpos($key, 'admin_') === 0;
});

foreach ($adminKeys as $key) {
    unset($_SESSION[$key]);
}

// Clear any admin cookies (if they exist)
if (isset($_COOKIE['admin_remember'])) {
    setcookie('admin_remember', '', time() - 3600, '/admin/', '', true, true);
}

// Set logout success message
$_SESSION['admin_login_success'] = "You have been successfully logged out.";

// Redirect to admin login page
header("Location: adminlogin.php");
exit();
?>
