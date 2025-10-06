<?php
// Set JSON header and error reporting
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output

require_once 'includes/session.php';
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = getUserId();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'cancel_order':
        $order_id = $_POST['order_id'] ?? '';
        
        if (!$order_id) {
            echo json_encode(['success' => false, 'message' => 'Missing order ID']);
            exit();
        }
        
        try {
            $pdo->beginTransaction();
            
            // Verify order belongs to user and is in Pending status
            $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $user_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit();
            }
            
            if (strtolower($order['status']) !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Only pending orders can be cancelled']);
                exit();
            }
            
            // Get order items to restore stock
            $stmt = $pdo->prepare("
                SELECT oi.product_id, oi.quantity 
                FROM order_items oi 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Restore product stock
            foreach ($order_items as $item) {
                $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Update order status to 'Cancelled' instead of deleting
            $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $user_id]);

            // Create notification for the user about order cancellation
            try {
                $notification_message = "Order #{$order_id} has been cancelled successfully. Your refund will be processed if applicable.";
                $notification_stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, message, is_read, created_at)
                    VALUES (?, 'order_update', ?, 0, NOW())
                ");
                $notification_stmt->execute([$user_id, $notification_message]);
            } catch (Exception $e) {
                error_log("Error creating cancellation notification: " . $e->getMessage());
                // Don't fail the cancellation if notification fails
            }

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Order cancellation error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
