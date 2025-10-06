<?php
/**
 * Professional Analytics Dashboard
 * Sales Data, Revenue, Product Performance Reports
 */

// Include admin authentication
require_once __DIR__ . '/includes/admin_auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: adminlogin.php');
    exit();
}

// Validate admin session
if (!validateAdminSession()) {
    header('Location: adminlogin.php');
    exit();
}

// Get current admin user info
$currentUser = getCurrentAdminUser();

// Database connection
require_once __DIR__ . '/../db.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'get_sales_data':
            $startDate = $_POST['start_date'] ?? date('Y-m-01');
            $endDate = $_POST['end_date'] ?? date('Y-m-d');
            
            try {
                $salesData = getSalesData($pdo, $startDate, $endDate);
                echo json_encode(['success' => true, 'data' => $salesData]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();

        case 'get_revenue_data':
            $startDate = $_POST['start_date'] ?? date('Y-m-01');
            $endDate = $_POST['end_date'] ?? date('Y-m-d');
            
            try {
                $revenueData = getRevenueData($pdo, $startDate, $endDate);
                echo json_encode(['success' => true, 'data' => $revenueData]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();

        case 'get_product_performance':
            $startDate = $_POST['start_date'] ?? date('Y-m-01');
            $endDate = $_POST['end_date'] ?? date('Y-m-d');
            
            try {
                $productData = getProductPerformance($pdo, $startDate, $endDate);
                echo json_encode(['success' => true, 'data' => $productData]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();

        case 'get_category_analysis':
            $startDate = $_POST['start_date'] ?? date('Y-m-01');
            $endDate = $_POST['end_date'] ?? date('Y-m-d');
            
            try {
                $categoryData = getCategoryAnalysis($pdo, $startDate, $endDate);
                echo json_encode(['success' => true, 'data' => $categoryData]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();

        case 'get_analytics_stats':
            $startDate = $_POST['start_date'] ?? date('Y-m-01');
            $endDate = $_POST['end_date'] ?? date('Y-m-d');
            
            try {
                $stats = getAnalyticsStats($pdo, $startDate, $endDate);
                echo json_encode(['success' => true, 'stats' => $stats]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();
    }
}

// Helper Functions
function getSalesData($pdo, $startDate, $endDate) {
    // First, create a date range table using a recursive CTE
    $stmt = $pdo->prepare("
        WITH RECURSIVE date_range AS (
            SELECT ? as date
            UNION ALL
            SELECT DATE_ADD(date, INTERVAL 1 DAY)
            FROM date_range
            WHERE date < ?
        )
        SELECT
            dr.date,
            COALESCE(COUNT(o.id), 0) as orders_count,
            COALESCE(SUM(o.total_price), 0) as daily_revenue,
            COALESCE(AVG(o.total_price), 0) as avg_order_value
        FROM date_range dr
        LEFT JOIN orders o ON DATE(o.created_at) = dr.date
            AND o.status != 'Cancelled'
            AND o.created_at BETWEEN ? AND ?
        GROUP BY dr.date
        ORDER BY dr.date ASC
    ");
    $stmt->execute([$startDate, $endDate, $startDate, $endDate . ' 23:59:59']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRevenueData($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            o.payment_method,
            COUNT(o.id) as order_count,
            SUM(o.total_price) as total_revenue,
            AVG(o.total_price) as avg_value
        FROM orders o
        WHERE o.created_at BETWEEN ? AND ?
        AND o.status != 'Cancelled'
        GROUP BY o.payment_method
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$startDate, $endDate . ' 23:59:59']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductPerformance($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            p.title,
            p.category,
            p.brand,
            p.price,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue,
            COUNT(DISTINCT o.id) as order_count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE o.created_at BETWEEN ? AND ?
        AND o.status != 'Cancelled'
        GROUP BY p.id, p.title, p.category, p.brand, p.price
        ORDER BY total_revenue DESC
        LIMIT 20
    ");
    $stmt->execute([$startDate, $endDate . ' 23:59:59']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategoryAnalysis($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            p.category,
            COUNT(DISTINCT p.id) as product_count,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue,
            AVG(oi.price) as avg_price
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE o.created_at BETWEEN ? AND ?
        AND o.status != 'Cancelled'
        GROUP BY p.category
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$startDate, $endDate . ' 23:59:59']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAnalyticsStats($pdo, $startDate, $endDate) {
    // Total Revenue
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(o.id) as total_orders,
            SUM(o.total_price) as total_revenue,
            AVG(o.total_price) as avg_order_value,
            COUNT(DISTINCT o.user_id) as unique_customers
        FROM orders o
        WHERE o.created_at BETWEEN ? AND ?
        AND o.status != 'Cancelled'
    ");
    $stmt->execute([$startDate, $endDate . ' 23:59:59']);
    $mainStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Top selling product
    $stmt = $pdo->prepare("
        SELECT 
            p.title,
            SUM(oi.quantity) as total_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE o.created_at BETWEEN ? AND ?
        AND o.status != 'Cancelled'
        GROUP BY p.id, p.title
        ORDER BY total_sold DESC
        LIMIT 1
    ");
    $stmt->execute([$startDate, $endDate . ' 23:59:59']);
    $topProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    // Total products sold
    $stmt = $pdo->prepare("
        SELECT SUM(oi.quantity) as total_products_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN ? AND ?
        AND o.status != 'Cancelled'
    ");
    $stmt->execute([$startDate, $endDate . ' 23:59:59']);
    $productStats = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'total_orders' => $mainStats['total_orders'] ?? 0,
        'total_revenue' => $mainStats['total_revenue'] ?? 0,
        'avg_order_value' => $mainStats['avg_order_value'] ?? 0,
        'unique_customers' => $mainStats['unique_customers'] ?? 0,
        'total_products_sold' => $productStats['total_products_sold'] ?? 0,
        'top_product' => $topProduct['title'] ?? 'N/A',
        'top_product_sold' => $topProduct['total_sold'] ?? 0
    ];
}

// Initialize default date range (current month) or from URL parameters
$defaultStartDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$defaultEndDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// Validate URL parameters
if (isset($_GET['start']) && isset($_GET['end'])) {
    try {
        $startDate = new DateTime($_GET['start']);
        $endDate = new DateTime($_GET['end']);

        if ($startDate > $endDate) {
            $defaultStartDate = date('Y-m-01');
            $defaultEndDate = date('Y-m-d');
        }
    } catch (Exception $e) {
        // Invalid date format, use defaults
        $defaultStartDate = date('Y-m-01');
        $defaultEndDate = date('Y-m-d');
    }
}

try {
    $initialStats = getAnalyticsStats($pdo, $defaultStartDate, $defaultEndDate);
} catch (PDOException $e) {
    error_log("Analytics initialization error: " . $e->getMessage());
    $initialStats = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'avg_order_value' => 0,
        'unique_customers' => 0,
        'total_products_sold' => 0,
        'top_product' => 'N/A',
        'top_product_sold' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - ShoeStore Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/inventory.css">
    
    <style>
        /* Custom analytics styling */
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }
        
        .date-picker-container {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #dee2e6;
        }
        
        .chart-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .chart-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
            }
            
            .date-picker-container {
                text-align: center;
            }
            
            .date-picker-container .row > div {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Header -->
            <div class="admin-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h4 class="mb-0">Analytics Dashboard</h4>
                        <small class="text-muted">Sales data, revenue, and performance reports</small>
                    </div>
                </div>
                <div class="header-right">
                    <div class="realtime-indicator">
                        <span class="realtime-dot"></span>
                        <small>Live Data</small>
                    </div>
                    <button type="button" class="btn-admin btn-admin-success" onclick="refreshAllCharts()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Analytics Content -->
            <div class="admin-content-wrapper">
                <div class="content-container">
                    <!-- Date Range Picker -->
                    <div class="date-picker-container">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h6 class="mb-2 mb-md-0">
                                    <i class="fas fa-calendar-alt me-2"></i>Date Range Filter
                                </h6>
                            </div>
                            <div class="col-md-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate" value="<?php echo $defaultStartDate; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="endDate" value="<?php echo $defaultEndDate; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">&nbsp;</label>
                                <button type="button" class="btn btn-primary w-100" onclick="updateAllCharts()">
                                    <i class="fas fa-filter me-1"></i>Apply Filter
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Dashboard -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-shopping-cart fa-2x text-primary"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="totalOrders"><?php echo number_format($initialStats['total_orders']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Total Orders</p>
                                            <small class="text-white-50">Completed orders</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-dollar-sign fa-2x text-success"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="totalRevenue">₱<?php echo number_format($initialStats['total_revenue'], 2); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Total Revenue</p>
                                            <small class="text-white-50">Sales income</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-chart-line fa-2x text-warning"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="avgOrderValue">₱<?php echo number_format($initialStats['avg_order_value'], 2); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Avg Order</p>
                                            <small class="text-white-50">Average value</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-users fa-2x text-info"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="uniqueCustomers"><?php echo number_format($initialStats['unique_customers']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Customers</p>
                                            <small class="text-white-50">Unique buyers</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-box fa-2x text-danger"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="totalProductsSold"><?php echo number_format($initialStats['total_products_sold']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Products Sold</p>
                                            <small class="text-white-50">Total quantity</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-star fa-2x text-warning"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="topProductSold"><?php echo number_format($initialStats['top_product_sold']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Top Product</p>
                                            <small class="text-white-50" id="topProductName"><?php echo htmlspecialchars(substr($initialStats['top_product'], 0, 15)); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row">
                        <!-- Sales Trend Chart -->
                        <div class="col-lg-8 mb-4">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2 text-primary"></i>Sales Trend Analysis
                                    </h5>
                                    <div class="loading-spinner" id="salesChartSpinner">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="salesTrendChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue by Payment Method -->
                        <div class="col-lg-4 mb-4">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-credit-card me-2 text-success"></i>Revenue by Payment
                                    </h5>
                                    <div class="loading-spinner" id="revenueChartSpinner">
                                        <div class="spinner-border spinner-border-sm text-success" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Category Performance -->
                        <div class="col-lg-6 mb-4">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-tags me-2 text-warning"></i>Category Performance
                                    </h5>
                                    <div class="loading-spinner" id="categoryChartSpinner">
                                        <div class="spinner-border spinner-border-sm text-warning" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Top Products -->
                        <div class="col-lg-6 mb-4">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-trophy me-2 text-danger"></i>Top Performing Products
                                    </h5>
                                    <div class="loading-spinner" id="productChartSpinner">
                                        <div class="spinner-border spinner-border-sm text-danger" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="productChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Analytics Table -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h5 class="mb-0">
                                <i class="fas fa-table me-2 text-info"></i>Product Performance Details
                            </h5>
                            <div class="loading-spinner" id="tableSpinner">
                                <div class="spinner-border spinner-border-sm text-info" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Price</th>
                                        <th>Quantity Sold</th>
                                        <th>Revenue</th>
                                        <th>Orders</th>
                                    </tr>
                                </thead>
                                <tbody id="productTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                                            Loading product performance data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Admin JS -->
    <script src="assets/js/admin.js"></script>

    <script>
        // Global chart instances
        let salesTrendChart = null;
        let revenueChart = null;
        let categoryChart = null;
        let productChart = null;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            loadAllData();

            // Auto-refresh every 5 minutes
            setInterval(() => {
                refreshAllCharts();
            }, 300000);
        });

        function initializeCharts() {
            // Sales Trend Chart (Line Chart)
            const salesCtx = document.getElementById('salesTrendChart').getContext('2d');
            salesTrendChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Daily Revenue (₱)',
                        data: [],
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#0d6efd',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        spanGaps: false
                    }, {
                        label: 'Orders Count',
                        data: [],
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        yAxisID: 'y1',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#198754',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        spanGaps: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day',
                                displayFormats: {
                                    day: 'MMM dd'
                                },
                                tooltipFormat: 'MMM dd, yyyy'
                            },
                            title: {
                                display: true,
                                text: 'Date'
                            },
                            ticks: {
                                source: 'data',
                                autoSkip: false,
                                maxTicksLimit: 10
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue (₱)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            },
                            grid: {
                                display: true,
                                drawBorder: true,
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Orders'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Sales Performance Over Time'
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                title: function(context) {
                                    const date = new Date(context[0].parsed.x);
                                    return date.toLocaleDateString('en-US', {
                                        weekday: 'long',
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    });
                                },
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y;

                                    if (context.datasetIndex === 0) {
                                        // Revenue dataset
                                        return `${label}: ₱${value.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                                    } else {
                                        // Orders dataset
                                        return `${label}: ${value} ${value === 1 ? 'order' : 'orders'}`;
                                    }
                                },
                                footer: function(context) {
                                    if (context.length > 1 && context[0].parsed.y > 0 && context[1].parsed.y > 0) {
                                        const revenue = context[0].parsed.y;
                                        const orders = context[1].parsed.y;
                                        const avgOrderValue = revenue / orders;
                                        return `Average Order Value: ₱${avgOrderValue.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                                    }
                                    return '';
                                }
                            }
                        }
                    }
                }
            });

            // Revenue Chart (Doughnut Chart)
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            revenueChart = new Chart(revenueCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#0d6efd',
                            '#198754',
                            '#ffc107',
                            '#dc3545',
                            '#6f42c1',
                            '#fd7e14'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Revenue Distribution'
                        },
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Category Chart (Bar Chart)
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            categoryChart = new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: [],
                        backgroundColor: 'rgba(255, 193, 7, 0.8)',
                        borderColor: '#ffc107',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue (₱)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Category'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Revenue by Product Category'
                        },
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Product Chart (Horizontal Bar Chart)
            const productCtx = document.getElementById('productChart').getContext('2d');
            productChart = new Chart(productCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Quantity Sold',
                        data: [],
                        backgroundColor: 'rgba(220, 53, 69, 0.8)',
                        borderColor: '#dc3545',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantity Sold'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Products'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Top 10 Best Selling Products'
                        },
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Data loading functions
        async function loadAllData() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            await Promise.all([
                loadSalesData(startDate, endDate),
                loadRevenueData(startDate, endDate),
                loadCategoryData(startDate, endDate),
                loadProductData(startDate, endDate),
                loadAnalyticsStats(startDate, endDate)
            ]);
        }

        async function loadSalesData(startDate, endDate) {
            const spinner = document.getElementById('salesChartSpinner');
            spinner.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('action', 'get_sales_data');
                formData.append('start_date', startDate);
                formData.append('end_date', endDate);

                const response = await fetch('analytics.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const data = result.data;

                    // Data should already be complete from the SQL query, but ensure proper formatting
                    const chartData = data.map(item => ({
                        x: item.date,
                        y: parseFloat(item.daily_revenue || 0)
                    }));

                    const orderData = data.map(item => ({
                        x: item.date,
                        y: parseInt(item.orders_count || 0)
                    }));

                    // Update chart data with precise date-value pairs
                    salesTrendChart.data.datasets[0].data = chartData;
                    salesTrendChart.data.datasets[1].data = orderData;

                    // Update chart options for precise date range
                    salesTrendChart.options.scales.x.min = startDate;
                    salesTrendChart.options.scales.x.max = endDate;

                    // Force chart to show all data points
                    salesTrendChart.options.scales.x.ticks.maxTicksLimit = Math.min(data.length, 15);

                    salesTrendChart.update('active');
                } else {
                    showAlert('error', result.message || 'Failed to load sales data');
                }
            } catch (error) {
                console.error('Error loading sales data:', error);
                showAlert('error', 'Failed to load sales data');
            } finally {
                spinner.style.display = 'none';
            }
        }

        async function loadRevenueData(startDate, endDate) {
            const spinner = document.getElementById('revenueChartSpinner');
            spinner.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('action', 'get_revenue_data');
                formData.append('start_date', startDate);
                formData.append('end_date', endDate);

                const response = await fetch('analytics.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const data = result.data;

                    revenueChart.data.labels = data.map(item => formatPaymentMethod(item.payment_method));
                    revenueChart.data.datasets[0].data = data.map(item => parseFloat(item.total_revenue));
                    revenueChart.update();
                } else {
                    showAlert('error', result.message || 'Failed to load revenue data');
                }
            } catch (error) {
                console.error('Error loading revenue data:', error);
                showAlert('error', 'Failed to load revenue data');
            } finally {
                spinner.style.display = 'none';
            }
        }

        async function loadCategoryData(startDate, endDate) {
            const spinner = document.getElementById('categoryChartSpinner');
            spinner.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('action', 'get_category_analysis');
                formData.append('start_date', startDate);
                formData.append('end_date', endDate);

                const response = await fetch('analytics.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const data = result.data;

                    categoryChart.data.labels = data.map(item => item.category);
                    categoryChart.data.datasets[0].data = data.map(item => parseFloat(item.total_revenue));
                    categoryChart.update();
                } else {
                    showAlert('error', result.message || 'Failed to load category data');
                }
            } catch (error) {
                console.error('Error loading category data:', error);
                showAlert('error', 'Failed to load category data');
            } finally {
                spinner.style.display = 'none';
            }
        }

        async function loadProductData(startDate, endDate) {
            const spinner = document.getElementById('productChartSpinner');
            const tableSpinner = document.getElementById('tableSpinner');
            spinner.style.display = 'block';
            tableSpinner.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('action', 'get_product_performance');
                formData.append('start_date', startDate);
                formData.append('end_date', endDate);

                const response = await fetch('analytics.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const data = result.data;

                    // Update chart (top 10)
                    const top10 = data.slice(0, 10);
                    productChart.data.labels = top10.map(item => truncateText(item.title, 20));
                    productChart.data.datasets[0].data = top10.map(item => parseInt(item.total_sold));
                    productChart.update();

                    // Update table
                    updateProductTable(data);
                } else {
                    showAlert('error', result.message || 'Failed to load product data');
                }
            } catch (error) {
                console.error('Error loading product data:', error);
                showAlert('error', 'Failed to load product data');
            } finally {
                spinner.style.display = 'none';
                tableSpinner.style.display = 'none';
            }
        }

        async function loadAnalyticsStats(startDate, endDate) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_analytics_stats');
                formData.append('start_date', startDate);
                formData.append('end_date', endDate);

                const response = await fetch('analytics.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const stats = result.stats;

                    document.getElementById('totalOrders').textContent = numberFormat(stats.total_orders);
                    document.getElementById('totalRevenue').textContent = '₱' + numberFormat(stats.total_revenue, 2);
                    document.getElementById('avgOrderValue').textContent = '₱' + numberFormat(stats.avg_order_value, 2);
                    document.getElementById('uniqueCustomers').textContent = numberFormat(stats.unique_customers);
                    document.getElementById('totalProductsSold').textContent = numberFormat(stats.total_products_sold);
                    document.getElementById('topProductSold').textContent = numberFormat(stats.top_product_sold);
                    document.getElementById('topProductName').textContent = truncateText(stats.top_product, 15);
                } else {
                    showAlert('error', result.message || 'Failed to load analytics stats');
                }
            } catch (error) {
                console.error('Error loading analytics stats:', error);
                showAlert('error', 'Failed to load analytics stats');
            }
        }

        // Utility functions
        function generateCompleteDateRange(startDate, endDate, data) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const completeData = [];

            // Create a map of existing data for quick lookup
            const dataMap = {};
            data.forEach(item => {
                dataMap[item.date] = item;
            });

            // Generate all dates in the range
            const currentDate = new Date(start);
            while (currentDate <= end) {
                const dateStr = currentDate.toISOString().split('T')[0];

                if (dataMap[dateStr]) {
                    // Use existing data
                    completeData.push(dataMap[dateStr]);
                } else {
                    // Add zero values for missing dates
                    completeData.push({
                        date: dateStr,
                        orders_count: 0,
                        daily_revenue: 0,
                        avg_order_value: 0
                    });
                }

                currentDate.setDate(currentDate.getDate() + 1);
            }

            return completeData;
        }

        function updateProductTable(data) {
            const tableBody = document.getElementById('productTableBody');

            if (data.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                            No product data found for the selected date range
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = data.map(product => `
                <tr>
                    <td>
                        <div class="fw-medium">${escapeHtml(product.title)}</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">${escapeHtml(product.category)}</span>
                    </td>
                    <td>${escapeHtml(product.brand)}</td>
                    <td>₱${numberFormat(product.price, 2)}</td>
                    <td>
                        <span class="badge bg-primary">${numberFormat(product.total_sold)}</span>
                    </td>
                    <td class="fw-medium text-success">₱${numberFormat(product.total_revenue, 2)}</td>
                    <td>
                        <span class="badge bg-info">${numberFormat(product.order_count)}</span>
                    </td>
                </tr>
            `).join('');
        }

        function formatPaymentMethod(method) {
            const methods = {
                'cod': 'Cash on Delivery',
                'bank_transfer': 'Bank Transfer',
                'credit_card': 'Credit Card',
                'debit_card': 'Debit Card',
                'paypal': 'PayPal',
                'gcash': 'GCash'
            };
            return methods[method] || method.replace('_', ' ').toUpperCase();
        }

        function truncateText(text, maxLength) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function numberFormat(num, decimals = 0) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(num);
        }

        function showAlert(type, message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert-floating');
            existingAlerts.forEach(alert => alert.remove());

            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show alert-floating`;
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;

            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alertDiv);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Date validation and formatting
        function validateAndFormatDates() {
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');

            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            if (!startDate || !endDate) {
                showAlert('error', 'Please select both start and end dates');
                return null;
            }

            const start = new Date(startDate);
            const end = new Date(endDate);

            if (start > end) {
                showAlert('error', 'Start date cannot be after end date');
                return null;
            }

            // Check if date range is too large (more than 1 year)
            const daysDiff = (end - start) / (1000 * 60 * 60 * 24);
            if (daysDiff > 365) {
                showAlert('warning', 'Date range is very large. This may affect performance.');
            }

            return {
                startDate: formatDateForSQL(start),
                endDate: formatDateForSQL(end),
                daysDiff: daysDiff
            };
        }

        function formatDateForSQL(date) {
            return date.toISOString().split('T')[0];
        }

        function formatDateForDisplay(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }

        // Event handlers
        function updateAllCharts() {
            const dateValidation = validateAndFormatDates();
            if (!dateValidation) {
                return;
            }

            // Update the date inputs to ensure consistency
            document.getElementById('startDate').value = dateValidation.startDate;
            document.getElementById('endDate').value = dateValidation.endDate;

            // Show loading message for large date ranges
            if (dateValidation.daysDiff > 90) {
                showAlert('info', `Loading data for ${Math.ceil(dateValidation.daysDiff)} days. This may take a moment...`);
            }

            loadAllData();
        }

        function refreshAllCharts() {
            loadAllData();
            showAlert('success', 'Analytics data refreshed successfully');
        }

        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && overlay) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshAllCharts();
            }
        });
    </script>
</body>
</html>
