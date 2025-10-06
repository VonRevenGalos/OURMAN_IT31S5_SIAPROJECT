<?php
require_once 'includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: user.php");
    exit();
}

$error = $_SESSION['signup_error'] ?? '';
$success = $_SESSION['signup_success'] ?? '';
$googleError = $_SESSION['google_error'] ?? '';
$otpError = $_SESSION['otp_error'] ?? '';
$otpSuccess = $_SESSION['otp_success'] ?? '';
$showOtpModal = $_SESSION['show_otp_modal'] ?? false;

// Clear messages after displaying
unset($_SESSION['signup_error'], $_SESSION['signup_success'], $_SESSION['google_error'], $_SESSION['otp_error'], $_SESSION['otp_success'], $_SESSION['show_otp_modal']);

// Preserve old values
$oldEmail = $_SESSION['old_email'] ?? '';
unset($_SESSION['old_email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - ShoeStore</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="auth-card-clean">
                        <div class="row g-0">
                            <!-- Left Side - Form -->
                            <div class="col-lg-6">
                                <div class="auth-left-clean">
                                    <h1 class="auth-title">Create Account</h1>
                                    <p class="auth-subtitle">Join ShoeStore today</p>
                                    
                                    <?php if ($error): ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($googleError): ?>
                                        <div class="alert alert-danger">
                                            <i class="fab fa-google me-2"></i><?php echo htmlspecialchars($googleError); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($otpError): ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($otpError); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($otpSuccess): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($otpSuccess); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($success): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form action="signup_process.php" method="POST" id="signupForm">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($oldEmail); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-user-plus me-2"></i>Create Account
                                        </button>
                                        
                                        <div class="divider">
                                            <span>OR</span>
                                        </div>
                                        
                                        <button type="button" class="btn btn-google" onclick="signInWithGoogle()">
                                            <i class="fab fa-google me-2"></i>Sign up with Google
                                        </button>
                                    </form>
                                    
                                    <div class="auth-link">
                                        Already have an account? <a href="login.php">Sign in here</a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Side - Benefits -->
                            <div class="col-lg-6">
                                <div class="auth-right-clean">
                                    <h2 class="benefits-title">Create an account to enhance your shopping experience with the help of our customised services:</h2>
                                    
                                    <ul class="benefits-list">
                                        <li>
                                            <i class="fas fa-bell"></i>
                                            Stay up to date with the latest news
                                        </li>
                                        <li>
                                            <i class="fas fa-bolt"></i>
                                            Buy faster
                                        </li>
                                        <li>
                                            <i class="fas fa-heart"></i>
                                            Save your favourite products
                                        </li>
                                        <li>
                                            <i class="fas fa-shopping-cart"></i>
                                            Quick checkout process
                                        </li>
                                        <li>
                                            <i class="fas fa-truck"></i>
                                            Track your orders
                                        </li>
                                        <li>
                                            <i class="fas fa-gift"></i>
                                            Exclusive member discounts
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- OTP Verification Modal -->
    <?php if ($showOtpModal): ?>
    <div class="modal fade show" id="otpModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verify Your Email</h5>
                </div>
                <div class="modal-body">
                    <p class="text-center mb-4">We've sent a 6-digit code to your email address. Please enter it below:</p>
                    
                    <form action="verify_otp.php" method="POST">
                        <div class="mb-3">
                            <input type="text" class="form-control otp-input" name="otp_code" 
                                   placeholder="000000" maxlength="6" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Verify Email</button>
                            <form action="signup_process.php" method="POST" class="d-inline">
                                <input type="hidden" name="resend_otp" value="1">
                                <button type="submit" class="btn btn-outline-secondary w-100">Resend Code</button>
                            </form>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/auth.js"></script>
    
    <!-- Debug Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const signupForm = document.getElementById('signupForm');
        if (signupForm) {
            signupForm.addEventListener('submit', function(e) {
                console.log('Form submit event triggered');
                console.log('Form action:', this.action);
                console.log('Form method:', this.method);
                console.log('Form data:', new FormData(this));
            });
        }
        
        const submitBtn = document.querySelector('#signupForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                console.log('Submit button clicked');
            });
        }
    });
    </script>
</body>
</html>
