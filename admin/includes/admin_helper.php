<?php
/**
 * Admin Helper Functions
 * Simple helper functions for admin pages without complex session management
 */

/**
 * Check admin remember me cookie
 */
function checkAdminRememberMe() {
    if (isset($_COOKIE['admin_remember']) && !isLoggedIn()) {
        $adminToken = $_COOKIE['admin_remember'];

        try {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT id, first_name, last_name, username, email, role, is_suspended, is_verified
                FROM users
                WHERE remember_selector = ?
                AND is_suspended = 0
                AND role = 'admin'
            ");
            $stmt->execute(['admin_' . $adminToken]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Valid admin token, log user in
                global $sessionManager;
                if ($sessionManager) {
                    $sessionManager->login($user['id'], false);
                    $_SESSION['admin_login'] = true;
                    $_SESSION['admin_login_time'] = time();
                    return true;
                }
            } else {
                // Invalid token, clear cookie
                setcookie('admin_remember', '', time() - 3600, '/admin/', '', true, true);
            }
        } catch (PDOException $e) {
            error_log("Admin remember me check error: " . $e->getMessage());
        }
    }

    return false;
}

/**
 * Require admin access for the current page
 */
function requireAdminAccess() {
    // Include session if not already included
    if (!function_exists('isLoggedIn')) {
        require_once __DIR__ . '/../../includes/session.php';
    }

    // Check admin remember me cookie first
    checkAdminRememberMe();

    // Check if user is logged in
    if (!isLoggedIn()) {
        $_SESSION['admin_login_error'] = "Please log in to access the admin panel.";
        header("Location: adminlogin.php");
        exit();
    }

    // Check if user is admin
    if (!isAdmin()) {
        $user = getCurrentUser();
        $userInfo = $user ? "User ID: " . $user['id'] . ", Email: " . $user['email'] : "Unknown user";
        error_log("Unauthorized admin access attempt by: " . $userInfo . " from IP: " . $_SERVER['REMOTE_ADDR']);

        $_SESSION['admin_login_error'] = "Access denied. Admin privileges required.";
        header("Location: adminlogin.php");
        exit();
    }

    // Log admin access
    $user = getCurrentUser();
    $page = basename($_SERVER['PHP_SELF']);
    error_log("Admin access: User {$user['email']} (ID: {$user['id']}) accessed {$page} from IP: " . $_SERVER['REMOTE_ADDR']);
}

/**
 * Get admin session info
 */
function getAdminSessionInfo() {
    if (!function_exists('getCurrentUser')) {
        return [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'session_duration' => 0
        ];
    }
    
    $user = getCurrentUser();
    if (!$user) {
        return [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'session_duration' => 0
        ];
    }
    
    return [
        'name' => $user['first_name'] . ' ' . $user['last_name'],
        'email' => $user['email'],
        'login_time' => $_SESSION['login_time'] ?? time(),
        'session_duration' => time() - ($_SESSION['login_time'] ?? time())
    ];
}

/**
 * Set admin notification
 */
function setAdminNotification($message, $type = 'info') {
    $_SESSION['admin_notification'] = [
        'message' => $message,
        'type' => $type,
        'timestamp' => time()
    ];
}

/**
 * Get and clear admin notification
 */
function getAdminNotification() {
    $notification = $_SESSION['admin_notification'] ?? null;
    unset($_SESSION['admin_notification']);
    return $notification;
}

/**
 * Admin logout
 */
function adminLogout() {
    if (!function_exists('getCurrentUser')) {
        require_once __DIR__ . '/../../includes/session.php';
    }
    
    // Log admin logout
    $user = getCurrentUser();
    if ($user && $user['role'] === 'admin') {
        error_log("Admin logout: User {$user['email']} (ID: {$user['id']}) logged out from IP: " . $_SERVER['REMOTE_ADDR']);
    }
    
    // Use the session manager to logout
    global $sessionManager;
    if ($sessionManager) {
        $sessionManager->logout();
    }
    
    $_SESSION['admin_login_success'] = "You have been successfully logged out.";
    header("Location: adminlogin.php");
    exit();
}

/**
 * Check if user can access admin feature
 */
function canAccessAdminFeature($feature) {
    if (!function_exists('isLoggedIn')) {
        return false;
    }
    
    if (!isLoggedIn() || !isAdmin()) {
        return false;
    }
    
    // For now, all admins have full access
    $allowedFeatures = [
        'dashboard', 'users', 'products', 'orders', 
        'analytics', 'settings', 'vouchers', 'reviews'
    ];
    
    return in_array($feature, $allowedFeatures);
}

/**
 * Require specific admin capability
 */
function requireAdminCapability($capability) {
    if (!canAccessAdminFeature($capability)) {
        $user = getCurrentUser();
        error_log("Admin capability denied: User {$user['email']} tried to access {$capability}");
        $_SESSION['admin_error'] = "You don't have permission to access this feature.";
        header("Location: dashboard.php");
        exit();
    }
}
?>
