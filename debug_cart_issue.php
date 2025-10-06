<?php
require_once 'includes/session.php';
require_once 'db.php';

echo "<!DOCTYPE html><html><head><title>Cart Debug</title></head><body>";
echo "<h1>Cart Functionality Debug</h1>";

// Test 1: Check if user is logged in
echo "<h2>Test 1: User Login Status</h2>";
if (isLoggedIn()) {
    $user_id = getUserId();
    echo "<p style='color: green;'>✓ User is logged in (ID: {$user_id})</p>";
} else {
    echo "<p style='color: red;'>✗ User is not logged in</p>";
    echo "<p><a href='login.php'>Please login to test cart functionality</a></p>";
    echo "</body></html>";
    exit();
}

// Test 2: Check cart table structure
echo "<h2>Test 2: Cart Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE cart");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✓ Cart table exists</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error checking cart table: " . $e->getMessage() . "</p>";
}

// Test 3: Check current cart contents
echo "<h2>Test 3: Current Cart Contents</h2>";
try {
    $stmt = $pdo->prepare("SELECT c.*, p.title, p.price FROM cart c LEFT JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($cart_items) . " items in cart</p>";
    
    if (count($cart_items) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Cart ID</th><th>Product</th><th>Quantity</th><th>Size</th><th>Price</th><th>Added</th></tr>";
        foreach ($cart_items as $item) {
            echo "<tr>";
            echo "<td>{$item['cart_id']}</td>";
            echo "<td>{$item['title']}</td>";
            echo "<td>{$item['quantity']}</td>";
            echo "<td>" . ($item['size'] ?: 'N/A') . "</td>";
            echo "<td>₱" . number_format($item['price'], 2) . "</td>";
            echo "<td>{$item['date_added']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><em>Cart is empty</em></p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error checking cart contents: " . $e->getMessage() . "</p>";
}

// Test 4: Test manual add to cart
echo "<h2>Test 4: Manual Add to Cart</h2>";
try {
    // Get a test product
    $stmt = $pdo->query("SELECT id, title, stock FROM products LIMIT 1");
    $test_product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test_product) {
        echo "<p>Testing with product: {$test_product['title']} (ID: {$test_product['id']})</p>";
        
        // Try to add to cart
        $stmt = $pdo->prepare("
            INSERT INTO cart (user_id, product_id, quantity, size, date_added)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            quantity = quantity + VALUES(quantity)
        ");
        
        $result = $stmt->execute([$user_id, $test_product['id'], 1, null]);
        
        if ($result) {
            echo "<p style='color: green;'>✓ Successfully added product to cart</p>";
            
            // Get updated cart count
            $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            echo "<p>New cart count: {$cart_count}</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to add product to cart</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ No test product found</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test 5: Test add_to_cart.php endpoint
echo "<h2>Test 5: Add to Cart Endpoint Test</h2>";
if (file_exists('add_to_cart.php')) {
    echo "<p style='color: green;'>✓ add_to_cart.php exists</p>";
    
    // Test the endpoint with a sample request
    echo "<h3>Testing endpoint with sample data:</h3>";
    echo "<div id='endpoint-test'>";
    echo "<button onclick='testAddToCartEndpoint()'>Test Add to Cart Endpoint</button>";
    echo "<div id='endpoint-result'></div>";
    echo "</div>";
} else {
    echo "<p style='color: red;'>✗ add_to_cart.php not found</p>";
}

// Test 6: Check JavaScript integration
echo "<h2>Test 6: JavaScript Integration</h2>";
$js_files = [
    'assets/js/global-cart.js' => 'Global Cart Functions',
    'assets/js/navbar.js' => 'Navbar Functions'
];

foreach ($js_files as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ {$description} ({$file}) exists</p>";
    } else {
        echo "<p style='color: red;'>✗ {$description} ({$file}) not found</p>";
    }
}

// Test 7: Check navbar cart badge
echo "<h2>Test 7: Navbar Cart Badge</h2>";
echo "<p>Current cart badge HTML:</p>";
echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
echo htmlspecialchars('<a href="cart.php" class="nav-icon" title="Shopping Cart">
    <i class="fas fa-shopping-cart"></i>
    <span class="notification-badge cart-count" style="display: none;">0</span>
</a>');
echo "</div>";

echo "<p>JavaScript should target: <code>a[href=\"cart.php\"] .cart-count</code></p>";

echo "<script>
function testAddToCartEndpoint() {
    const formData = new FormData();
    formData.append('product_id', 1);
    formData.append('quantity', 1);
    
    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('endpoint-result').innerHTML = 
            '<h4>Response:</h4><pre>' + JSON.stringify(data, null, 2) + '</pre>';
    })
    .catch(error => {
        document.getElementById('endpoint-result').innerHTML = 
            '<h4>Error:</h4><pre>' + error.toString() + '</pre>';
    });
}

// Test cart count update function
function testCartCountUpdate() {
    const cartIcon = document.querySelector('a[href=\"cart.php\"] .cart-count');
    if (cartIcon) {
        console.log('Cart icon found:', cartIcon);
        cartIcon.textContent = '5';
        cartIcon.style.display = 'flex';
        alert('Cart count updated to 5. Check the navbar.');
    } else {
        alert('Cart icon not found. Selector: a[href=\"cart.php\"] .cart-count');
    }
}
</script>";

echo "<h2>Manual Tests</h2>";
echo "<button onclick='testCartCountUpdate()'>Test Cart Count Update</button>";
echo "<p><em>This will manually update the cart count to test if the selector works</em></p>";

echo "</body></html>";
?>
