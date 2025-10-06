<?php
// Simple admin login without complex session management
session_start();

$error = $_SESSION['admin_login_error'] ?? '';
$success = $_SESSION['admin_login_success'] ?? '';

// Clear messages after displaying
unset($_SESSION['admin_login_error'], $_SESSION['admin_login_success']);

// Preserve old values
$oldEmail = $_SESSION['old_admin_email'] ?? '';
unset($_SESSION['old_admin_email']);
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
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .admin-login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 500px;
            width: 100%;
        }
        
        .admin-login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .admin-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .admin-icon i {
            font-size: 32px;
            color: #fff;
        }
        
        .admin-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .admin-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        
        .admin-login-body {
            padding: 40px 30px;
        }
        
        .form-label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .admin-input {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .admin-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 14px 20px;
            font-weight: 600;
            font-size: 16px;
            color: white;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .security-notice {
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="admin-login-card">
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
            
            <form action="admin_login_process.php" method="POST" id="adminLoginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Admin Email
                    </label>
                    <input type="email" class="form-control admin-input" id="email" name="email" 
                           value="<?php echo htmlspecialchars($oldEmail); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <input type="password" class="form-control admin-input" id="password" name="password" required>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Keep me signed in
                    </label>
                </div>
                
                <button type="submit" class="btn btn-admin w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Access Admin Panel
                </button>
            </form>
        </div>
        
        <div class="security-notice">
            <i class="fas fa-shield-alt me-2"></i>
            This is a secure admin area. All access attempts are logged.
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
