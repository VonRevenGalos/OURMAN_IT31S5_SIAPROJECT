<?php
/**
 * Orders Data API
 * Handles order data retrieval, status updates, and bulk operations
 */

// Set JSON header and error reporting
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include admin authentication
require_once __DIR__ . '/../includes/admin_auth.php';

// Require admin login for all operations
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Database connection
require_once __DIR__ . '/../../db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Get action from different sources based on method
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
}

// Debug logging
error_log("Orders API - Method: $method, Action: $action");

try {
    switch ($action) {
        case 'test':
            echo json_encode([
                'success' => true,
                'message' => 'API is working',
                'method' => $method,
                'action' => $action,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
        case 'update_status':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $orderId = $input['order_id'] ?? '';
            $status = $input['status'] ?? '';

            error_log("Update Status - Order ID: $orderId, Status: $status");

            if (!$orderId || !$status) {
                throw new Exception('Missing required parameters: orderId=' . $orderId . ', status=' . $status);
            }

            // Validate status
            $validStatuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status: ' . $status);
            }

            // Get order details before update for notification
            $order_stmt = $pdo->prepare("SELECT user_id, status FROM orders WHERE id = ?");
            $order_stmt->execute([$orderId]);
            $order_data = $order_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order_data) {
                throw new Exception('Order not found');
            }

            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$status, $orderId]);

            if ($result && $stmt->rowCount() > 0) {
                // Create notification for status change
                try {
                    $status_messages = [
                        'Pending' => 'Your order is being processed and will be shipped soon.',
                        'Shipped' => 'Great news! Your order has been shipped and is on its way to you.',
                        'Delivered' => 'Your order has been delivered successfully. Thank you for shopping with us!',
                        'Cancelled' => 'Your order has been cancelled. If you have any questions, please contact support.'
                    ];

                    $notification_message = "Order #{$orderId} status updated to {$status}. " . ($status_messages[$status] ?? '');

                    $notification_stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, message, is_read, created_at)
                        VALUES (?, 'order_update', ?, 0, NOW())
                    ");
                    $notification_stmt->execute([$order_data['user_id'], $notification_message]);

                    error_log("Notification created for order #{$orderId} status change to {$status}");
                } catch (Exception $e) {
                    error_log("Error creating notification: " . $e->getMessage());
                    // Don't fail the status update if notification fails
                }

                echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
            } else {
                throw new Exception('Failed to update order status - no rows affected');
            }
            break;
            
        case 'bulk_update_status':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $orderIds = $input['order_ids'] ?? [];
            $status = $input['status'] ?? '';

            error_log("Bulk Update - Order IDs: " . json_encode($orderIds) . ", Status: $status");

            if (empty($orderIds) || !$status) {
                throw new Exception('Missing required parameters: orderIds=' . json_encode($orderIds) . ', status=' . $status);
            }

            // Validate status
            $validStatuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status: ' . $status);
            }

            // Validate order IDs are numeric
            foreach ($orderIds as $orderId) {
                if (!is_numeric($orderId)) {
                    throw new Exception('Invalid order ID: ' . $orderId);
                }
            }

            // Get order details before update for notifications
            $placeholders_select = str_repeat('?,', count($orderIds) - 1) . '?';
            $orders_stmt = $pdo->prepare("SELECT id, user_id FROM orders WHERE id IN ($placeholders_select)");
            $orders_stmt->execute($orderIds);
            $orders_data = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

            $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
            $params = array_merge([$status], $orderIds);

            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)");
            $result = $stmt->execute($params);

            if ($result) {
                $affectedRows = $stmt->rowCount();

                // Create notifications for each updated order
                try {
                    $status_messages = [
                        'Pending' => 'Your order is being processed and will be shipped soon.',
                        'Shipped' => 'Great news! Your order has been shipped and is on its way to you.',
                        'Delivered' => 'Your order has been delivered successfully. Thank you for shopping with us!',
                        'Cancelled' => 'Your order has been cancelled. If you have any questions, please contact support.'
                    ];

                    $notification_stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, message, is_read, created_at)
                        VALUES (?, 'order_update', ?, 0, NOW())
                    ");

                    foreach ($orders_data as $order) {
                        $notification_message = "Order #{$order['id']} status updated to {$status}. " . ($status_messages[$status] ?? '');
                        $notification_stmt->execute([$order['user_id'], $notification_message]);
                    }

                    error_log("Notifications created for bulk update of " . count($orders_data) . " orders");
                } catch (Exception $e) {
                    error_log("Error creating bulk notifications: " . $e->getMessage());
                    // Don't fail the status update if notifications fail
                }

                error_log("Bulk update successful - affected rows: $affectedRows");
                echo json_encode([
                    'success' => true,
                    'message' => "Successfully updated $affectedRows orders",
                    'affected_rows' => $affectedRows
                ]);
            } else {
                throw new Exception('Failed to update orders - no rows affected');
            }
            break;
            
        case 'get_order_details':
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $orderId = $_GET['order_id'] ?? '';
            if (!$orderId) {
                throw new Exception('Missing order ID');
            }
            
            // Get order details
            $stmt = $pdo->prepare("
                SELECT o.*, u.first_name, u.last_name, u.email,
                       ua.full_name as shipping_name, ua.address_line1, ua.address_line2,
                       ua.city, ua.state, ua.postal_code, ua.country, ua.phone
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
                WHERE o.id = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Get order items
            $stmt = $pdo->prepare("
                SELECT oi.*, p.title, p.image, p.brand, p.color, p.description
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
                ORDER BY oi.id
            ");
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combine data
            $order['customer_name'] = $order['first_name'] . ' ' . $order['last_name'];
            $order['customer_email'] = $order['email'];
            $order['items'] = $items;
            
            echo json_encode(['success' => true, 'order' => $order]);
            break;
            
        case 'get_invoice':
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $orderId = $_GET['order_id'] ?? '';
            if (!$orderId) {
                throw new Exception('Missing order ID');
            }
            
            // Get order details (same as above)
            $stmt = $pdo->prepare("
                SELECT o.*, u.first_name, u.last_name, u.email,
                       ua.full_name as shipping_name, ua.address_line1, ua.address_line2,
                       ua.city, ua.state, ua.postal_code, ua.country, ua.phone
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
                WHERE o.id = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Get order items
            $stmt = $pdo->prepare("
                SELECT oi.*, p.title, p.image, p.brand, p.color
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
                ORDER BY oi.id
            ");
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate invoice HTML
            $invoiceHTML = generateInvoiceHTML($order, $items);
            
            echo json_encode(['success' => true, 'invoice_html' => $invoiceHTML]);
            break;
            
        case 'export':
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            // Get filter parameters (same as main page)
            $status_filter = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            $date_from = $_GET['date_from'] ?? '';
            $date_to = $_GET['date_to'] ?? '';
            $payment_method = $_GET['payment_method'] ?? '';
            
            // Build WHERE clause
            $whereConditions = [];
            $params = [];
            
            if (!empty($status_filter)) {
                $whereConditions[] = "o.status = ?";
                $params[] = $status_filter;
            }
            
            if (!empty($search)) {
                $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if (!empty($date_from)) {
                $whereConditions[] = "DATE(o.created_at) >= ?";
                $params[] = $date_from;
            }
            
            if (!empty($date_to)) {
                $whereConditions[] = "DATE(o.created_at) <= ?";
                $params[] = $date_to;
            }
            
            if (!empty($payment_method)) {
                $whereConditions[] = "o.payment_method = ?";
                $params[] = $payment_method;
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get orders for export
            $query = "
                SELECT o.id, o.status, o.total_price, o.payment_method, o.created_at,
                       u.first_name, u.last_name, u.email,
                       ua.full_name as shipping_name, ua.address_line1, ua.city, ua.state
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
                $whereClause
                ORDER BY o.created_at DESC
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, [
                'Order ID', 'Customer Name', 'Email', 'Status', 'Total Price', 
                'Payment Method', 'Shipping Address', 'Order Date'
            ]);
            
            // CSV data
            foreach ($orders as $order) {
                fputcsv($output, [
                    $order['id'],
                    $order['first_name'] . ' ' . $order['last_name'],
                    $order['email'],
                    $order['status'],
                    $order['total_price'],
                    $order['payment_method'],
                    $order['address_line1'] . ', ' . $order['city'] . ', ' . $order['state'],
                    $order['created_at']
                ]);
            }
            
            fclose($output);
            exit();
            break;
            
        default:
            error_log("Orders API - Unknown action: '$action'");
            throw new Exception('Invalid action: ' . $action);
    }

} catch (Exception $e) {
    error_log("Orders API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'action' => $action]);
}

/**
 * Generate professional invoice HTML
 */
function generateInvoiceHTML($order, $items) {
    $paymentLabels = [
        'cod' => 'Cash on Delivery',
        'card' => 'Credit Card',
        'gcash' => 'GCash',
        'bank_transfer' => 'Bank Transfer'
    ];

    $subtotal = floatval($order['total_price']) + floatval($order['discount_amount'] ?? 0);
    $discount = floatval($order['discount_amount'] ?? 0);
    $total = floatval($order['total_price']);

    $itemsHTML = '';
    foreach ($items as $item) {
        $itemTotal = floatval($item['price']) * intval($item['quantity']);
        $itemsHTML .= "
            <tr>
                <td style='padding: 12px; border-bottom: 1px solid #eee;'>
                    <div style='font-weight: 500;'>{$item['title']}</div>
                    <small style='color: #666;'>{$item['brand']} - {$item['color']}</small>
                </td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center;'>Size {$item['size']}</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: right;'>₱" . number_format(floatval($item['price']), 2) . "</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: right; font-weight: 500;'>₱" . number_format($itemTotal, 2) . "</td>
            </tr>
        ";
    }

    $discountRow = '';
    if ($discount > 0) {
        $discountRow = "
            <tr>
                <td colspan='4' style='padding: 8px; text-align: right; font-weight: 500;'>Discount:</td>
                <td style='padding: 8px; text-align: right; color: #28a745; font-weight: 500;'>-₱" . number_format($discount, 2) . "</td>
            </tr>
        ";
    }

    return "
        <div style='max-width: 800px; margin: 0 auto; font-family: Arial, sans-serif; color: #333;'>
            <!-- Invoice Header -->
            <div style='text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #000;'>
                <h1 style='margin: 0; font-size: 28px; font-weight: bold;'>INVOICE</h1>
                <p style='margin: 5px 0 0 0; color: #666; font-size: 16px;'>ShoeARizz Philippines</p>
            </div>

            <!-- Invoice Info -->
            <div style='display: flex; justify-content: space-between; margin-bottom: 30px;'>
                <div style='flex: 1;'>
                    <h3 style='margin: 0 0 10px 0; font-size: 18px;'>Bill To:</h3>
                    <div style='line-height: 1.6;'>
                        <strong>" . htmlspecialchars($order['shipping_name'] ?: ($order['first_name'] . ' ' . $order['last_name'])) . "</strong><br>
                        " . htmlspecialchars($order['address_line1']) . "<br>
                        " . ($order['address_line2'] ? htmlspecialchars($order['address_line2']) . '<br>' : '') . "
                        " . htmlspecialchars($order['city']) . ", " . htmlspecialchars($order['state']) . " " . htmlspecialchars($order['postal_code']) . "<br>
                        " . htmlspecialchars($order['country']) . "
                        " . ($order['phone'] ? '<br>Phone: ' . htmlspecialchars($order['phone']) : '') . "
                    </div>
                </div>
                <div style='flex: 1; text-align: right;'>
                    <table style='margin-left: auto;'>
                        <tr><td style='padding: 4px 8px; font-weight: 500;'>Invoice #:</td><td style='padding: 4px 0;'>#" . $order['id'] . "</td></tr>
                        <tr><td style='padding: 4px 8px; font-weight: 500;'>Date:</td><td style='padding: 4px 0;'>" . date('M j, Y', strtotime($order['created_at'])) . "</td></tr>
                        <tr><td style='padding: 4px 8px; font-weight: 500;'>Status:</td><td style='padding: 4px 0;'><span style='background: #f8f9fa; padding: 2px 8px; border-radius: 4px;'>" . $order['status'] . "</span></td></tr>
                        <tr><td style='padding: 4px 8px; font-weight: 500;'>Payment:</td><td style='padding: 4px 0;'>" . ($paymentLabels[$order['payment_method']] ?? $order['payment_method']) . "</td></tr>
                    </table>
                </div>
            </div>

            <!-- Items Table -->
            <table style='width: 100%; border-collapse: collapse; margin-bottom: 30px; border: 1px solid #ddd;'>
                <thead>
                    <tr style='background: #f8f9fa;'>
                        <th style='padding: 15px 12px; text-align: left; border-bottom: 2px solid #ddd; font-weight: 600;'>Product</th>
                        <th style='padding: 15px 12px; text-align: center; border-bottom: 2px solid #ddd; font-weight: 600;'>Size</th>
                        <th style='padding: 15px 12px; text-align: center; border-bottom: 2px solid #ddd; font-weight: 600;'>Qty</th>
                        <th style='padding: 15px 12px; text-align: right; border-bottom: 2px solid #ddd; font-weight: 600;'>Unit Price</th>
                        <th style='padding: 15px 12px; text-align: right; border-bottom: 2px solid #ddd; font-weight: 600;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHTML}
                </tbody>
            </table>

            <!-- Totals -->
            <div style='text-align: right; margin-bottom: 30px;'>
                <table style='margin-left: auto; min-width: 300px;'>
                    <tr>
                        <td style='padding: 8px; text-align: right; font-weight: 500;'>Subtotal:</td>
                        <td style='padding: 8px; text-align: right; font-weight: 500;'>₱" . number_format($subtotal, 2) . "</td>
                    </tr>
                    {$discountRow}
                    <tr style='border-top: 2px solid #000;'>
                        <td style='padding: 12px 8px; text-align: right; font-weight: bold; font-size: 18px;'>Total:</td>
                        <td style='padding: 12px 8px; text-align: right; font-weight: bold; font-size: 18px;'>₱" . number_format($total, 2) . "</td>
                    </tr>
                </table>
            </div>

            <!-- Footer -->
            <div style='text-align: center; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;'>
                <p style='margin: 0;'>Thank you for your business!</p>
                <p style='margin: 5px 0 0 0;'>For questions about this invoice, please contact us at support@shoearizz.store.ph</p>
            </div>
        </div>
    ";
}
?>
