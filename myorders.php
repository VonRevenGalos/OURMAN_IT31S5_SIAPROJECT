<?php
require_once 'includes/session.php';
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = getUserId();

// Get user orders
try {
    $stmt = $pdo->prepare("
        SELECT o.*, ua.full_name, ua.address_line1, ua.city, ua.state
        FROM orders o
        LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ShoeARizz</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #000000;
        }

        .orders-container {
            background-color: #ffffff;
            min-height: 100vh;
            padding: 2rem 0;
        }

        .orders-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }

        .orders-header {
            background: #ffffff;
            color: #000000;
            padding: 2rem;
            text-align: center;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .order-item {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .order-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #000000;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-shipped {
            background: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .payment-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.7rem;
        }
        
        .payment-cod {
            background: #fff3cd;
            color: #856404;
        }
        
        .payment-bank {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .payment-card {
            background: #d4edda;
            color: #155724;
        }
        
        .payment-gcash {
            background: #ffeaa7;
            color: #6c5ce7;
        }
        
        .btn-view-details {
            background: #000000;
            border: 2px solid #000000;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-view-details:hover {
            background: white;
            color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-cancel-order {
            background: #dc3545;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-left: 0.5rem;
        }

        .btn-cancel-order:hover {
            background: white;
            color: #dc3545;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220,53,69,0.2);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="orders-container">
        <div class="container">
            <div class="orders-card">
                <div class="orders-header">
                    <h1><i class="fas fa-shopping-bag me-2"></i>My Orders</h1>
                    <p class="mb-0">Track and manage your orders</p>
                </div>
                
                <div class="p-4">
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h3>No Orders Yet</h3>
                            <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <div class="order-item" data-order-id="<?php echo $order['id']; ?>">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <h5 class="mb-1">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
                                
                                <div class="col-md-2">
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </div>
                                
                                <div class="col-md-2">
                                    <span class="payment-badge payment-<?php echo str_replace('_', '', $order['payment_method']); ?>">
                                        <?php 
                                        switch($order['payment_method']) {
                                            case 'cod': echo 'COD'; break;
                                            case 'bank_transfer': echo 'Bank'; break;
                                            case 'card': echo 'Card'; break;
                                            case 'gcash': echo 'GCash'; break;
                                            default: echo ucfirst($order['payment_method']);
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="col-md-2">
                                    <p class="mb-0 fw-bold text-success">₱<?php echo number_format($order['total_price'], 2); ?></p>
                                    <?php if (!empty($order['voucher_code']) && $order['discount_amount'] > 0): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($order['voucher_code']); ?>
                                            (-₱<?php echo number_format($order['discount_amount'], 2); ?>)
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-3 text-end">
                                    <button class="btn btn-view-details" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                    <?php if (strtolower($order['status']) === 'pending'): ?>
                                    <button class="btn btn-cancel-order" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($order['full_name']): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Shipping to: <?php echo htmlspecialchars($order['full_name']); ?>, 
                                        <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Loading order details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Global Cart and Favorites JS -->
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>
    <script src="assets/js/global-notifications.js"></script>
    
    <script>
        // Initialize cart and favorites counts on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
            if (typeof updateFavoritesCount === 'function') {
                updateFavoritesCount();
            }
        });

        function viewOrderDetails(orderId) {
            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            const content = document.getElementById('orderDetailsContent');
            
            // Show loading state
            content.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading order details...</p>
                </div>
            `;
            
            modal.show();
            
            // Fetch order details
            fetch(`get_order_details.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        content.innerHTML = data.html;
                    } else {
                        content.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${data.message || 'Failed to load order details'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            An error occurred while loading order details
                        </div>
                    `;
                });
        }

        function cancelOrder(orderId) {
            if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'cancel_order');
            formData.append('order_id', orderId);

            fetch('process_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the order status to 'Cancelled' instead of removing
                    const orderElement = document.querySelector(`[data-order-id="${orderId}"]`);
                    if (orderElement) {
                        // Update status badge
                        const statusBadge = orderElement.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.className = 'status-badge status-cancelled';
                            statusBadge.textContent = 'CANCELLED';
                        }

                        // Remove the cancel button since order is now cancelled
                        const cancelButton = orderElement.querySelector('.btn-cancel-order');
                        if (cancelButton) {
                            cancelButton.remove();
                        }

                        // Add visual feedback with animation
                        orderElement.style.transition = 'all 0.3s ease';
                        orderElement.style.transform = 'scale(0.98)';
                        orderElement.style.opacity = '0.8';

                        setTimeout(() => {
                            orderElement.style.transform = 'scale(1)';
                            orderElement.style.opacity = '1';
                        }, 300);
                    }

                    // Update notification count in navbar if function exists
                    if (typeof updateNotificationCountInNavbar === 'function') {
                        updateNotificationCountInNavbar();
                    }

                    alert('Order cancelled successfully. The order status has been updated to Cancelled. Check your notifications for details.');
                } else {
                    alert(data.message || 'Failed to cancel order');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cancelling the order');
            });
        }
    </script>
</body>
</html>
