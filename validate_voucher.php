<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to apply vouchers']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$voucher_code = trim($_POST['voucher_code'] ?? '');
$subtotal = floatval($_POST['subtotal'] ?? 0);

// Validate inputs
if (empty($voucher_code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a voucher code']);
    exit();
}

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid subtotal amount']);
    exit();
}

try {
    // Check if voucher exists and is valid
    $stmt = $pdo->prepare("
        SELECT id, code, discount, valid_until 
        FROM vouchers 
        WHERE code = ? AND valid_until >= CURDATE()
    ");
    $stmt->execute([$voucher_code]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired voucher code']);
        exit();
    }
    
    // Calculate discount amount (percentage-based)
    $discount_percentage = $voucher['discount'];
    $discount_amount = ($subtotal * $discount_percentage) / 100;
    
    // Round to 2 decimal places
    $discount_amount = round($discount_amount, 2);
    
    // Calculate new totals (using checkout logic for consistency)
    $new_subtotal = $subtotal - $discount_amount;
    $shipping_fee = 150; // Fixed shipping fee to match checkout
    $tax = $new_subtotal * 0.12; // 12% VAT to match checkout
    $new_total = $new_subtotal + $shipping_fee + $tax;
    
    echo json_encode([
        'success' => true,
        'message' => 'Voucher applied successfully!',
        'voucher' => [
            'code' => $voucher['code'],
            'discount_percentage' => $discount_percentage,
            'discount_amount' => $discount_amount,
            'valid_until' => $voucher['valid_until']
        ],
        'totals' => [
            'original_subtotal' => $subtotal,
            'discount_amount' => $discount_amount,
            'new_subtotal' => $new_subtotal,
            'shipping_fee' => $shipping_fee,
            'tax' => $tax,
            'total' => $new_total
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Voucher validation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while validating the voucher']);
}
?>
