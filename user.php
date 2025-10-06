<?php
require_once 'includes/session.php';
require_once 'db.php';

// Require login
requireLogin();

$user = getCurrentUser();
$user_id = getUserId();
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_success']);

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'status-pending';
        case 'shipped':
            return 'status-shipped';
        case 'delivered':
            return 'status-delivered';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return 'status-pending';
    }
}

// Helper function to format order date
function formatOrderDate($date) {
    return date('F j, Y', strtotime($date));
}

// Get user stats
try {
    // Get cart count
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Get favorites count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $favorites_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Get orders count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $orders_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Get notifications count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $notifications_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Get recent orders (limit to 3 most recent)
    $stmt = $pdo->prepare("
        SELECT o.*, ua.full_name, ua.address_line1, ua.city, ua.state
        FROM orders o
        LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$user_id]); 
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("User stats error: " . $e->getMessage());
    $cart_count = $favorites_count = $orders_count = $notifications_count = 0;
    $recent_orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - ShoeStore</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/auth.css">

    <style>
        /* Order status badges */
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-processing {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #99d6ff;
        }

        .status-shipped {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #a3d9a5;
        }

        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #7fdbda;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f1aeb5;
        }

        /* Order item styling */
        .order-item {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .order-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .recent-orders {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="user-container">
        <div class="container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Welcome Section -->
            <div class="welcome-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                        <p class="welcome-subtitle">Manage your account, track orders, and discover new products.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex justify-content-end">
                            <a href="logout.php" class="logout-btn" id="logoutBtn">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Section -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: #e3f2fd; color: #1976d2;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stats-number" id="cart-stats"><?php echo (int)$cart_count; ?></div>
                        <div class="stats-label">Items in Cart</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: #fce4ec; color: #c2185b;">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stats-number" id="favorites-stats"><?php echo (int)$favorites_count; ?></div>
                        <div class="stats-label">Favorites</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: #e8f5e8; color: #388e3c;">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stats-number" id="orders-stats"><?php echo (int)$orders_count; ?></div>
                            <div class="stats-label">Total Orders</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: #fff3e0; color: #f57c00;">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="stats-number" id="notifications-stats"><?php echo (int)$notifications_count; ?></div>
                        <div class="stats-label">Notifications</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Quick Actions -->
                <div class="col-lg-4 mb-4">
                    <div class="quick-actions">
                        <h3 class="mb-4">Quick Actions</h3>
                        <div class="row g-3">
                            <div class="col-6">
                                <a href="cart.php" class="action-btn">
                                    <i class="fas fa-shopping-cart action-icon"></i>
                                    <div>My Cart</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="favorites.php" class="action-btn">
                                    <i class="fas fa-heart action-icon"></i>
                                    <div>Favorites</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="myorders.php" class="action-btn">
                                    <i class="fas fa-box action-icon"></i>
                                    <div>My Orders</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="notification.php" class="action-btn">
                                    <i class="fas fa-bell action-icon"></i>
                                    <div>Notifications</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="profile.php" class="action-btn">
                                    <i class="fas fa-user action-icon"></i>
                                    <div>Edit Profile</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="col-lg-8">
                    <div class="recent-orders">
                        <h3 class="mb-4">Recent Orders</h3>
                        
                        <!-- Dynamic Order Items -->
                        <?php if (!empty($recent_orders)): ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="mb-1">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h6>
                                            <small class="text-muted">Placed on <?php echo formatOrderDate($order['created_at']); ?></small>
                                            <?php if (!empty($order['voucher_code']) && $order['discount_amount'] > 0): ?>
                                                <br><small class="text-success">
                                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($order['voucher_code']); ?>
                                                    (-₱<?php echo number_format($order['discount_amount'], 2); ?>)
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="order-status <?php echo getStatusBadgeClass($order['status']); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <strong>₱<?php echo number_format($order['total_price'], 2); ?></strong>
                                            <br><small class="text-muted"><?php echo ucfirst($order['payment_method']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No orders yet</h5>
                                <p class="text-muted">Start shopping to see your orders here!</p>
                                <a href="men.php" class="btn btn-primary">Start Shopping</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($recent_orders)): ?>
                            <div class="text-center mt-4">
                                <a href="myorders.php" class="btn btn-outline-primary">View All Orders</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>
    <script src="assets/js/global-notifications.js"></script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/user-stats.js"></script>
</body>
</html>
