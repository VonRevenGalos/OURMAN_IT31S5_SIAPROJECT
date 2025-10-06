<?php
// Start session first
session_start();
require_once 'db.php';
require_once 'includes/session.php';

// Get user info before logout for debugging
$userBeforeLogout = getCurrentUser();

// Logout user
$sessionManager = new SessionManager($pdo);
$sessionManager->logout();

// Clear all session data manually to ensure complete logout
$_SESSION = array();

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start new session for logout message
session_start();
$_SESSION['logout_success'] = "You have been logged out successfully.";

// Redirect to home page
header("Location: index.php");
exit();
?>
