<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Log that the file is being accessed
error_log("signup_process.php accessed at " . date('Y-m-d H:i:s'));

require_once 'db.php'; // $pdo connection
require 'vendor/PHPMailer/src/Exception.php';
require 'vendor/PHPMailer/src/PHPMailer.php';
require 'vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send OTP email
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@shoearizz.store';
        $mail->Password   = 'Astron_202'; // ⚠️ replace w/ real pass
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('noreply@shoearizz.store', 'Shoearizz Store');
        $mail->addAddress($email, 'User');

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "<p>Hello <b>User</b>,</p>
                          <p>Your OTP code is: <b>{$otp}</b></p>
                          <p>This code will expire in 5 minutes.</p>";

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug: Log POST data
    error_log("POST data received: " . print_r($_POST, true));
    
    // Check if this is a resend OTP request
    if (isset($_POST['resend_otp']) && isset($_SESSION['pending_user_id'])) {
        $userId = $_SESSION['pending_user_id'];
        
        try {
            // Get user email
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate new OTP
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                
                // Update OTP in DB
                $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
                $stmt->execute([$otp, $expires_at, $userId]);
                
                // Send new OTP
                if (sendOTPEmail($user['email'], $otp)) {
                    $_SESSION['otp_success'] = "New OTP code sent to your email.";
                } else {
                    $_SESSION['otp_error'] = "Failed to send OTP. Please try again.";
                }
            } else {
                $_SESSION['otp_error'] = "Session expired. Please sign up again.";
                unset($_SESSION['pending_user_id'], $_SESSION['show_otp_modal']);
            }
        } catch (PDOException $e) {
            $_SESSION['otp_error'] = "An error occurred. Please try again.";
        }
        
        header("Location: signup.php");
        exit();
    }
    
    // Regular signup process
    $email      = trim($_POST['email'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $role       = 'user';

    // Preserve old values if error
    $_SESSION['old_email'] = $email;

    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['signup_error'] = "Email and password are required.";
        header("Location: signup.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['signup_error'] = "Invalid email address.";
        header("Location: signup.php");
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['signup_error'] = "Email already registered.";
            header("Location: signup.php");
            exit();
        }

        // Generate username from email
        $username = explode('@', $email)[0] . rand(100, 999);

        // Insert new user with is_verified = 0
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, username, password, role, is_verified) 
                               VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute(['User', 'User', $email, $username, $hashedPassword, $role]);

        $userId = $pdo->lastInsertId();

        // Generate OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        // Save OTP in DB
        $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
        $stmt->execute([$otp, $expires_at, $userId]);

        // Send OTP via email
        if (!sendOTPEmail($email, $otp)) {
            $_SESSION['signup_error'] = "Account created, but OTP email could not be sent. Please contact support.";
            header("Location: signup.php");
            exit();
        }

        // Clear old values
        unset($_SESSION['old_email']);

        // Set OTP modal trigger
        $_SESSION['pending_user_id'] = $userId;
        $_SESSION['show_otp_modal'] = true;
        $_SESSION['signup_success'] = "Account created successfully. Please check your email for the OTP.";

        header("Location: signup.php");
        exit();

    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
} else {
    header("Location: signup.php");
    exit();
}
?>
