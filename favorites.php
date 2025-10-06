<?php
require_once 'includes/session.php';
require_once 'db.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = getUserId();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'remove_favorite':
                $product_id = (int)$_POST['product_id'];
                if ($product_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                    exit();
                }

                $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
                $result = $stmt->execute([$user_id, $product_id]);

                // Get updated count
                $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
                $count_stmt->execute([$user_id]);
                $new_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

                echo json_encode([
                    'success' => $result,
                    'remaining_count' => $new_count
                ]);
                exit();
                
            case 'clear_all_favorites':
                $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ?");
                $result = $stmt->execute([$user_id]);
                echo json_encode([
                    'success' => $result,
                    'remaining_count' => 0
                ]);
                exit();
                
            case 'remove_selected':
                $product_ids = $_POST['product_ids'] ?? [];

                // Validate and sanitize product IDs
                $valid_ids = [];
                foreach ($product_ids as $id) {
                    $clean_id = (int)$id;
                    if ($clean_id > 0) {
                        $valid_ids[] = $clean_id;
                    }
                }

                if (!empty($valid_ids)) {
                    $placeholders = str_repeat('?,', count($valid_ids) - 1) . '?';
                    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id IN ($placeholders)");
                    $result = $stmt->execute(array_merge([$user_id], $valid_ids));

                    // Get updated count
                    $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
                    $count_stmt->execute([$user_id]);
                    $new_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

                    echo json_encode([
                        'success' => $result,
                        'removed_count' => count($valid_ids),
                        'remaining_count' => $new_count
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No valid items selected']);
                }
                exit();

            case 'add_selected_to_cart':
                $product_ids = $_POST['product_ids'] ?? [];

                // Validate and sanitize product IDs
                $valid_ids = [];
                foreach ($product_ids as $id) {
                    $clean_id = (int)$id;
                    if ($clean_id > 0) {
                        $valid_ids[] = $clean_id;
                    }
                }

                if (empty($valid_ids)) {
                    echo json_encode(['success' => false, 'message' => 'No valid items selected']);
                    exit();
                }

                try {
                    $pdo->beginTransaction();

                    $added_count = 0;
                    $out_of_stock_items = [];

                    foreach ($valid_ids as $product_id) {
                        // Check if product exists and has stock
                        $stmt = $pdo->prepare("SELECT id, title, stock FROM products WHERE id = ?");
                        $stmt->execute([$product_id]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$product) {
                            continue; // Skip if product doesn't exist
                        }

                        if ($product['stock'] <= 0) {
                            $out_of_stock_items[] = $product['title'];
                            continue; // Skip out of stock items
                        }

                        // Check if item already exists in cart
                        $stmt = $pdo->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size IS NULL");
                        $stmt->execute([$user_id, $product_id]);
                        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($existing_item) {
                            // Update existing cart item
                            $new_quantity = $existing_item['quantity'] + 1;
                            if ($new_quantity > $product['stock']) {
                                $new_quantity = $product['stock'];
                            }

                            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
                            $stmt->execute([$new_quantity, $existing_item['cart_id']]);
                        } else {
                            // Add new item to cart
                            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, size, date_added) VALUES (?, ?, ?, ?, NOW())");
                            $stmt->execute([$user_id, $product_id, 1, null]);
                        }

                        $added_count++;
                    }

                    // Remove successfully added items from favorites
                    if ($added_count > 0) {
                        $added_product_ids = array_slice($valid_ids, 0, $added_count);
                        $placeholders = str_repeat('?,', count($added_product_ids) - 1) . '?';
                        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id IN ($placeholders)");
                        $stmt->execute(array_merge([$user_id], $added_product_ids));
                    }

                    $pdo->commit();

                    // Get updated counts
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $favorites_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

                    $message = "Successfully added {$added_count} item(s) to cart";
                    if (!empty($out_of_stock_items)) {
                        $message .= ". " . count($out_of_stock_items) . " item(s) were out of stock: " . implode(', ', $out_of_stock_items);
                    }

                    echo json_encode([
                        'success' => true,
                        'message' => $message,
                        'added_count' => $added_count,
                        'out_of_stock_count' => count($out_of_stock_items),
                        'out_of_stock_items' => $out_of_stock_items,
                        'remaining_favorites' => $favorites_count,
                        'cart_count' => (int)$cart_count
                    ]);

                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Add to cart error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
                }
                exit();
        }
    }
}

// Fetch user's favorite products
try {
    $stmt = $pdo->prepare("
        SELECT p.*, f.created_at as favorited_at 
        FROM favorites f 
        JOIN products p ON f.product_id = p.id 
        WHERE f.user_id = ? 
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Favorites fetch error: " . $e->getMessage());
    $favorites = [];
}

// Get current user
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - ShoeARizz</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/favorites.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid favorites-container">
        <div class="row">
            <div class="col-12">
                <!-- Page Header -->
                <div class="favorites-header">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h1 class="page-title">
                                    <i class="fas fa-heart me-3"></i>My Favorites
                                </h1>
                                <p class="page-subtitle">
                                    <span id="favorites-count"><?php echo count($favorites); ?></span> item<?php echo count($favorites) !== 1 ? 's' : ''; ?> in your favorites
                                </p>
                            </div>
                            <div class="col-md-6 text-end">
                                <?php if (!empty($favorites)): ?>
                                <div class="header-actions">
                                    <button class="btn btn-outline-dark me-2" id="select-all-btn" onclick="toggleSelectAll()">
                                        <i class="fas fa-check-square me-1"></i>Select All
                                    </button>
                                    <button class="btn btn-success me-2" id="add-to-cart-btn" onclick="addSelectedToCart()" disabled>
                                        <i class="fas fa-shopping-cart me-1"></i>Add Selected to Cart
                                    </button>
                                    <button class="btn btn-warning me-2" id="remove-selected-btn" onclick="removeSelected()" disabled>
                                        <i class="fas fa-trash me-1"></i>Remove Selected
                                    </button>
                                    <button class="btn btn-dark" onclick="clearAllFavorites()">
                                        <i class="fas fa-trash-alt me-1"></i>Clear All
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Favorites Content -->
                <div class="favorites-content">
                    <div class="container">
                        <?php if (empty($favorites)): ?>
                            <!-- Empty State -->
                            <div class="empty-favorites">
                                <div class="empty-icon">
                                    <i class="fas fa-heart-broken"></i>
                                </div>
                                <h3>No favorites yet</h3>
                                <p>Start adding products to your favorites to see them here.</p>
                                <a href="men.php" class="btn btn-dark btn-lg">
                                    <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Favorites Grid -->
                            <div class="favorites-grid">
                                <div class="row" id="favorites-grid">
                                    <?php foreach ($favorites as $product): ?>
                                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4" data-product-id="<?php echo $product['id']; ?>">
                                            <div class="favorite-card">
                                                <div class="favorite-checkbox">
                                                    <input type="checkbox" class="form-check-input favorite-select" value="<?php echo $product['id']; ?>" onchange="updateSelectedCount()">
                                                </div>
                                                <div class="favorite-image">
                                                    <a href="product.php?id=<?php echo $product['id']; ?>">
                                                        <img src="<?php echo htmlspecialchars(trim($product['image'])); ?>" 
                                                             alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                                             class="img-fluid"
                                                             onerror="this.src='assets/img/placeholder.jpg'">
                                                    </a>
                                                    <button class="btn-remove-favorite" onclick="removeFromFavorites(<?php echo $product['id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="favorite-info">
                                                    <div class="product-brand"><?php echo htmlspecialchars($product['brand'] ?? 'Generic'); ?></div>
                                                    <h3 class="product-title">
                                                        <a href="product.php?id=<?php echo $product['id']; ?>">
                                                            <?php echo htmlspecialchars($product['title']); ?>
                                                        </a>
                                                    </h3>
                                                    <div class="product-details">
                                                        <span class="product-color"><?php echo htmlspecialchars($product['color'] ?? 'N/A'); ?></span>
                                                        <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                                    </div>
                                                    <div class="product-stock">
                                                        <?php if ($product['stock'] > 0): ?>
                                                            <span class="text-success">
                                                                <i class="fas fa-check-circle me-1"></i>In Stock
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-danger">
                                                                <i class="fas fa-times-circle me-1"></i>Out of Stock
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="favorite-actions">
                                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-dark btn-sm">
                                                            <i class="fas fa-eye me-1"></i>View
                                                        </a>
                                                        <button class="btn btn-outline-dark btn-sm"
                                                                onclick="addToCart(<?php echo $product['id']; ?>, 1, null, this, true)"
                                                                <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-shopping-cart me-1"></i>Add to Cart
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
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
    <?php if (isLoggedIn()): ?>
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>
    <?php endif; ?>
    <script src="assets/js/favorites.js"></script>
</body>
</html>
