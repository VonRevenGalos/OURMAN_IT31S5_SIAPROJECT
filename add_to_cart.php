<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $size = $_POST['size'] ?? null;
    $from_favorites = isset($_POST['from_favorites']) && $_POST['from_favorites'] === 'true';
    $user_id = getUserId();
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit();
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
        exit();
    }
    
    try {
        // Check if product exists and has enough stock
        $stmt = $pdo->prepare("SELECT id, title, stock, price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit();
        }
        
        if ($product['stock'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            exit();
        }
        
        // Check if item already exists in cart
        $stmt = $pdo->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size = ?");
        $stmt->execute([$user_id, $product_id, $size]);
        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_item) {
            // Update existing item quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            
            // Check if new quantity exceeds stock
            if ($new_quantity > $product['stock']) {
                echo json_encode(['success' => false, 'message' => 'Cannot add more items. Stock limit reached.']);
                exit();
            }
            
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
            $result = $stmt->execute([$new_quantity, $existing_item['cart_id']]);
            $message = 'Cart updated successfully!';
        } else {
            // Add new item to cart
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, size, date_added) VALUES (?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$user_id, $product_id, $quantity, $size]);
            $message = 'Added to cart successfully!';
        }
        
        if ($result) {
            // If adding from favorites, remove from favorites
            if ($from_favorites) {
                $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
                $message = 'Product moved to cart from favorites!';
            }

            // Get updated cart count
            $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // Get updated favorites count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $favorites_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            echo json_encode([
                'success' => true,
                'message' => $message,
                'cart_count' => (int)$cart_count,
                'favorites_count' => (int)$favorites_count,
                'from_favorites' => $from_favorites
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
        }
        
    } catch (PDOException $e) {
        error_log("Add to cart error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
