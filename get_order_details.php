<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to view order details']);
    exit();
}

$user_id = getUserId();
$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, ua.full_name, ua.address_line1, ua.address_line2, 
               ua.city, ua.state, ua.postal_code, ua.phone
        FROM orders o
        LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.title, p.image, p.brand, p.color
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate breakdown
    $subtotal = 0;
    foreach ($order_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    // Get voucher information
    $voucher_discount = $order['discount_amount'] ?? 0;
    $voucher_code = $order['voucher_code'] ?? null;

    // Calculate after discount
    $discounted_subtotal = $subtotal - $voucher_discount;
    $shipping_fee = 150;
    $tax = $discounted_subtotal * 0.12;
    
    // Generate HTML
    ob_start();
    ?>
    
    <div class="row">
        <div class="col-md-6">
            <h6><i class="fas fa-receipt me-2"></i>Order Information</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Order ID:</strong></td>
                    <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        <span class="badge bg-<?php 
                            switch($order['status']) {
                                case 'Pending': echo 'warning'; break;
                                case 'Processing': echo 'info'; break;
                                case 'Shipped': echo 'primary'; break;
                                case 'Delivered': echo 'success'; break;
                                case 'Cancelled': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                        ?>"><?php echo $order['status']; ?></span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Payment:</strong></td>
                    <td>
                        <?php 
                        switch($order['payment_method']) {
                            case 'cod': echo 'Cash on Delivery'; break;
                            case 'bank_transfer': echo 'Bank Transfer'; break;
                            case 'card': echo 'Credit/Debit Card'; break;
                            case 'gcash': echo 'GCash'; break;
                            default: echo ucfirst($order['payment_method']);
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="col-md-6">
            <h6><i class="fas fa-map-marker-alt me-2"></i>Shipping Address</h6>
            <address>
                <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                <?php echo htmlspecialchars($order['address_line1']); ?><br>
                <?php if ($order['address_line2']): ?>
                    <?php echo htmlspecialchars($order['address_line2']); ?><br>
                <?php endif; ?>
                <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> <?php echo htmlspecialchars($order['postal_code']); ?><br>
                <?php if ($order['phone']): ?>
                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($order['phone']); ?>
                <?php endif; ?>
            </address>
        </div>
    </div>
    
    <hr>
    
    <h6><i class="fas fa-box me-2"></i>Order Items</h6>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Size</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($item['brand']); ?> • <?php echo htmlspecialchars($item['color']); ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($item['size']); ?></span></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                    <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <hr>
    
    <div class="row">
        <div class="col-md-6 offset-md-6">
            <table class="table table-sm">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-end">₱<?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php if ($voucher_code && $voucher_discount > 0): ?>
                <tr>
                    <td>Discount (<?php echo htmlspecialchars($voucher_code); ?>):</td>
                    <td class="text-end text-success">-₱<?php echo number_format($voucher_discount, 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Shipping Fee:</td>
                    <td class="text-end">₱<?php echo number_format($shipping_fee, 2); ?></td>
                </tr>
                <tr>
                    <td>Tax (12% VAT):</td>
                    <td class="text-end">₱<?php echo number_format($tax, 2); ?></td>
                </tr>
                <tr class="table-primary">
                    <td><strong>Total:</strong></td>
                    <td class="text-end"><strong>₱<?php echo number_format($order['total_price'], 2); ?></strong></td>
                </tr>
            </table>
        </div>
    </div>
    
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
