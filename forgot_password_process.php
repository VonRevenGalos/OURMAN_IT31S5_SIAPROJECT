<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    
    // Validate input
    if (empty($email)) {
        $_SESSION['forgot_error'] = "Email address is required.";
        header("Location: forgot_password.php");
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['forgot_error'] = "Invalid email address.";
        header("Location: forgot_password.php");
        exit();
    }
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ? AND is_suspended = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date("Y-m-d H:i:s", strtotime("+1 hour"));
            
            // Store reset token (you might want to create a separate table for this)
            // For now, we'll use the otp_code field temporarily
            $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
            $stmt->execute([$resetToken, $expiresAt, $user['id']]);
            
            // Send reset email (simplified version)
            // In a real application, you would send an email with the reset link
            $_SESSION['forgot_success'] = "If an account with that email exists, we've sent a password reset link.";
        } else {
            // Don't reveal if email exists or not for security
            $_SESSION['forgot_success'] = "If an account with that email exists, we've sent a password reset link.";
        }
        
        header("Location: forgot_password.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['forgot_error'] = "An error occurred. Please try again.";
        header("Location: forgot_password.php");
        exit();
    }
} else {
    header("Location: forgot_password.php");
    exit();
}
?>
