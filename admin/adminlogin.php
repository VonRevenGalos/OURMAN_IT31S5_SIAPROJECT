<?php
/**
 * Admin Login Page
 * Secure admin authentication system with separate session management
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

// Check if already logged in as admin
if (isset($_SESSION['admin_logged_in']) &&
    $_SESSION['admin_logged_in'] === true &&
    isset($_SESSION['admin_user_id']) &&
    isset($_SESSION['admin_role']) &&
    $_SESSION['admin_role'] === 'admin') {

    // Verify admin session is still valid
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin' AND is_suspended = 0");
        $stmt->execute([$_SESSION['admin_user_id']]);
        if ($stmt->fetch()) {
            header("Location: dashboard.php");
            exit();
        } else {
            // Invalid session, clear admin session
            unset($_SESSION['admin_logged_in'], $_SESSION['admin_user_id'], $_SESSION['admin_role'],
                  $_SESSION['admin_email'], $_SESSION['admin_first_name'], $_SESSION['admin_last_name']);
        }
    } catch (PDOException $e) {
        // Database error, clear session for security
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_user_id'], $_SESSION['admin_role'],
              $_SESSION['admin_email'], $_SESSION['admin_first_name'], $_SESSION['admin_last_name']);
    }
}

$error = $_SESSION['admin_login_error'] ?? '';
$success = $_SESSION['admin_login_success'] ?? '';

// Clear messages after displaying
unset($_SESSION['admin_login_error'], $_SESSION['admin_login_success']);

// Preserve old values
$oldLogin = $_SESSION['old_admin_login'] ?? '';
unset($_SESSION['old_admin_login']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ShoeStore</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts - Inter (Professional & Readable) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin-auth.css">
</head>
<body class="admin-login-body">
    <div class="admin-login-container">
        <div class="container-fluid">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
                    <div class="admin-login-card" style="max-width: 500px; margin: 0 auto;">
                        <div class="admin-login-header">
                            <div class="admin-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h2 class="admin-title">Admin Portal</h2>
                            <p class="admin-subtitle">Secure Administrator Access</p>
                        </div>

                        <div class="admin-login-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                </div>
                            <?php endif; ?>

                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Admin Access Only:</strong> This login is restricted to users with administrator privileges (role='admin').
                            </div>

                            <form action="admin_login_process.php" method="POST" id="adminLoginForm">
                                <div class="mb-3">
                                    <label for="login" class="form-label">
                                        <i class="fas fa-user me-2"></i>Admin Username or Email
                                    </label>
                                    <input type="text" class="form-control admin-input" id="login" name="login"
                                           value="<?php echo htmlspecialchars($oldLogin); ?>"
                                           placeholder="Enter your username or email" required>
                                    <div class="form-text">
                                        <small class="text-muted">
                                            <i class="fas fa-shield-alt me-1"></i>
                                            Only accounts with admin role can access this panel
                                        </small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Admin Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control admin-input" id="password" name="password"
                                               placeholder="Enter your password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Remember me functionality temporarily disabled to fix redirect issues -->
                                <div class="alert alert-warning" style="font-size: 12px;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Note:</strong> Remember me functionality is temporarily disabled. Please login each session.
                                </div>

                                <button type="submit" class="btn btn-admin w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Access Admin Panel
                                </button>
                            </form>
                        </div>

                        <div class="admin-login-footer">
                            <div class="security-notice">
                                <i class="fas fa-shield-alt me-2"></i>
                                This is a secure admin area. All access attempts are logged.
                                <br><br>
                                <strong>Admin Access Requirements:</strong>
                                <ul class="text-start mt-2 mb-0" style="font-size: 11px;">
                                    <li>Must have <code>role='admin'</code> in database</li>
                                    <li>Account must not be suspended</li>
                                    <li>Can login with username or email</li>
                                </ul>

                                <?php
                                // Show available admin accounts for development/testing
                                if (isset($_GET['debug']) && $_GET['debug'] === 'show_admins') {
                                    try {
                                        global $pdo;
                                        $stmt = $pdo->query("SELECT username, email, first_name, last_name FROM users WHERE role = 'admin' AND is_suspended = 0 LIMIT 5");
                                        echo "<br><small><strong>Available Admin Accounts:</strong><br>";
                                        while ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "• " . htmlspecialchars($admin['username']) . " (" . htmlspecialchars($admin['email']) . ")<br>";
                                        }
                                        echo "</small>";
                                    } catch (Exception $e) {
                                        // Silently fail
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Admin Login JS -->
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Form validation
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const login = document.getElementById('login').value;
            const password = document.getElementById('password').value;

            if (!login || !password) {
                e.preventDefault();
                alert('Please fill in all fields.');
                return false;
            }

            // Basic validation - allow both username and email
            if (login.length < 3) {
                e.preventDefault();
                alert('Please enter a valid username or email address.');
                return false;
            }
        });
    </script>
</body>
</html>