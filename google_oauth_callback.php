<?php
session_start();
require_once 'db.php';
require_once 'includes/session.php';
require_once 'includes/google_oauth_config.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    try {
        // Exchange code for access token
        $tokenData = getGoogleAccessToken($code);
        
        if (!$tokenData || !isset($tokenData['access_token'])) {
            $_SESSION['google_error'] = 'Failed to authenticate with Google.';
            header("Location: signup.php");
            exit();
        }
        
        // Get user info from Google
        $userInfo = getGoogleUserInfo($tokenData['access_token']);
        
        if (!$userInfo) {
            $_SESSION['google_error'] = 'Failed to get user information from Google.';
            header("Location: signup.php");
            exit();
        }
        
        $email = $userInfo['email'];
        $firstName = $userInfo['given_name'] ?? 'User';
        $lastName = $userInfo['family_name'] ?? 'User';
        $googleId = $userInfo['id'];
        
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingUser) {
            // User exists, log them in
            if ($existingUser['is_suspended']) {
                $_SESSION['google_error'] = 'Your account has been suspended.';
                header("Location: signup.php");
                exit();
            }
            
            // Update user info from Google
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
            $stmt->execute([$firstName, $lastName, $existingUser['id']]);
            
            // Log user in
            $sessionManager = new SessionManager($pdo);
            $sessionManager->login($existingUser['id'], true); // Remember me
            
            $_SESSION['login_success'] = "Welcome back, $firstName!";
            
            // Close popup and redirect parent window
            echo "<script>
                if (window.opener) {
                    window.opener.location.href = 'index.php';
                    window.close();
                } else {
                    window.location.href = 'index.php';
                }
            </script>";
            exit();
            
        } else {
            // New user, create account
            $username = explode('@', $email)[0] . rand(100, 999);
            
            // Generate a random password (user won't need it for Google login)
            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, username, password, role, is_verified) 
                                   VALUES (?, ?, ?, ?, ?, 'user', 1)");
            $stmt->execute([$firstName, $lastName, $email, $username, $hashedPassword]);
            
            $userId = $pdo->lastInsertId();
            
            // Log user in immediately
            $sessionManager = new SessionManager($pdo);
            $sessionManager->login($userId, true); // Remember me
            
            $_SESSION['login_success'] = "Welcome to ShoeStore, $firstName!";
            
            // Close popup and redirect parent window
            echo "<script>
                if (window.opener) {
                    window.opener.location.href = 'index.php';
                    window.close();
                } else {
                    window.location.href = 'index.php';
                }
            </script>";
            exit();
        }
        
    } catch (Exception $e) {
        $_SESSION['google_error'] = 'An error occurred during Google authentication.';
        
        // Close popup and redirect parent window
        echo "<script>
            if (window.opener) {
                window.opener.location.href = 'signup.php';
                window.close();
            } else {
                window.location.href = 'signup.php';
            }
        </script>";
        exit();
    }
    
} else if (isset($_GET['error'])) {
    // User denied permission or other error
    $_SESSION['google_error'] = 'Google authentication was cancelled or failed.';
    
    // Close popup and redirect parent window
    echo "<script>
        if (window.opener) {
            window.opener.location.href = 'signup.php';
            window.close();
        } else {
            window.location.href = 'signup.php';
        }
    </script>";
    exit();
    
} else {
    // No code or error, redirect to signup
    echo "<script>
        if (window.opener) {
            window.opener.location.href = 'signup.php';
            window.close();
        } else {
            window.location.href = 'signup.php';
        }
    </script>";
    exit();
}
?>
