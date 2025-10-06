<?php
/**
 * Simple Admin Authentication
 * Lightweight admin authentication without complex session dependencies
 */

// Set secure session cookie parameters BEFORE starting session
if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/../../db.php';

/**
 * Check if user is logged in (simple check)
 */
function isAdminLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'admin';
}

/**
 * Get current admin user data
 */
function getCurrentAdminUser() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['user_role'],
        'email' => $_SESSION['user_email'] ?? '',
        'first_name' => $_SESSION['user_first_name'] ?? '',
        'last_name' => $_SESSION['user_last_name'] ?? '',
        'login_time' => $_SESSION['login_time'] ?? time()
    ];
}

/**
 * Check admin remember me cookie
 */
function checkAdminRememberMe() {
    if (isset($_COOKIE['admin_remember']) && !isAdminLoggedIn()) {
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
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_first_name'] = $user['first_name'];
                $_SESSION['user_last_name'] = $user['last_name'];
                $_SESSION['login_time'] = time();
                $_SESSION['admin_login'] = true;
                $_SESSION['admin_login_time'] = time();
                return true;
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
    // Check admin remember me cookie first
    checkAdminRememberMe();
    
    // Check if user is logged in
    if (!isAdminLoggedIn()) {
        $_SESSION['admin_login_error'] = "Please log in to access the admin panel.";
        header("Location: adminlogin.php");
        exit();
    }
    
    // Log admin access
    $user = getCurrentAdminUser();
    $page = basename($_SERVER['PHP_SELF']);
    error_log("Admin access: User {$user['email']} (ID: {$user['id']}) accessed {$page} from IP: " . $_SERVER['REMOTE_ADDR']);
}

/**
 * Admin logout
 */
function adminLogout() {
    // Log admin logout
    $user = getCurrentAdminUser();
    if ($user) {
        error_log("Admin logout: User {$user['email']} (ID: {$user['id']}) logged out from IP: " . $_SERVER['REMOTE_ADDR']);
        
        // Clear admin remember me cookie and database token
        if (isset($_COOKIE['admin_remember'])) {
            setcookie('admin_remember', '', time() - 3600, '/admin/', '', true, true);
            
            // Clear admin token from database
            try {
                global $pdo;
                $stmt = $pdo->prepare("UPDATE users SET remember_selector = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
            } catch (PDOException $e) {
                error_log("Error clearing admin token: " . $e->getMessage());
            }
        }
    }
    
    // Clear all session data
    session_unset();
    session_destroy();
    
    // Start new session for messages
    session_start();
    $_SESSION['admin_login_success'] = "You have been successfully logged out.";
    
    header("Location: adminlogin.php");
    exit();
}

/**
 * Get admin session info
 */
function getAdminSessionInfo() {
    $user = getCurrentAdminUser();
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
        'login_time' => $user['login_time'],
        'session_duration' => time() - $user['login_time']
    ];
}
?>
