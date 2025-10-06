<?php
require_once 'includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: user.php");
    exit();
}

$error = $_SESSION['login_error'] ?? '';
$success = $_SESSION['login_success'] ?? '';
$googleError = $_SESSION['google_error'] ?? '';
$verificationSuccess = $_SESSION['verification_success'] ?? '';

// Clear messages after displaying
unset($_SESSION['login_error'], $_SESSION['login_success'], $_SESSION['google_error'], $_SESSION['verification_success']);

// Preserve old values
$oldEmail = $_SESSION['old_email'] ?? '';
unset($_SESSION['old_email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - ShoeStore</title>
    
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
                                    <h1 class="auth-title">Welcome Back</h1>
                                    <p class="auth-subtitle">Sign in to your ShoeStore account</p>
                                    
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
                                    
                                    <?php if ($success): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($verificationSuccess): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($verificationSuccess); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form action="login_process.php" method="POST">
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
                                        
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                            <label class="form-check-label" for="remember">
                                                Remember me
                                            </label>
                                        </div>
                                        
                                        <div class="forgot-password">
                                            <a href="forgot_password.php">Forgot your password?</a>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                        </button>
                                        
                                        <div class="divider">
                                            <span>OR</span>
                                        </div>
                                        
                                        <button type="button" class="btn btn-google" onclick="signInWithGoogle()">
                                            <i class="fab fa-google me-2"></i>Sign in with Google
                                        </button>
                                    </form>
                                    
                                    <div class="auth-link">
                                        Don't have an account? <a href="signup.php">Create one here</a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Side - Benefits -->
                            <div class="col-lg-6">
                                <div class="auth-right-clean">
                                    <h2 class="benefits-title">Welcome back! Here's what you can do:</h2>
                                    
                                    <ul class="benefits-list">
                                        <li>
                                            <i class="fas fa-shopping-cart"></i>
                                            Access your shopping cart
                                        </li>
                                        <li>
                                            <i class="fas fa-heart"></i>
                                            View your favorite products
                                        </li>
                                        <li>
                                            <i class="fas fa-bell"></i>
                                            Check your notifications
                                        </li>
                                        <li>
                                            <i class="fas fa-truck"></i>
                                            Track your orders
                                        </li>
                                        <li>
                                            <i class="fas fa-user"></i>
                                            Manage your profile
                                        </li>
                                        <li>
                                            <i class="fas fa-gift"></i>
                                            Enjoy member benefits
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

    <!-- Admin Access Icon -->
    <div class="admin-access-icon" onclick="openAdminLogin()">
        <i class="fas fa-user-shield"></i>
        <span class="admin-tooltip">Admin Access</span>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/auth.js"></script>

    <!-- Admin Access Script -->
    <script>
        function openAdminLogin() {
            window.open('admin/adminlogin.php', '_blank');
        }
    </script>

    <style>
        .admin-access-icon {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .admin-access-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .admin-access-icon i {
            color: white;
            font-size: 20px;
        }

        .admin-tooltip {
            position: absolute;
            bottom: 60px;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .admin-access-icon:hover .admin-tooltip {
            opacity: 1;
            visibility: visible;
        }

        .admin-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            right: 20px;
            border: 5px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.8);
        }
    </style>
</body>
</html>
