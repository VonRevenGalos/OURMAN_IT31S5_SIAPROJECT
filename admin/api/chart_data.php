<?php
/**
 * Chart Data API
 * Provides dynamic chart data for dashboard
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
    $period = $_GET['period'] ?? 'weekly';
    $filter = $_GET['filter'] ?? 'all';
    
    // Database connection
    require_once __DIR__ . '/../../db.php';
    
    if ($period === 'weekly') {
        // Weekly sales data
        $weeks = 12; // Default to 12 weeks
        
        if ($filter !== 'all') {
            $weeks = (int) str_replace('last', '', $filter);
        }
        
        $stmt = $pdo->prepare("
            SELECT
                YEARWEEK(created_at, 1) as week_year,
                WEEK(created_at, 1) as week_num,
                YEAR(created_at) as year,
                DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY)) as week_start,
                COUNT(*) as order_count,
                SUM(total_price) as revenue,
                AVG(total_price) as avg_order_value
            FROM orders
            WHERE status != 'Cancelled'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? WEEK)
            GROUP BY YEARWEEK(created_at, 1)
            ORDER BY week_year
        ");
        $stmt->execute([$weeks]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Monthly sales data
        $stmt = $pdo->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as order_count,
                SUM(total_price) as revenue,
                AVG(total_price) as avg_order_value
            FROM orders
            WHERE status != 'Cancelled'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Format data for chart
    $chartData = [];
    foreach ($data as $item) {
        if ($period === 'weekly') {
            $date = new DateTime($item['week_start']);
            $label = $date->format('M j');
        } else {
            $parts = explode('-', $item['month']);
            $date = new DateTime($parts[0] . '-' . $parts[1] . '-01');
            $label = $date->format('M Y');
        }
        
        $chartData[] = [
            'label' => $label,
            'revenue' => (float) ($item['revenue'] ?? 0),
            'orders' => (int) ($item['order_count'] ?? 0),
            'avg_order' => (float) ($item['avg_order_value'] ?? 0)
        ];
    }
    
    // Calculate totals
    $totalRevenue = array_sum(array_column($chartData, 'revenue'));
    $totalOrders = array_sum(array_column($chartData, 'orders'));
    $avgOrder = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
    
    // Return response
    echo json_encode([
        'success' => true,
        'data' => $chartData,
        'summary' => [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'avg_order' => $avgOrder
        ],
        'period' => $period,
        'filter' => $filter
    ]);
    
} catch (PDOException $e) {
    error_log("Chart data API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Unable to fetch chart data'
    ]);
} catch (Exception $e) {
    error_log("Chart data API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'An unexpected error occurred'
    ]);
}
?>
