<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otpCode = trim($_POST['otp_code'] ?? '');
    $userId = $_SESSION['pending_user_id'] ?? null;
    
    if (empty($otpCode) || !$userId) {
        $_SESSION['otp_error'] = "Invalid OTP or session expired.";
        header("Location: signup.php");
        exit();
    }
    
    try {
        // Verify OTP
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND otp_code = ? AND otp_expires_at > NOW()");
        $stmt->execute([$userId, $otpCode]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $_SESSION['otp_error'] = "Invalid or expired OTP code.";
            header("Location: signup.php");
            exit();
        }
        
        // Mark user as verified
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Clear session
        unset($_SESSION['pending_user_id'], $_SESSION['show_otp_modal']);
        
        // Set success message
        $_SESSION['verification_success'] = "Email verified successfully! You can now log in.";
        
        // Redirect to login
        header("Location: login.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['otp_error'] = "An error occurred. Please try again.";
        header("Location: signup.php");
        exit();
    }
} else {
    header("Location: signup.php");
    exit();
}
?>
