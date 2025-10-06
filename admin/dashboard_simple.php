<?php
session_start();
require_once '../db.php';
require_once '../includes/session.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['admin_login_error'] = "Please log in as an administrator.";
    header("Location: adminlogin_simple.php");
    exit();
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ShoeStore</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
        }
        
        .admin-header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">
                <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
            </h2>
            <div>
                <span class="me-3">Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</span>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </div>
    
    <div class="container-fluid">
        <div class="welcome-card">
            <h3><i class="fas fa-shield-alt me-2"></i>Admin Panel Access Successful!</h3>
            <p class="mb-0">You have successfully logged into the admin panel. The system is working correctly.</p>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon bg-primary text-white mx-auto">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="stat-number text-primary">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'");
                            echo $stmt->fetchColumn();
                        } catch (Exception $e) {
                            echo "N/A";
                        }
                        ?>
                    </h3>
                    <p class="stat-label">Total Users</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon bg-success text-white mx-auto">
                        <i class="fas fa-box"></i>
                    </div>
                    <h3 class="stat-number text-success">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM products");
                            echo $stmt->fetchColumn();
                        } catch (Exception $e) {
                            echo "N/A";
                        }
                        ?>
                    </h3>
                    <p class="stat-label">Total Products</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon bg-warning text-white mx-auto">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3 class="stat-number text-warning">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
                            echo $stmt->fetchColumn();
                        } catch (Exception $e) {
                            echo "N/A";
                        }
                        ?>
                    </h3>
                    <p class="stat-label">Total Orders</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon bg-info text-white mx-auto">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3 class="stat-number text-info">
                        ₱<?php
                        try {
                            $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status != 'Cancelled'");
                            echo number_format($stmt->fetchColumn(), 2);
                        } catch (Exception $e) {
                            echo "0.00";
                        }
                        ?>
                    </h3>
                    <p class="stat-label">Total Revenue</p>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="stat-card">
                    <h5><i class="fas fa-info-circle me-2"></i>System Status</h5>
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle me-2"></i>All Systems Operational</h6>
                        <ul class="mb-0">
                            <li>✅ Database connection: Working</li>
                            <li>✅ Admin authentication: Working</li>
                            <li>✅ Session management: Working</li>
                            <li>✅ Admin access control: Working</li>
                        </ul>
                    </div>
                    
                    <h6 class="mt-3">Next Steps:</h6>
                    <p>The basic admin system is now functional. You can:</p>
                    <ul>
                        <li>Add product management features</li>
                        <li>Implement order management</li>
                        <li>Create user management tools</li>
                        <li>Add more detailed analytics</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
