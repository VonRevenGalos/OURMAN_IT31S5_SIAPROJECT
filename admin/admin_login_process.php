<?php
/**
 * Admin Login Processing
 * Secure admin authentication with separate session management
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
require_once __DIR__ . '/../db.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($login) || empty($password)) {
        $_SESSION['admin_login_error'] = "Please fill in all fields.";
        $_SESSION['old_admin_login'] = $login;
        header("Location: adminlogin.php");
        exit();
    }
    
    // Rate limiting - simple implementation
    $loginAttemptKey = 'admin_login_attempts_' . $_SERVER['REMOTE_ADDR'];
    $attempts = $_SESSION[$loginAttemptKey] ?? 0;
    $lastAttempt = $_SESSION[$loginAttemptKey . '_time'] ?? 0;
    
    // Reset attempts if more than 15 minutes have passed
    if (time() - $lastAttempt > 900) {
        $attempts = 0;
    }
    
    if ($attempts >= 5) {
        $_SESSION['admin_login_error'] = "Too many failed login attempts. Please try again in 15 minutes.";
        $_SESSION['old_admin_login'] = $login;
        header("Location: adminlogin.php");
        exit();
    }
    
    try {
        // Check if user exists by username or email, is not suspended, and is an admin
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, username, email, password, role, is_suspended, is_verified
            FROM users
            WHERE (email = ? OR username = ?)
            AND is_suspended = 0
            AND role = 'admin'
        ");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Increment failed attempts
            $_SESSION[$loginAttemptKey] = $attempts + 1;
            $_SESSION[$loginAttemptKey . '_time'] = time();
            
            // Log failed admin login attempt
            error_log("Failed admin login attempt for login: " . $login . " from IP: " . $_SERVER['REMOTE_ADDR'] . " (user not found or not admin)");

            // Log to audit system (create temporary session for logging)
            $_SESSION['temp_audit_email'] = $login;
            require_once 'includes/audit_logger.php';
            logAdminActivity('login', 'authentication', 'Failed login attempt - user not found or not admin', null, null, null, null, 'medium', 'failed');
            unset($_SESSION['temp_audit_email']);
            $_SESSION['admin_login_error'] = "Invalid admin credentials or insufficient privileges. Only admin users can access this panel.";
            $_SESSION['old_admin_login'] = $login;
            header("Location: adminlogin.php");
            exit();
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            // Increment failed attempts
            $_SESSION[$loginAttemptKey] = $attempts + 1;
            $_SESSION[$loginAttemptKey . '_time'] = time();
            
            // Log failed admin login attempt
            error_log("Failed admin login attempt (wrong password) for: " . $user['email'] . " from IP: " . $_SERVER['REMOTE_ADDR']);

            // Log to audit system (create temporary session for logging)
            $_SESSION['temp_audit_email'] = $user['email'];
            $_SESSION['temp_audit_id'] = $user['id'];
            $_SESSION['temp_audit_name'] = $user['first_name'] . ' ' . $user['last_name'];
            require_once 'includes/audit_logger.php';
            logAdminActivity('login', 'authentication', 'Failed login attempt - wrong password', null, null, null, null, 'medium', 'failed');
            unset($_SESSION['temp_audit_email'], $_SESSION['temp_audit_id'], $_SESSION['temp_audit_name']);
            $_SESSION['admin_login_error'] = "Invalid admin credentials. Please check your password.";
            $_SESSION['old_admin_login'] = $login;
            header("Location: adminlogin.php");
            exit();
        }
        
        // Login successful - clear failed attempts
        unset($_SESSION[$loginAttemptKey], $_SESSION[$loginAttemptKey . '_time']);
        
        // Set admin session data (separate from user sessions)
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_first_name'] = $user['first_name'];
        $_SESSION['admin_last_name'] = $user['last_name'];
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_last_activity'] = time();

        // Log successful admin login
        error_log("Successful admin login for: " . $user['email'] . " (ID: " . $user['id'] . ") from IP: " . $_SERVER['REMOTE_ADDR']);

        // Log to audit system
        require_once 'includes/audit_logger.php';
        auditLogin('success', 'Successful login');

        // Clear old values
        unset($_SESSION['old_admin_login']);

        // Set success message
        $_SESSION['admin_login_success'] = "Welcome to the admin panel, " . $user['first_name'] . "!";

        // Redirect to admin dashboard
        header("Location: dashboard.php");
        exit();
        
    } catch (PDOException $e) {
        // Log database error
        error_log("Admin login database error: " . $e->getMessage());
        $_SESSION['admin_login_error'] = "System error. Please try again later.";
        $_SESSION['old_admin_login'] = $login;
        header("Location: adminlogin.php");
        exit();
    }
} else {
    // Not a POST request, redirect to login page
    header("Location: adminlogin.php");
    exit();
}
?>
