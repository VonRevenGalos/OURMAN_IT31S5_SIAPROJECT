<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to place an order']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$action = $_POST['action'];
if (!in_array($action, ['place_order', 'prepare_order'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

$user_id = getUserId();
$shipping_address_id = (int)($_POST['shipping_address_id'] ?? 0);
$payment_method = $_POST['payment_method'] ?? '';
$selected_cart_ids = $_POST['selected_cart_ids'] ?? null;
$voucher_code = $_POST['voucher_code'] ?? null;
$discount_amount = floatval($_POST['discount_amount'] ?? 0);

// Validate inputs
if ($shipping_address_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a shipping address']);
    exit();
}

if (!in_array($payment_method, ['cod', 'bank_transfer', 'card', 'gcash'])) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid payment method']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify shipping address belongs to user
    $stmt = $pdo->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$shipping_address_id, $user_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid shipping address');
    }
    
    // Get cart items with stock validation (handle selected items)
    if ($selected_cart_ids && !empty($selected_cart_ids)) {
        $cart_ids = explode(',', $selected_cart_ids);
        $cart_ids = array_map('intval', $cart_ids);
        $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';

        $stmt = $pdo->prepare("
            SELECT c.cart_id, c.product_id, c.quantity, c.size,
                   p.title, p.price, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ? AND c.cart_id IN ($placeholders)
        ");
        $params = array_merge([$user_id], $cart_ids);
        $stmt->execute($params);
    } else {
        // Get all cart items
        $stmt = $pdo->prepare("
            SELECT c.cart_id, c.product_id, c.quantity, c.size,
                   p.title, p.price, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
    }
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cart_items)) {
        throw new Exception('Your cart is empty');
    }
    
    // Validate stock and sizes
    $total_price = 0;
    $items_without_size = [];
    
    foreach ($cart_items as $item) {
        // Check stock
        if ($item['quantity'] > $item['stock']) {
            throw new Exception("Not enough stock for {$item['title']}. Available: {$item['stock']}, Requested: {$item['quantity']}");
        }
        
        // Check size selection
        if (empty($item['size'])) {
            $items_without_size[] = $item['title'];
        }
        
        $total_price += $item['price'] * $item['quantity'];
    }
    
    if (!empty($items_without_size)) {
        throw new Exception('Please select sizes for all items: ' . implode(', ', $items_without_size));
    }
    
    // Validate and apply voucher if provided
    $validated_discount = 0;
    if ($voucher_code && $discount_amount > 0) {
        // Re-validate voucher to ensure it's still valid
        $stmt = $pdo->prepare("
            SELECT id, code, discount, valid_until
            FROM vouchers
            WHERE code = ? AND valid_until >= CURDATE()
        ");
        $stmt->execute([$voucher_code]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($voucher) {
            // Recalculate discount to ensure accuracy
            $calculated_discount = ($total_price * $voucher['discount']) / 100;
            $calculated_discount = round($calculated_discount, 2);

            // Use the smaller of the two (for security)
            $validated_discount = min($discount_amount, $calculated_discount);
            $total_price = $total_price - $validated_discount;
        } else {
            // Voucher is no longer valid, proceed without discount
            $voucher_code = null;
            $validated_discount = 0;
        }
    }

    // Add shipping and tax (tax calculated on discounted amount)
    $shipping_fee = 150;
    $tax_rate = 0.12;
    $tax = $total_price * $tax_rate;
    $final_total = $total_price + $shipping_fee + $tax;
    
    // Create order
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, status, total_price, payment_method, shipping_address_id, voucher_code, discount_amount, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    // Set order status based on action and payment method
    if ($action === 'place_order' && $payment_method === 'cod') {
        $order_status = 'Pending';
    } else {
        $order_status = 'pending_payment';
    }

    $stmt->execute([$user_id, $order_status, $final_total, $payment_method, $shipping_address_id, $voucher_code, $validated_discount]);
    $order_id = $pdo->lastInsertId();

    // Create order items
    foreach ($cart_items as $item) {
        // Insert order item
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price, size)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price'], $item['size']]);
    }

    // For COD orders or place_order action, update stock and clear cart immediately
    if ($action === 'place_order' && $payment_method === 'cod') {
        // Update product stock
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }

        // Clear cart (only selected items or all if none selected)
        if ($selected_cart_ids && !empty($selected_cart_ids)) {
            $cart_ids = explode(',', $selected_cart_ids);
            $cart_ids = array_map('intval', $cart_ids);
            $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND cart_id IN ($placeholders)");
            $params = array_merge([$user_id], $cart_ids);
            $stmt->execute($params);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
    }
    
    // Commit transaction
    $pdo->commit();

    // Store selected cart IDs in session for payment processing (only for prepare_order)
    if ($action === 'prepare_order' && $selected_cart_ids && !empty($selected_cart_ids)) {
        $_SESSION['payment_selected_cart_ids'] = $selected_cart_ids;
    }

    // Log successful order
    error_log("Order created successfully: Order ID {$order_id}, User ID {$user_id}, Payment Method: {$payment_method}, Action: {$action}");

    $message = ($action === 'place_order') ? 'Order placed successfully' : 'Order prepared for payment';

    echo json_encode([
        'success' => true,
        'message' => $message,
        'order_id' => $order_id,
        'payment_method' => $payment_method
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollBack();
    
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
