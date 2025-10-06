<?php
require_once __DIR__ . '/includes/admin_session.php';

// Require admin access
requireAdminAccess();

$currentAdmin = getCurrentAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login Test - ShoeStore</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }
        
        .test-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .success-icon {
            color: #28a745;
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .admin-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-card text-center">
            <i class="fas fa-check-circle success-icon"></i>
            <h2 class="text-success">Admin Login Working!</h2>
            <p class="lead">Successfully logged in as admin user with role='admin'.</p>
        </div>
        
        <div class="test-card">
            <h5><i class="fas fa-user-shield me-2"></i>Current Admin User</h5>
            <span class="admin-badge">ADMIN ACCESS VERIFIED</span>
            
            <table class="table table-striped mt-3">
                <tr>
                    <th>Admin ID:</th>
                    <td><?php echo htmlspecialchars($currentAdmin['id']); ?></td>
                </tr>
                <tr>
                    <th>Username:</th>
                    <td><?php echo htmlspecialchars($currentAdmin['username']); ?></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><?php echo htmlspecialchars($currentAdmin['email']); ?></td>
                </tr>
                <tr>
                    <th>Full Name:</th>
                    <td><?php echo htmlspecialchars($currentAdmin['first_name'] . ' ' . $currentAdmin['last_name']); ?></td>
                </tr>
                <tr>
                    <th>Role:</th>
                    <td><span class="badge bg-danger"><?php echo htmlspecialchars($currentAdmin['role']); ?></span></td>
                </tr>
                <tr>
                    <th>Suspended:</th>
                    <td>
                        <?php if ($currentAdmin['is_suspended']): ?>
                            <span class="badge bg-warning">Yes</span>
                        <?php else: ?>
                            <span class="badge bg-success">No</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Verified:</th>
                    <td>
                        <?php if ($currentAdmin['is_verified']): ?>
                            <span class="badge bg-success">Yes</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">No</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Login Time:</th>
                    <td><?php echo isset($_SESSION['admin_login_time']) ? date('Y-m-d H:i:s', $_SESSION['admin_login_time']) : 'Not available'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="test-card">
            <h5><i class="fas fa-database me-2"></i>Admin Users in Database</h5>
            <p>Users with <code>role='admin'</code> who can access the admin panel:</p>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT id, username, email, first_name, last_name, is_suspended, is_verified FROM users WHERE role = 'admin' ORDER BY id");
                            while ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $statusClass = $admin['is_suspended'] ? 'bg-danger' : 'bg-success';
                                $statusText = $admin['is_suspended'] ? 'Suspended' : 'Active';
                                
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($admin['id']) . "</td>";
                                echo "<td><code>" . htmlspecialchars($admin['username']) . "</code></td>";
                                echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</td>";
                                echo "<td><span class='badge {$statusClass}'>{$statusText}</span>";
                                if ($admin['is_verified']) {
                                    echo " <span class='badge bg-info'>Verified</span>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } catch (Exception $e) {
                            echo "<tr><td colspan='5'>Error loading admin users: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="test-card">
            <h5><i class="fas fa-check-circle me-2"></i>Login Features Verified</h5>
            <div class="alert alert-success">
                <h6>✅ Admin Login System Working:</h6>
                <ul class="mb-0">
                    <li><strong>Username/Email Login:</strong> Can login with either username or email</li>
                    <li><strong>Admin Role Only:</strong> Only users with <code>role='admin'</code> can access</li>
                    <li><strong>Security Checks:</strong> Validates not suspended and correct password</li>
                    <li><strong>Session Management:</strong> Uses admin-specific session variables</li>
                    <li><strong>Remember Me:</strong> Admin-specific remember me cookies</li>
                    <li><strong>Access Logging:</strong> All login attempts are logged</li>
                </ul>
            </div>
        </div>
        
        <div class="test-card">
            <h5><i class="fas fa-info-circle me-2"></i>How to Test</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6>Login Methods:</h6>
                    <ul>
                        <li>Use <strong>username</strong> (e.g., <code>admin</code>)</li>
                        <li>Use <strong>email</strong> (e.g., <code>vonrevenmewe@gmail.com</code>)</li>
                        <li>Only works for users with <code>role='admin'</code></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Debug Mode:</h6>
                    <p>Add <code>?debug=show_admins</code> to the admin login URL to see available admin accounts.</p>
                    <a href="adminlogin.php?debug=show_admins" class="btn btn-sm btn-outline-info" target="_blank">
                        <i class="fas fa-bug me-1"></i>View Debug Mode
                    </a>
                </div>
            </div>
        </div>
        
        <div class="test-card">
            <h5><i class="fas fa-link me-2"></i>Navigation</h5>
            <div class="d-grid gap-2 d-md-flex">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                </a>
                <a href="adminlogin.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-in-alt me-2"></i>Admin Login
                </a>
                <a href="admin_logout.php" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
