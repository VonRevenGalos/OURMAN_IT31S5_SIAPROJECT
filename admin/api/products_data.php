<?php
/**
 * Products Data API
 * Provides real-time product data for admin interface
 */

// Include admin authentication
require_once __DIR__ . '/../includes/admin_auth.php';

// Set JSON header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Validate admin session
if (!validateAdminSession()) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid session']);
    exit();
}

try {
    // Database connection
    require_once __DIR__ . '/../../db.php';
    
    // Get request parameters
    $type = $_GET['type'] ?? 'all';
    $lastUpdate = $_GET['last_update'] ?? null;
    $productIds = $_GET['product_ids'] ?? null;
    
    $response = ['success' => true, 'timestamp' => time()];
    
    // Get product statistics
    if ($type === 'all' || $type === 'stats') {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_products,
                SUM(stock) as total_stock,
                COUNT(CASE WHEN stock <= 5 THEN 1 END) as low_stock,
                COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock,
                AVG(price) as avg_price,
                MIN(price) as min_price,
                MAX(price) as max_price,
                COUNT(DISTINCT category) as total_categories,
                COUNT(DISTINCT brand) as total_brands
            FROM products
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Convert to proper types
        $response['stats'] = [
            'total_products' => (int) $stats['total_products'],
            'total_stock' => (int) $stats['total_stock'],
            'low_stock' => (int) $stats['low_stock'],
            'out_of_stock' => (int) $stats['out_of_stock'],
            'avg_price' => (float) $stats['avg_price'],
            'min_price' => (float) $stats['min_price'],
            'max_price' => (float) $stats['max_price'],
            'total_categories' => (int) $stats['total_categories'],
            'total_brands' => (int) $stats['total_brands']
        ];
    }
    
    // Get recently updated products
    if ($type === 'all' || $type === 'updates') {
        $whereClause = '';
        $params = [];
        
        if ($lastUpdate) {
            $whereClause = 'WHERE date_added >= FROM_UNIXTIME(?)';
            $params[] = $lastUpdate;
        }
        
        if ($productIds) {
            $ids = explode(',', $productIds);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $whereClause = $whereClause ? 
                $whereClause . " OR id IN ($placeholders)" : 
                "WHERE id IN ($placeholders)";
            $params = array_merge($params, $ids);
        }
        
        $stmt = $pdo->prepare("
            SELECT id, title, stock, price, category, brand, color, date_added
            FROM products 
            $whereClause
            ORDER BY date_added DESC
            LIMIT 50
        ");
        $stmt->execute($params);
        $updatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['updated_products'] = $updatedProducts;
    }
    
    // Get low stock products
    if ($type === 'all' || $type === 'alerts') {
        $stmt = $pdo->query("
            SELECT id, title, stock, price, category, brand
            FROM products
            WHERE stock <= 5
            ORDER BY stock ASC, title ASC
            LIMIT 20
        ");
        $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['low_stock_products'] = $lowStockProducts;
    }
    
    // Get category breakdown
    if ($type === 'all' || $type === 'categories') {
        $stmt = $pdo->query("
            SELECT 
                category,
                COUNT(*) as product_count,
                SUM(stock) as total_stock,
                AVG(price) as avg_price,
                COUNT(CASE WHEN stock <= 5 THEN 1 END) as low_stock_count
            FROM products
            GROUP BY category
            ORDER BY product_count DESC
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['categories'] = $categories;
    }
    
    // Get brand breakdown
    if ($type === 'all' || $type === 'brands') {
        $stmt = $pdo->query("
            SELECT 
                brand,
                COUNT(*) as product_count,
                SUM(stock) as total_stock,
                AVG(price) as avg_price
            FROM products
            GROUP BY brand
            ORDER BY product_count DESC
        ");
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['brands'] = $brands;
    }
    
    // Get recent products
    if ($type === 'all' || $type === 'recent') {
        $stmt = $pdo->query("
            SELECT id, title, price, stock, category, brand, color, image, date_added
            FROM products
            ORDER BY date_added DESC
            LIMIT 10
        ");
        $recentProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['recent_products'] = $recentProducts;
    }
    
    // Get search suggestions
    if ($type === 'suggestions') {
        $query = $_GET['q'] ?? '';
        if (!empty($query)) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT title, category, brand
                FROM products
                WHERE title LIKE ? OR category LIKE ? OR brand LIKE ?
                ORDER BY title ASC
                LIMIT 10
            ");
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['suggestions'] = $suggestions;
        }
    }
    
    // Get product details
    if ($type === 'product' && isset($_GET['id'])) {
        $productId = (int) $_GET['id'];
        $stmt = $pdo->prepare("
            SELECT *
            FROM products
            WHERE id = ?
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $response['product'] = $product;
            
            // Get related products
            $stmt = $pdo->prepare("
                SELECT id, title, price, stock, image
                FROM products
                WHERE category = ? AND id != ?
                ORDER BY RAND()
                LIMIT 4
            ");
            $stmt->execute([$product['category'], $productId]);
            $relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['related_products'] = $relatedProducts;
        } else {
            $response['error'] = 'Product not found';
        }
    }
    
    // Performance metrics
    $response['performance'] = [
        'query_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true)
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Products API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Unable to fetch product data'
    ]);
} catch (Exception $e) {
    error_log("Products API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'An unexpected error occurred'
    ]);
}
?>
