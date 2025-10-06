<?php
require_once 'includes/admin_session.php';

$pageTitle = "Security Test";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <!-- Header -->
            <div class="admin-header">
                <div class="header-left">
                    <button class="btn btn-link d-md-none" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="mb-0">Security Test</h4>
                </div>
                <div class="header-right">
                    <span class="text-success">
                        <i class="fas fa-shield-alt me-2"></i>Admin Access Verified
                    </span>
                </div>
            </div>
            
            <!-- Content -->
            <div class="admin-content">
                <div class="row">
                    <div class="col-12">
                        <div class="admin-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Admin Security Test Passed
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-shield-alt me-2"></i>Security Verification Successful</h6>
                                    <p class="mb-0">
                                        If you can see this page, it means the admin security system is working correctly. 
                                        Only users with admin role can access this area.
                                    </p>
                                </div>
                                
                                <h6>Current Admin Session Information:</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="200">Admin Name</th>
                                            <td><?php echo htmlspecialchars(getCurrentAdmin()['first_name'] . ' ' . getCurrentAdmin()['last_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo htmlspecialchars(getCurrentAdmin()['email']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>User ID</th>
                                            <td><?php echo getCurrentAdmin()['id']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Role</th>
                                            <td><span class="badge bg-primary"><?php echo getCurrentAdmin()['role']; ?></span></td>
                                        </tr>
                                        <tr>
                                            <th>Session Duration</th>
                                            <td><?php echo gmdate('H:i:s', getAdminSessionInfo()['session_duration']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>IP Address</th>
                                            <td><?php echo $_SERVER['REMOTE_ADDR']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <h6 class="mt-4">Security Features Implemented:</h6>
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Admin role verification required
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Session validation on every page load
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Automatic redirect for non-admin users
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Security headers implemented
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Access logging for security monitoring
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Secure session management
                                    </li>
                                </ul>
                                
                                <div class="mt-4">
                                    <a href="dashboard.php" class="btn btn-primary">
                                        <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                                    </a>
                                    <a href="admin_logout.php" class="btn btn-outline-danger ms-2">
                                        <i class="fas fa-sign-out-alt me-2"></i>Test Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
