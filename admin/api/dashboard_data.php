<?php
/**
 * Real-time Dashboard Data API
 * Provides live data updates for admin dashboard
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
    
    $response = ['success' => true, 'timestamp' => time()];
    
    // Get dashboard statistics
    if ($type === 'all' || $type === 'stats') {
        // Total counts
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role != 'admin'");
        $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
        $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
        $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
        
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) as total_revenue FROM orders WHERE status != 'Cancelled'");
        $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
        
        // Activity summary
        $stmt = $pdo->query("
            SELECT
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_orders,
                COUNT(CASE WHEN DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as yesterday_orders,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_orders,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_price ELSE 0 END) as today_revenue,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN total_price ELSE 0 END) as week_revenue
            FROM orders
            WHERE status != 'Cancelled'
        ");
        $activitySummary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response['stats'] = [
            'total_users' => (int) $totalUsers,
            'total_products' => (int) $totalProducts,
            'total_orders' => (int) $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'activity' => $activitySummary
        ];
    }
    
    // Get recent orders
    if ($type === 'all' || $type === 'orders') {
        $stmt = $pdo->query("
            SELECT o.*, u.first_name, u.last_name, u.email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT 8
        ");
        $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['recent_orders'] = $recentOrders;
    }
    
    // Get top selling products
    if ($type === 'all' || $type === 'products') {
        $stmt = $pdo->query("
            SELECT
                p.id,
                p.title,
                p.price,
                p.image as image_url,
                p.category,
                p.brand,
                p.collection,
                SUM(oi.quantity) as total_sold,
                SUM(oi.quantity * oi.price) as total_revenue,
                COUNT(DISTINCT o.id) as order_count
            FROM products p
            JOIN order_items oi ON p.id = oi.product_id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status != 'Cancelled'
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT 6
        ");
        $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['top_products'] = $topProducts;
    }
    
    // Get chart data
    if ($type === 'all' || $type === 'charts') {
        // Weekly sales data
        $stmt = $pdo->query("
            SELECT
                YEARWEEK(created_at, 1) as week_year,
                DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY)) as week_start,
                COUNT(*) as order_count,
                SUM(total_price) as revenue,
                AVG(total_price) as avg_order_value
            FROM orders
            WHERE status != 'Cancelled'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
            GROUP BY YEARWEEK(created_at, 1)
            ORDER BY week_year
        ");
        $weeklySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Order status distribution
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count,
                   ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM orders)), 1) as percentage
            FROM orders
            GROUP BY status
            ORDER BY count DESC
        ");
        $orderStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['charts'] = [
            'weekly_sales' => $weeklySales,
            'order_status' => $orderStatus
        ];
    }
    
    // Get notifications/alerts
    if ($type === 'all' || $type === 'notifications') {
        // Low stock products
        $stmt = $pdo->query("
            SELECT id, title, stock
            FROM products
            WHERE stock <= 5
            ORDER BY stock ASC
            LIMIT 5
        ");
        $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent user registrations
        $stmt = $pdo->query("
            SELECT COUNT(*) as new_users
            FROM users
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND role != 'admin'
        ");
        $newUsers = $stmt->fetch(PDO::FETCH_ASSOC)['new_users'];
        
        // Pending orders count
        $stmt = $pdo->query("
            SELECT COUNT(*) as pending_orders
            FROM orders
            WHERE status = 'Pending'
        ");
        $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['pending_orders'];
        
        $response['notifications'] = [
            'low_stock_products' => $lowStock,
            'new_users_today' => (int) $newUsers,
            'pending_orders' => (int) $pendingOrders
        ];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Dashboard API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Unable to fetch dashboard data'
    ]);
} catch (Exception $e) {
    error_log("Dashboard API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'An unexpected error occurred'
    ]);
}
?>
