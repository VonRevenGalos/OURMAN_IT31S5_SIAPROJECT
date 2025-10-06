<?php
/**
 * Professional Users Management System
 * Clean, Modern UI with Real-time Updates
 */

// Include admin authentication
require_once __DIR__ . '/includes/admin_auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: adminlogin.php');
    exit();
}

// Validate admin session
if (!validateAdminSession()) {
    header('Location: adminlogin.php');
    exit();
}

// Get current admin user info
$currentUser = getCurrentAdminUser();

// Database connection
require_once __DIR__ . '/../db.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'suspend_user':
            if (isset($_POST['user_id'])) {
                $userId = (int)$_POST['user_id'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE users SET is_suspended = 1 WHERE id = ? AND (role IS NULL OR role = '')");
                    $stmt->execute([$userId]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'User suspended successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'User not found or cannot be suspended']);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit();

        case 'unsuspend_user':
            if (isset($_POST['user_id'])) {
                $userId = (int)$_POST['user_id'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE users SET is_suspended = 0 WHERE id = ? AND (role IS NULL OR role = '')");
                    $stmt->execute([$userId]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'User unsuspended successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'User not found or cannot be unsuspended']);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit();

        case 'get_users_data':
            $userType = $_POST['user_type'] ?? 'regular';
            $page = max(1, (int)($_POST['page'] ?? 1));

            try {
                $result = getUsersData($pdo, $userType, $page);
                echo json_encode(['success' => true, 'data' => $result]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();

        case 'get_user_stats':
            try {
                $stats = getUserStats($pdo);
                echo json_encode(['success' => true, 'stats' => $stats]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();

        case 'update_user':
            if (isset($_POST['user_id'])) {
                $userId = (int)$_POST['user_id'];
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $gender = $_POST['gender'] ?? null;
                $phone = trim($_POST['phone'] ?? '');

                try {
                    // Check if username/email already exists for other users
                    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                    $checkStmt->execute([$username, $email, $userId]);
                    
                    if ($checkStmt->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                        exit();
                    }

                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET first_name = ?, last_name = ?, username = ?, email = ?, gender = ?, phone = ?
                        WHERE id = ? AND (role IS NULL OR role = '')
                    ");
                    $stmt->execute([$firstName, $lastName, $username, $email, $gender, $phone, $userId]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'User not found or no changes made']);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit();

        case 'add_admin':
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            try {
                // Check if username/email already exists
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $checkStmt->execute([$username, $email]);
                
                if ($checkStmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                    exit();
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (first_name, last_name, username, email, password, role, is_suspended, is_verified)
                    VALUES (?, ?, ?, ?, ?, 'admin', 0, 1)
                ");
                $stmt->execute([$firstName, $lastName, $username, $email, $hashedPassword]);
                
                echo json_encode(['success' => true, 'message' => 'Admin user created successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            exit();

        case 'update_admin':
            if (isset($_POST['user_id'])) {
                $userId = (int)$_POST['user_id'];
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                try {
                    // Check if username/email already exists for other users
                    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                    $checkStmt->execute([$username, $email, $userId]);
                    
                    if ($checkStmt->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                        exit();
                    }

                    if (!empty($password)) {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET first_name = ?, last_name = ?, username = ?, email = ?, password = ?
                            WHERE id = ? AND role = 'admin'
                        ");
                        $stmt->execute([$firstName, $lastName, $username, $email, $hashedPassword, $userId]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET first_name = ?, last_name = ?, username = ?, email = ?
                            WHERE id = ? AND role = 'admin'
                        ");
                        $stmt->execute([$firstName, $lastName, $username, $email, $userId]);
                    }
                    
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'Admin updated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Admin not found or no changes made']);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit();

        case 'delete_admin':
            if (isset($_POST['user_id'])) {
                $userId = (int)$_POST['user_id'];
                
                // Prevent deleting current admin
                if ($userId == $currentUser['id']) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete your own admin account']);
                    exit();
                }
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
                    $stmt->execute([$userId]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'Admin deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Admin not found or cannot be deleted']);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit();
    }
}

// Helper Functions
function getUserStats($pdo) {
    $stmt = $pdo->query("
        SELECT
            COUNT(CASE WHEN role IS NULL OR role = '' THEN 1 END) as total_users,
            COUNT(CASE WHEN (role IS NULL OR role = '') AND is_suspended = 0 THEN 1 END) as active_users,
            COUNT(CASE WHEN (role IS NULL OR role = '') AND is_suspended = 1 THEN 1 END) as suspended_users,
            COUNT(CASE WHEN role = 'admin' THEN 1 END) as total_admins,
            COUNT(CASE WHEN (role IS NULL OR role = '') AND is_verified = 1 THEN 1 END) as verified_users,
            COUNT(CASE WHEN (role IS NULL OR role = '') AND is_verified = 0 THEN 1 END) as unverified_users
        FROM users
    ");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUsersData($pdo, $userType = 'regular', $page = 1) {
    $itemsPerPage = 20;
    $offset = ($page - 1) * $itemsPerPage;

    if ($userType === 'admin') {
        $whereClause = "WHERE role = 'admin'";
    } else {
        $whereClause = "WHERE (role IS NULL OR role = '')";
    }

    // Count total users
    $countQuery = "SELECT COUNT(*) FROM users $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute();
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $itemsPerPage);

    // Get users
    $query = "
        SELECT id, first_name, last_name, username, email, gender, phone, is_suspended, is_verified, role
        FROM users 
        $whereClause 
        ORDER BY id DESC 
        LIMIT $itemsPerPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'users' => $users,
        'totalUsers' => $totalUsers,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ];
}

// Initialize data for page load
try {
    $stats = getUserStats($pdo);
} catch (PDOException $e) {
    error_log("Users initialization error: " . $e->getMessage());
    $stats = [
        'total_users' => 0,
        'active_users' => 0,
        'suspended_users' => 0,
        'total_admins' => 0,
        'verified_users' => 0,
        'unverified_users' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - ShoeStore Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/inventory.css">

    <style>
        /* Custom tab styling for better visibility */
        .nav-tabs .nav-link {
            background-color: #343a40 !important;
            color: white !important;
            border: 1px solid #495057 !important;
            margin-right: 2px;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            background-color: #495057 !important;
            color: white !important;
            border-color: #6c757d !important;
            transform: translateY(-1px);
        }

        .nav-tabs .nav-link.active {
            background-color: #212529 !important;
            color: white !important;
            border-color: #495057 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-tabs .nav-link.active:hover {
            background-color: #212529 !important;
            color: white !important;
        }

        /* Ensure tab content has proper spacing */
        .tab-content {
            border-top: 2px solid #495057;
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="admin-content">
            <!-- Header -->
            <div class="admin-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h4 class="mb-0">Users Management</h4>
                        <small class="text-muted">Manage regular users and admin accounts</small>
                    </div>
                </div>
                <div class="header-right">
                    <div class="realtime-indicator">
                        <span class="realtime-dot"></span>
                        <small>Live Updates</small>
                    </div>
                    <button type="button" class="btn-admin btn-admin-success" onclick="refreshUsers()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>
            
            <!-- Users Content -->
            <div class="admin-content-wrapper">
                <div class="content-container">
                    <!-- Statistics Dashboard -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-users fa-2x text-primary"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="totalUsers"><?php echo number_format($stats['total_users']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Total Users</p>
                                            <small class="text-white-50">Regular users</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-user-check fa-2x text-success"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="activeUsers"><?php echo number_format($stats['active_users']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Active</p>
                                            <small class="text-white-50">Not suspended</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-user-times fa-2x text-danger"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="suspendedUsers"><?php echo number_format($stats['suspended_users']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Suspended</p>
                                            <small class="text-white-50">Blocked users</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-user-shield fa-2x text-warning"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="totalAdmins"><?php echo number_format($stats['total_admins']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Admins</p>
                                            <small class="text-white-50">Admin users</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-user-check fa-2x text-info"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="verifiedUsers"><?php echo number_format($stats['verified_users']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Verified</p>
                                            <small class="text-white-50">Email verified</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-user-clock fa-2x text-secondary"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="unverifiedUsers"><?php echo number_format($stats['unverified_users']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Unverified</p>
                                            <small class="text-white-50">Pending verification</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="userTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active bg-dark text-white border-dark" id="regular-users-tab" data-bs-toggle="tab"
                                            data-bs-target="#regular-users" type="button" role="tab"
                                            style="border-radius: 0.375rem 0 0 0.375rem;">
                                        <i class="fas fa-users me-2"></i>Regular Users
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link bg-dark text-white border-dark" id="admin-users-tab" data-bs-toggle="tab"
                                            data-bs-target="#admin-users" type="button" role="tab"
                                            style="border-radius: 0 0.375rem 0.375rem 0;">
                                        <i class="fas fa-user-shield me-2"></i>Admin Users
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="admin-card-body p-0">
                            <div class="tab-content" id="userTabsContent">
                                <!-- Regular Users Tab -->
                                <div class="tab-pane fade show active" id="regular-users" role="tabpanel">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">
                                                <i class="fas fa-users me-2"></i>Regular Users Management
                                            </h5>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="text-muted" id="regularUsersInfo">
                                                    Loading...
                                                </div>
                                                <div class="spinner-border spinner-border-sm d-none" id="regularUsersSpinner" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>User</th>
                                                        <th>Username</th>
                                                        <th>Email</th>
                                                        <th>Gender</th>
                                                        <th>Phone</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="regularUsersTableBody">
                                                    <!-- Dynamic content will be loaded here -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <!-- Pagination for Regular Users -->
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div class="text-muted" id="regularUsersPaginationInfo">
                                                <!-- Pagination info will be loaded here -->
                                            </div>
                                            <nav aria-label="Regular users pagination">
                                                <ul class="pagination mb-0" id="regularUsersPaginationControls">
                                                    <!-- Pagination controls will be loaded here -->
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>

                                <!-- Admin Users Tab -->
                                <div class="tab-pane fade" id="admin-users" role="tabpanel">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">
                                                <i class="fas fa-user-shield me-2"></i>Admin Users Management
                                            </h5>
                                            <div class="d-flex align-items-center gap-3">
                                                <button type="button" class="btn btn-primary btn-sm" onclick="showAddAdminModal()">
                                                    <i class="fas fa-plus me-1"></i>Add Admin
                                                </button>
                                                <div class="text-muted" id="adminUsersInfo">
                                                    Loading...
                                                </div>
                                                <div class="spinner-border spinner-border-sm d-none" id="adminUsersSpinner" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Admin</th>
                                                        <th>Admin Roles</th>
                                                        <th>Email</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="adminUsersTableBody">
                                                    <!-- Dynamic content will be loaded here -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <!-- Pagination for Admin Users -->
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div class="text-muted" id="adminUsersPaginationInfo">
                                                <!-- Pagination info will be loaded here -->
                                            </div>
                                            <nav aria-label="Admin users pagination">
                                                <ul class="pagination mb-0" id="adminUsersPaginationControls">
                                                    <!-- Pagination controls will be loaded here -->
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" id="editUserId" name="user_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editFirstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editLastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="editLastName" name="last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editUsername" class="form-label">Username</label>
                                <input type="text" class="form-control" id="editUsername" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editGender" class="form-label">Gender</label>
                                <select class="form-select" id="editGender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editPhone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="editPhone" name="phone">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAdminModalLabel">Add New Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addAdminForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="addAdminFirstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="addAdminFirstName" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="addAdminLastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="addAdminLastName" name="last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="addAdminUsername" class="form-label">Username</label>
                                <input type="text" class="form-control" id="addAdminUsername" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label for="addAdminEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="addAdminEmail" name="email" required>
                            </div>
                            <div class="col-12">
                                <label for="addAdminPassword" class="form-label">Password</label>
                                <input type="password" class="form-control" id="addAdminPassword" name="password" required>
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAdminModalLabel">Edit Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editAdminForm">
                    <div class="modal-body">
                        <input type="hidden" id="editAdminId" name="user_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editAdminFirstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="editAdminFirstName" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editAdminLastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="editAdminLastName" name="last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editAdminUsername" class="form-label">Username</label>
                                <input type="text" class="form-control" id="editAdminUsername" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editAdminEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="editAdminEmail" name="email" required>
                            </div>
                            <div class="col-12">
                                <label for="editAdminPassword" class="form-label">Password</label>
                                <input type="password" class="form-control" id="editAdminPassword" name="password">
                                <div class="form-text">Leave blank to keep current password.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Admin JS -->
    <script src="assets/js/admin.js"></script>

    <script>
        // Global variables
        let currentRegularPage = 1;
        let currentAdminPage = 1;
        let currentTab = 'regular';

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            setupEventHandlers();
            loadUsersData('regular', 1);

            // Auto-refresh every 30 seconds
            setInterval(() => {
                refreshUsers();
            }, 30000);
        });

        function setupEventHandlers() {
            // Tab switching with styling updates
            document.getElementById('regular-users-tab').addEventListener('click', function() {
                currentTab = 'regular';
                updateTabStyling('regular-users-tab');
                loadUsersData('regular', currentRegularPage);
            });

            document.getElementById('admin-users-tab').addEventListener('click', function() {
                currentTab = 'admin';
                updateTabStyling('admin-users-tab');
                loadUsersData('admin', currentAdminPage);
            });

            // Form submissions
            document.getElementById('editUserForm').addEventListener('submit', handleEditUser);
            document.getElementById('addAdminForm').addEventListener('submit', handleAddAdmin);
            document.getElementById('editAdminForm').addEventListener('submit', handleEditAdmin);
        }

        function updateTabStyling(activeTabId) {
            // Remove active class from all tabs
            document.querySelectorAll('.nav-tabs .nav-link').forEach(tab => {
                tab.classList.remove('active');
            });

            // Add active class to clicked tab
            document.getElementById(activeTabId).classList.add('active');
        }

        async function loadUsersData(userType, page = 1) {
            const spinner = document.getElementById(userType === 'regular' ? 'regularUsersSpinner' : 'adminUsersSpinner');
            const tableBody = document.getElementById(userType === 'regular' ? 'regularUsersTableBody' : 'adminUsersTableBody');
            const info = document.getElementById(userType === 'regular' ? 'regularUsersInfo' : 'adminUsersInfo');
            const paginationInfo = document.getElementById(userType === 'regular' ? 'regularUsersPaginationInfo' : 'adminUsersPaginationInfo');
            const paginationControls = document.getElementById(userType === 'regular' ? 'regularUsersPaginationControls' : 'adminUsersPaginationControls');

            spinner.classList.remove('d-none');

            try {
                const formData = new FormData();
                formData.append('action', 'get_users_data');
                formData.append('user_type', userType);
                formData.append('page', page);

                const response = await fetch('users.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const data = result.data;

                    // Update info
                    info.textContent = `${data.totalUsers} total ${userType} users`;

                    // Update pagination info
                    const start = ((data.currentPage - 1) * 20) + 1;
                    const end = Math.min(data.currentPage * 20, data.totalUsers);
                    paginationInfo.textContent = `Showing ${start}-${end} of ${data.totalUsers} users`;

                    // Render table
                    renderUsersTable(data.users, userType, tableBody);

                    // Render pagination
                    renderPagination(data.currentPage, data.totalPages, userType, paginationControls);

                    // Update current page
                    if (userType === 'regular') {
                        currentRegularPage = data.currentPage;
                    } else {
                        currentAdminPage = data.currentPage;
                    }
                } else {
                    showAlert('error', result.message || 'Failed to load users data');
                }
            } catch (error) {
                console.error('Error loading users data:', error);
                showAlert('error', 'Failed to load users data');
            } finally {
                spinner.classList.add('d-none');
            }
        }

        function renderUsersTable(users, userType, tableBody) {
            if (users.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="${userType === 'regular' ? '7' : '5'}" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-2x mb-2"></i><br>
                            No ${userType} users found
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = users.map(user => {
                if (userType === 'regular') {
                    return renderRegularUserRow(user);
                } else {
                    return renderAdminUserRow(user);
                }
            }).join('');
        }

        function renderRegularUserRow(user) {
            const statusBadge = user.is_suspended == 1
                ? '<span class="badge bg-danger">Suspended</span>'
                : '<span class="badge bg-success">Active</span>';

            const verifiedBadge = user.is_verified == 1
                ? '<i class="fas fa-check-circle text-success" title="Verified"></i>'
                : '<i class="fas fa-clock text-warning" title="Unverified"></i>';

            return `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-2">
                                <i class="fas fa-user-circle fa-2x text-muted"></i>
                            </div>
                            <div>
                                <div class="fw-medium">${escapeHtml(user.first_name)} ${escapeHtml(user.last_name)}</div>
                                <small class="text-muted">ID: ${user.id}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${escapeHtml(user.username)}
                            <span class="ms-2">${verifiedBadge}</span>
                        </div>
                    </td>
                    <td>${escapeHtml(user.email || 'N/A')}</td>
                    <td>
                        ${user.gender ? `<span class="badge bg-light text-dark">${user.gender.charAt(0).toUpperCase() + user.gender.slice(1)}</span>` : '<span class="text-muted">N/A</span>'}
                    </td>
                    <td>${escapeHtml(user.phone || 'N/A')}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="editUser(${user.id})" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${user.is_suspended == 1
                                ? `<button type="button" class="btn btn-outline-success" onclick="unsuspendUser(${user.id})" title="Unsuspend User">
                                     <i class="fas fa-user-check"></i>
                                   </button>`
                                : `<button type="button" class="btn btn-outline-danger" onclick="suspendUser(${user.id})" title="Suspend User">
                                     <i class="fas fa-user-times"></i>
                                   </button>`
                            }
                        </div>
                    </td>
                </tr>
            `;
        }

        function renderAdminUserRow(user) {
            const currentAdminId = <?php echo $currentUser['id']; ?>;
            const isCurrentAdmin = user.id == currentAdminId;

            return `
                <tr ${isCurrentAdmin ? 'class="table-warning"' : ''}>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-2">
                                <i class="fas fa-user-shield fa-2x text-warning"></i>
                            </div>
                            <div>
                                <div class="fw-medium">
                                    ${escapeHtml(user.first_name)} ${escapeHtml(user.last_name)}
                                    ${isCurrentAdmin ? '<span class="badge bg-primary ms-2">You</span>' : ''}
                                </div>
                                <small class="text-muted">ID: ${user.id}</small>
                            </div>
                        </div>
                    </td>
                    <td>${escapeHtml(user.username)}</td>
                    <td>${escapeHtml(user.email || 'N/A')}</td>
                    <td>
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-user-shield me-1"></i>Admin
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="editAdmin(${user.id})" title="Edit Admin">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${!isCurrentAdmin
                                ? `<button type="button" class="btn btn-outline-danger" onclick="deleteAdmin(${user.id})" title="Delete Admin">
                                     <i class="fas fa-trash"></i>
                                   </button>`
                                : '<button type="button" class="btn btn-outline-secondary" disabled title="Cannot delete yourself"><i class="fas fa-lock"></i></button>'
                            }
                        </div>
                    </td>
                </tr>
            `;
        }

        function renderPagination(currentPage, totalPages, userType, container) {
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let pagination = '';

            // Previous button
            pagination += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadUsersData('${userType}', ${currentPage - 1}); return false;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;

            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                pagination += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="loadUsersData('${userType}', 1); return false;">1</a>
                    </li>
                `;
                if (startPage > 2) {
                    pagination += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                pagination += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadUsersData('${userType}', ${i}); return false;">${i}</a>
                    </li>
                `;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    pagination += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                pagination += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="loadUsersData('${userType}', ${totalPages}); return false;">${totalPages}</a>
                    </li>
                `;
            }

            // Next button
            pagination += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadUsersData('${userType}', ${currentPage + 1}); return false;">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;

            container.innerHTML = pagination;
        }

        // User Actions
        async function suspendUser(userId) {
            if (!confirm('Are you sure you want to suspend this user? They will be logged out immediately.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'suspend_user');
                formData.append('user_id', userId);

                const response = await fetch('users.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    refreshUsers();
                    refreshStats();
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error suspending user:', error);
                showAlert('error', 'Failed to suspend user');
            }
        }

        async function unsuspendUser(userId) {
            if (!confirm('Are you sure you want to unsuspend this user?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'unsuspend_user');
                formData.append('user_id', userId);

                const response = await fetch('users.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    refreshUsers();
                    refreshStats();
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error unsuspending user:', error);
                showAlert('error', 'Failed to unsuspend user');
            }
        }

        // Edit User Functions
        async function editUser(userId) {
            try {
                // Get user data from the table row
                const row = document.querySelector(`tr:has(button[onclick="editUser(${userId})"])`);
                if (!row) return;

                const cells = row.querySelectorAll('td');
                const nameText = cells[0].querySelector('.fw-medium').textContent.trim();
                const [firstName, ...lastNameParts] = nameText.split(' ');
                const lastName = lastNameParts.join(' ');
                const username = cells[1].textContent.trim().split('\n')[0];
                const email = cells[2].textContent.trim();
                const genderBadge = cells[3].querySelector('.badge');
                const gender = genderBadge ? genderBadge.textContent.toLowerCase() : '';
                const phone = cells[4].textContent.trim();

                // Populate modal
                document.getElementById('editUserId').value = userId;
                document.getElementById('editFirstName').value = firstName;
                document.getElementById('editLastName').value = lastName;
                document.getElementById('editUsername').value = username;
                document.getElementById('editEmail').value = email === 'N/A' ? '' : email;
                document.getElementById('editGender').value = gender === 'n/a' ? '' : gender;
                document.getElementById('editPhone').value = phone === 'N/A' ? '' : phone;

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
            } catch (error) {
                console.error('Error preparing edit user:', error);
                showAlert('error', 'Failed to load user data');
            }
        }

        async function handleEditUser(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            formData.append('action', 'update_user');

            try {
                const response = await fetch('users.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                    refreshUsers();
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error updating user:', error);
                showAlert('error', 'Failed to update user');
            }
        }

        // Admin Management Functions
        function showAddAdminModal() {
            document.getElementById('addAdminForm').reset();
            const modal = new bootstrap.Modal(document.getElementById('addAdminModal'));
            modal.show();
        }

        async function handleAddAdmin(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            formData.append('action', 'add_admin');

            // Validate password
            const password = formData.get('password');
            if (password.length < 6) {
                showAlert('error', 'Password must be at least 6 characters long');
                return;
            }

            try {
                const response = await fetch('users.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    bootstrap.Modal.getInstance(document.getElementById('addAdminModal')).hide();
                    refreshUsers();
                    refreshStats();
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error adding admin:', error);
                showAlert('error', 'Failed to add admin');
            }
        }

        async function editAdmin(userId) {
            try {
                // Get admin data from the table row
                const row = document.querySelector(`tr:has(button[onclick="editAdmin(${userId})"])`);
                if (!row) return;

                const cells = row.querySelectorAll('td');
                const nameText = cells[0].querySelector('.fw-medium').textContent.trim().replace(/\s+You$/, '');
                const [firstName, ...lastNameParts] = nameText.split(' ');
                const lastName = lastNameParts.join(' ');
                const username = cells[1].textContent.trim();
                const email = cells[2].textContent.trim();

                // Populate modal
                document.getElementById('editAdminId').value = userId;
                document.getElementById('editAdminFirstName').value = firstName;
                document.getElementById('editAdminLastName').value = lastName;
                document.getElementById('editAdminUsername').value = username;
                document.getElementById('editAdminEmail').value = email === 'N/A' ? '' : email;
                document.getElementById('editAdminPassword').value = '';

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('editAdminModal'));
                modal.show();
            } catch (error) {
                console.error('Error preparing edit admin:', error);
                showAlert('error', 'Failed to load admin data');
            }
        }

        async function handleEditAdmin(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            formData.append('action', 'update_admin');

            // Validate password if provided
            const password = formData.get('password');
            if (password && password.length < 6) {
                showAlert('error', 'Password must be at least 6 characters long');
                return;
            }

            try {
                const response = await fetch('users.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    bootstrap.Modal.getInstance(document.getElementById('editAdminModal')).hide();
                    refreshUsers();
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error updating admin:', error);
                showAlert('error', 'Failed to update admin');
            }
        }

        async function deleteAdmin(userId) {
            if (!confirm('Are you sure you want to delete this admin? This action cannot be undone.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete_admin');
                formData.append('user_id', userId);

                const response = await fetch('users.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    refreshUsers();
                    refreshStats();
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error deleting admin:', error);
                showAlert('error', 'Failed to delete admin');
            }
        }

        // Utility Functions
        async function refreshUsers() {
            await loadUsersData(currentTab, currentTab === 'regular' ? currentRegularPage : currentAdminPage);
        }

        async function refreshStats() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_user_stats');

                const response = await fetch('users.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const stats = result.stats;
                    document.getElementById('totalUsers').textContent = numberFormat(stats.total_users);
                    document.getElementById('activeUsers').textContent = numberFormat(stats.active_users);
                    document.getElementById('suspendedUsers').textContent = numberFormat(stats.suspended_users);
                    document.getElementById('totalAdmins').textContent = numberFormat(stats.total_admins);
                    document.getElementById('verifiedUsers').textContent = numberFormat(stats.verified_users);
                    document.getElementById('unverifiedUsers').textContent = numberFormat(stats.unverified_users);
                }
            } catch (error) {
                console.error('Error refreshing stats:', error);
            }
        }

        function showAlert(type, message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert-floating');
            existingAlerts.forEach(alert => alert.remove());

            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show alert-floating`;
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;

            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alertDiv);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function numberFormat(num) {
            return new Intl.NumberFormat().format(num);
        }

        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && overlay) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            }
        }
    </script>
</body>
</html>
