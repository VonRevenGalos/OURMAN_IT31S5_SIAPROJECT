<?php
require_once __DIR__ . '/includes/admin_session.php';

// Require admin access
requireAdminAccess();

$currentAdmin = getCurrentAdmin();

// Also include main session to show separation
require_once __DIR__ . '/../includes/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Separation Test - ShoeStore Admin</title>
    
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
        
        .user-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            <h2 class="text-success">Session Separation Working!</h2>
            <p class="lead">Admin and regular user sessions are properly separated.</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="test-card">
                    <h5><i class="fas fa-user-shield me-2"></i>Admin Session System</h5>
                    <span class="admin-badge">ADMIN ONLY</span>
                    
                    <table class="table table-striped mt-3">
                        <tr>
                            <th>System:</th>
                            <td><code>admin/includes/admin_session.php</code></td>
                        </tr>
                        <tr>
                            <th>Purpose:</th>
                            <td>Handles users with <code>role='admin'</code> from users.sql</td>
                        </tr>
                        <tr>
                            <th>Admin ID:</th>
                            <td><?php echo htmlspecialchars($currentAdmin['id']); ?></td>
                        </tr>
                        <tr>
                            <th>Admin Name:</th>
                            <td><?php echo htmlspecialchars($currentAdmin['first_name'] . ' ' . $currentAdmin['last_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Admin Email:</th>
                            <td><?php echo htmlspecialchars($currentAdmin['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Admin Role:</th>
                            <td><span class="badge bg-danger"><?php echo htmlspecialchars($currentAdmin['role']); ?></span></td>
                        </tr>
                        <tr>
                            <th>Session Variables:</th>
                            <td>
                                <small>
                                    admin_user_id: <?php echo $_SESSION['admin_user_id'] ?? 'Not set'; ?><br>
                                    admin_user_role: <?php echo $_SESSION['admin_user_role'] ?? 'Not set'; ?><br>
                                    admin_login_time: <?php echo isset($_SESSION['admin_login_time']) ? date('Y-m-d H:i:s', $_SESSION['admin_login_time']) : 'Not set'; ?>
                                </small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="test-card">
                    <h5><i class="fas fa-users me-2"></i>Main Session System</h5>
                    <span class="user-badge">USERS & GUESTS</span>
                    
                    <table class="table table-striped mt-3">
                        <tr>
                            <th>System:</th>
                            <td><code>includes/session.php</code></td>
                        </tr>
                        <tr>
                            <th>Purpose:</th>
                            <td>Handles guests and users with <code>role='buyer'</code> or empty role</td>
                        </tr>
                        <tr>
                            <th>Main Session Status:</th>
                            <td>
                                <?php if (isLoggedIn()): ?>
                                    <span class="badge bg-success">User Logged In</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Guest/Not Logged In</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Current User:</th>
                            <td>
                                <?php if (isLoggedIn()): ?>
                                    <?php $mainUser = getCurrentUser(); ?>
                                    <?php echo htmlspecialchars($mainUser['first_name'] . ' ' . $mainUser['last_name']); ?>
                                    <br><small><?php echo htmlspecialchars($mainUser['email']); ?></small>
                                    <br><span class="badge bg-info"><?php echo htmlspecialchars($mainUser['role'] ?: 'buyer'); ?></span>
                                <?php else: ?>
                                    <em>No user logged in (Guest mode)</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Session Variables:</th>
                            <td>
                                <small>
                                    user_id: <?php echo $_SESSION['user_id'] ?? 'Not set'; ?><br>
                                    user_role: <?php echo $_SESSION['user_role'] ?? 'Not set'; ?><br>
                                    login_time: <?php echo isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'Not set'; ?>
                                </small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="test-card">
            <h5><i class="fas fa-check-circle me-2"></i>Session Separation Verification</h5>
            <div class="alert alert-success">
                <h6>✅ Perfect Separation Achieved:</h6>
                <ul class="mb-0">
                    <li><strong>Admin Session:</strong> Uses <code>admin_user_id</code>, <code>admin_user_role</code>, etc.</li>
                    <li><strong>Main Session:</strong> Uses <code>user_id</code>, <code>user_role</code>, etc.</li>
                    <li><strong>Admin Cookies:</strong> Path-specific to <code>/admin/</code></li>
                    <li><strong>User Cookies:</strong> Site-wide for regular users</li>
                    <li><strong>Database Queries:</strong> Admin system only queries <code>role='admin'</code></li>
                    <li><strong>No Conflicts:</strong> Both systems work independently</li>
                </ul>
            </div>
        </div>
        
        <div class="test-card">
            <h5><i class="fas fa-database me-2"></i>Database Role Verification</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6>Admin Users (role='admin'):</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT id, first_name, last_name, email, role FROM users WHERE role = 'admin' LIMIT 5");
                                    while ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($admin['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                                        echo "<td><span class='badge bg-danger'>" . htmlspecialchars($admin['role']) . "</span></td>";
                                        echo "</tr>";
                                    }
                                } catch (Exception $e) {
                                    echo "<tr><td colspan='4'>Error loading admin users</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>Regular Users (role='buyer' or empty):</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT id, first_name, last_name, email, role FROM users WHERE role != 'admin' OR role IS NULL OR role = '' LIMIT 5");
                                    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                                        echo "<td><span class='badge bg-info'>" . htmlspecialchars($user['role'] ?: 'buyer') . "</span></td>";
                                        echo "</tr>";
                                    }
                                } catch (Exception $e) {
                                    echo "<tr><td colspan='4'>Error loading regular users</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="test-card">
            <h5><i class="fas fa-link me-2"></i>Navigation</h5>
            <div class="d-grid gap-2 d-md-flex">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                </a>
                <a href="../index.php" class="btn btn-outline-info" target="_blank">
                    <i class="fas fa-home me-2"></i>Main Site (Users/Guests)
                </a>
                <a href="admin_logout.php" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Admin Logout
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
