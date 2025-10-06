<?php
/**
 * Admin Authentication Helper
 * Secure admin session management separate from user sessions
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

// Include database connection
require_once __DIR__ . '/../../db.php';

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    // Check both admin session formats for compatibility
    return (isset($_SESSION['admin_logged_in']) &&
            $_SESSION['admin_logged_in'] === true &&
            isset($_SESSION['admin_user_id']) &&
            isset($_SESSION['admin_role']) &&
            $_SESSION['admin_role'] === 'admin') ||
           (isset($_SESSION['user_id']) &&
            isset($_SESSION['user_role']) &&
            $_SESSION['user_role'] === 'admin');
}

/**
 * Get current admin user ID
 */
function getAdminId() {
    if (!isAdminLoggedIn()) {
        return null;
    }

    // Return admin user ID from either session format
    if (isset($_SESSION['admin_user_id'])) {
        return $_SESSION['admin_user_id'];
    } elseif (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
        return $_SESSION['user_id'];
    }

    return null;
}

/**
 * Require admin login - redirect if not logged in
 */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        $_SESSION['admin_login_error'] = "Please log in to access the admin panel.";
        header("Location: adminlogin.php");
        exit();
    }

    // Update last activity
    $_SESSION['admin_last_activity'] = time();

    // Check session timeout (24 hours) - support both session formats
    $login_time = $_SESSION['admin_login_time'] ?? $_SESSION['login_time'] ?? time();
    if ((time() - $login_time) > 86400) {
        adminLogout();
        $_SESSION['admin_login_error'] = "Your session has expired. Please log in again.";
        header("Location: adminlogin.php");
        exit();
    }
}

/**
 * Get current admin user data
 */
function getCurrentAdminUser() {
    global $pdo;

    if (!isAdminLoggedIn()) {
        return null;
    }

    $admin_id = getAdminId();
    if (!$admin_id) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, username, email, role, is_suspended, is_verified
            FROM users
            WHERE id = ? AND role = 'admin' AND is_suspended = 0
        ");
        $stmt->execute([$admin_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // User no longer exists or is not admin, clear session
            adminLogout();
            return null;
        }

        return $user;

    } catch (PDOException $e) {
        error_log("Admin user fetch error: " . $e->getMessage());
        return null;
    }
}

/**
 * Admin logout function
 */
function adminLogout() {
    // Log admin logout if user is logged in
    $admin_id = getAdminId();
    $email = $_SESSION['admin_email'] ?? $_SESSION['user_email'] ?? 'unknown';

    if ($admin_id) {
        error_log("Admin logout: User {$email} (ID: {$admin_id}) logged out from IP: " . $_SERVER['REMOTE_ADDR']);
    }

    // Clear all admin session variables (keep user session intact if it's a regular user)
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user_id']);
    unset($_SESSION['admin_role']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_first_name']);
    unset($_SESSION['admin_last_name']);
    unset($_SESSION['admin_login_time']);
    unset($_SESSION['admin_last_activity']);

    // If using simple session format, clear those too
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_first_name']);
        unset($_SESSION['user_last_name']);
        unset($_SESSION['login_time']);
        unset($_SESSION['admin_login']);
    }

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
}

/**
 * Get admin session info for display
 */
function getAdminSessionInfo() {
    if (!isAdminLoggedIn()) {
        return [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'login_time' => time(),
            'session_duration' => 0
        ];
    }

    // Support both session formats
    $first_name = $_SESSION['admin_first_name'] ?? $_SESSION['user_first_name'] ?? 'Admin';
    $last_name = $_SESSION['admin_last_name'] ?? $_SESSION['user_last_name'] ?? 'User';
    $email = $_SESSION['admin_email'] ?? $_SESSION['user_email'] ?? 'admin@example.com';
    $username = $_SESSION['admin_username'] ?? $_SESSION['user_username'] ?? 'admin';
    $login_time = $_SESSION['admin_login_time'] ?? $_SESSION['login_time'] ?? time();

    return [
        'name' => $first_name . ' ' . $last_name,
        'email' => $email,
        'username' => $username,
        'login_time' => $login_time,
        'session_duration' => time() - $login_time
    ];
}

/**
 * Check if current user has admin privileges (for mixed access pages)
 */
function hasAdminPrivileges() {
    return isAdminLoggedIn();
}

/**
 * Validate admin session against database
 */
function validateAdminSession() {
    global $pdo;

    if (!isAdminLoggedIn()) {
        return false;
    }

    $admin_id = getAdminId();
    if (!$admin_id) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id FROM users
            WHERE id = ? AND role = 'admin' AND is_suspended = 0
        ");
        $stmt->execute([$admin_id]);

        if (!$stmt->fetch()) {
            // Invalid session, clear admin session
            adminLogout();
            return false;
        }

        return true;

    } catch (PDOException $e) {
        error_log("Admin session validation error: " . $e->getMessage());
        adminLogout();
        return false;
    }
}

/**
 * Get admin dashboard stats
 */
function getAdminDashboardStats() {
    global $pdo;
    
    try {
        $stats = [];
        
        // Total users (buyers only)
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'");
        $stats['total_users'] = $stmt->fetchColumn();
        
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $stats['total_products'] = $stmt->fetchColumn();
        
        // Total orders
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
        $stats['total_orders'] = $stmt->fetchColumn();
        
        // Total revenue
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status != 'Cancelled'");
        $stats['total_revenue'] = $stmt->fetchColumn();
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Admin dashboard stats error: " . $e->getMessage());
        return [
            'total_users' => 0,
            'total_products' => 0,
            'total_orders' => 0,
            'total_revenue' => 0
        ];
    }
}
