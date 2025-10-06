<?php
session_start();
require_once 'db.php';
require_once 'includes/session.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);
    
    // Preserve email for error display
    $_SESSION['old_email'] = $email;
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Email and password are required.";
        header("Location: login.php");
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_error'] = "Invalid email address.";
        header("Location: login.php");
        exit();
    }
    
    try {
        // Check if user exists and is verified
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_suspended = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: login.php");
            exit();
        }
        
        // Check if user is verified
        if (!$user['is_verified']) {
            $_SESSION['login_error'] = "Please verify your email address before logging in.";
            header("Location: login.php");
            exit();
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: login.php");
            exit();
        }
        
        // Login successful
        $sessionManager = new SessionManager($pdo);
        $sessionManager->login($user['id'], $remember);
        
        // Clear old values
        unset($_SESSION['old_email']);
        
        // Set success message
        $_SESSION['login_success'] = "Welcome back, " . $user['first_name'] . "!";
        
        // Redirect to user page or intended destination
        $redirectTo = $_SESSION['intended_destination'] ?? 'user.php';
        unset($_SESSION['intended_destination']);
        
        header("Location: $redirectTo");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['login_error'] = "An error occurred. Please try again.";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
