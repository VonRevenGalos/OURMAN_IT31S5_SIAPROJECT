<?php
/**
 * Top Products API
 * Provides top selling products data
 */

// Include admin authentication
require_once __DIR__ . '/../includes/admin_auth.php';

// Set JSON header
header('Content-Type: application/json');

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
    // Get parameters
    $limit = (int) ($_GET['limit'] ?? 10);
    $period = $_GET['period'] ?? 'all'; // all, week, month
    
    // Database connection
    require_once __DIR__ . '/../../db.php';
    
    // Build date filter
    $dateFilter = '';
    $params = [];
    
    switch ($period) {
        case 'week':
            $dateFilter = 'AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
            break;
        case 'month':
            $dateFilter = 'AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
            break;
        case 'quarter':
            $dateFilter = 'AND o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)';
            break;
        default:
            $dateFilter = '';
    }
    
    // Query top selling products
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.title,
            p.price,
            p.image as image_url,
            p.category,
            p.brand,
            p.collection,
            p.stock,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue,
            COUNT(DISTINCT o.id) as order_count,
            AVG(oi.price) as avg_price,
            MAX(o.created_at) as last_sold
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status != 'Cancelled'
        {$dateFilter}
        GROUP BY p.id
        ORDER BY total_sold DESC, total_revenue DESC
        LIMIT ?
    ");
    
    $params[] = $limit;
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format products data
    $formattedProducts = [];
    foreach ($products as $index => $product) {
        $formattedProducts[] = [
            'rank' => $index + 1,
            'id' => (int) $product['id'],
            'title' => $product['title'],
            'price' => (float) $product['price'],
            'image_url' => $product['image_url'],
            'category' => $product['category'],
            'brand' => $product['brand'],
            'collection' => $product['collection'],
            'stock' => (int) $product['stock'],
            'total_sold' => (int) $product['total_sold'],
            'total_revenue' => (float) $product['total_revenue'],
            'order_count' => (int) $product['order_count'],
            'avg_price' => (float) $product['avg_price'],
            'last_sold' => $product['last_sold'],
            'revenue_per_unit' => $product['total_sold'] > 0 ? (float) $product['total_revenue'] / (int) $product['total_sold'] : 0
        ];
    }
    
    // Get category breakdown
    $stmt = $pdo->prepare("
        SELECT
            p.category,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue,
            COUNT(DISTINCT p.id) as product_count
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status != 'Cancelled'
        {$dateFilter}
        GROUP BY p.category
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    
    $stmt->execute(array_slice($params, 0, -1)); // Remove limit parameter
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $totalSold = array_sum(array_column($formattedProducts, 'total_sold'));
    $totalRevenue = array_sum(array_column($formattedProducts, 'total_revenue'));
    $avgRevenuePerProduct = count($formattedProducts) > 0 ? $totalRevenue / count($formattedProducts) : 0;
    
    // Return response
    echo json_encode([
        'success' => true,
        'products' => $formattedProducts,
        'categories' => $categories,
        'summary' => [
            'total_products' => count($formattedProducts),
            'total_sold' => $totalSold,
            'total_revenue' => $totalRevenue,
            'avg_revenue_per_product' => $avgRevenuePerProduct
        ],
        'period' => $period,
        'limit' => $limit
    ]);
    
} catch (PDOException $e) {
    error_log("Top products API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Unable to fetch top products data'
    ]);
} catch (Exception $e) {
    error_log("Top products API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'An unexpected error occurred'
    ]);
}
?>
