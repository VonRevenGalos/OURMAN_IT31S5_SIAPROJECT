<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = getUserId();
$order_id = (int)($_POST['order_id'] ?? 0);
$otp_code = $_POST['otp_code'] ?? '';

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

if (empty($otp_code) || strlen($otp_code) !== 6) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit verification code']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify OTP
    $stmt = $pdo->prepare("
        SELECT id, expires_at, verified 
        FROM payment_otps 
        WHERE user_id = ? AND order_id = ? AND otp_code = ?
    ");
    $stmt->execute([$user_id, $order_id, $otp_code]);
    $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otp_record) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
        exit();
    }
    
    if ($otp_record['verified']) {
        echo json_encode(['success' => false, 'message' => 'This verification code has already been used']);
        exit();
    }
    
    if (strtotime($otp_record['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one']);
        exit();
    }
    
    // Mark OTP as verified
    $stmt = $pdo->prepare("UPDATE payment_otps SET verified = 1 WHERE id = ?");
    $stmt->execute([$otp_record['id']]);
    
    // Update order status to Processing and deduct stock
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Processing', updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    
    // Get order items to deduct stock
    $stmt = $pdo->prepare("
        SELECT oi.product_id, oi.quantity 
        FROM order_items oi
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Deduct stock for each item
    foreach ($order_items as $item) {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET stock = GREATEST(0, stock - ?) 
            WHERE id = ?
        ");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Log successful payment verification
    error_log("Payment verified successfully: Order ID {$order_id}, User ID {$user_id}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully! Your order is now being processed.'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction
    $pdo->rollBack();
    
    error_log("Verify payment OTP error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollBack();
    
    error_log("Verify payment OTP error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while verifying payment']);
}
?>
