<?php
require_once 'includes/session.php';
require_once 'db.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please log in to manage cart']);
    exit();
}

$user_id = getUserId();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $cart_id = (int)$_POST['cart_id'];
                $quantity = (int)$_POST['quantity'];
                
                if ($cart_id <= 0 || $quantity <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                    exit();
                }
                
                try {
                    // Check if cart item belongs to user and get product stock
                    $stmt = $pdo->prepare("
                        SELECT c.cart_id, c.product_id, p.stock, p.price 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.cart_id = ? AND c.user_id = ?
                    ");
                    $stmt->execute([$cart_id, $user_id]);
                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$item) {
                        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                        exit();
                    }
                    
                    if ($quantity > $item['stock']) {
                        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
                        exit();
                    }
                    
                    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
                    $result = $stmt->execute([$quantity, $cart_id]);
                    
                    if ($result) {
                        // Get updated cart count and total
                        $stmt = $pdo->prepare("
                            SELECT SUM(c.quantity) as count, SUM(c.quantity * p.price) as total 
                            FROM cart c 
                            JOIN products p ON c.product_id = p.id 
                            WHERE c.user_id = ?
                        ");
                        $stmt->execute([$user_id]);
                        $cart_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        echo json_encode([
                            'success' => true,
                            'cart_count' => (int)($cart_data['count'] ?? 0),
                            'cart_total' => (float)($cart_data['total'] ?? 0),
                            'item_total' => $quantity * $item['price']
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update quantity']);
                    }
                } catch (PDOException $e) {
                    error_log("Update quantity error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error']);
                }
                exit();
                
            case 'update_size':
                $cart_id = (int)$_POST['cart_id'];
                $size = $_POST['size'] ?? null;
                
                if ($cart_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
                    exit();
                }
                
                try {
                    // Check if cart item belongs to user
                    $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE cart_id = ? AND user_id = ?");
                    $stmt->execute([$cart_id, $user_id]);
                    
                    if (!$stmt->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                        exit();
                    }
                    
                    $stmt = $pdo->prepare("UPDATE cart SET size = ? WHERE cart_id = ?");
                    $result = $stmt->execute([$size, $cart_id]);
                    
                    echo json_encode(['success' => $result]);
                } catch (PDOException $e) {
                    error_log("Update size error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error']);
                }
                exit();
                
            case 'remove_item':
                $cart_id = (int)$_POST['cart_id'];
                
                if ($cart_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
                    exit();
                }
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
                    $result = $stmt->execute([$cart_id, $user_id]);
                    
                    if ($result) {
                        // Get updated cart count and total
                        $stmt = $pdo->prepare("
                            SELECT SUM(c.quantity) as count, SUM(c.quantity * p.price) as total 
                            FROM cart c 
                            JOIN products p ON c.product_id = p.id 
                            WHERE c.user_id = ?
                        ");
                        $stmt->execute([$user_id]);
                        $cart_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        echo json_encode([
                            'success' => true,
                            'cart_count' => (int)($cart_data['count'] ?? 0),
                            'cart_total' => (float)($cart_data['total'] ?? 0)
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
                    }
                } catch (PDOException $e) {
                    error_log("Remove item error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error']);
                }
                exit();
                
            case 'remove_selected':
                $cart_ids = $_POST['cart_ids'] ?? [];
                
                // Validate and sanitize cart IDs
                $valid_ids = [];
                foreach ($cart_ids as $id) {
                    $clean_id = (int)$id;
                    if ($clean_id > 0) {
                        $valid_ids[] = $clean_id;
                    }
                }
                
                if (!empty($valid_ids)) {
                    try {
                        $placeholders = str_repeat('?,', count($valid_ids) - 1) . '?';
                        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND cart_id IN ($placeholders)");
                        $result = $stmt->execute(array_merge([$user_id], $valid_ids));
                        
                        if ($result) {
                            // Get updated cart count and total
                            $stmt = $pdo->prepare("
                                SELECT SUM(c.quantity) as count, SUM(c.quantity * p.price) as total 
                                FROM cart c 
                                JOIN products p ON c.product_id = p.id 
                                WHERE c.user_id = ?
                            ");
                            $stmt->execute([$user_id]);
                            $cart_data = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo json_encode([
                                'success' => true,
                                'removed_count' => count($valid_ids),
                                'cart_count' => (int)($cart_data['count'] ?? 0),
                                'cart_total' => (float)($cart_data['total'] ?? 0)
                            ]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to remove selected items']);
                        }
                    } catch (PDOException $e) {
                        error_log("Remove selected error: " . $e->getMessage());
                        echo json_encode(['success' => false, 'message' => 'Database error']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'No valid items selected']);
                }
                exit();
                
            case 'clear_cart':
                try {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $result = $stmt->execute([$user_id]);
                    
                    echo json_encode([
                        'success' => $result,
                        'cart_count' => 0,
                        'cart_total' => 0
                    ]);
                } catch (PDOException $e) {
                    error_log("Clear cart error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error']);
                }
                exit();
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
