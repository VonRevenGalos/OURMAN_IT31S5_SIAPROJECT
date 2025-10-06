<?php
/**
 * Admin Dashboard
 * Secure admin dashboard with proper authentication
 */

// Include admin authentication
require_once __DIR__ . '/includes/admin_auth.php';

// Require admin login
requireAdminLogin();

// Validate admin session
if (!validateAdminSession()) {
    $_SESSION['admin_login_error'] = "Your session is invalid. Please log in again.";
    header("Location: adminlogin.php");
    exit();
}

// Get current admin user data
$currentUser = getCurrentAdminUser();
if (!$currentUser) {
    $_SESSION['admin_login_error'] = "Unable to load user data. Please log in again.";
    header("Location: adminlogin.php");
    exit();
}

// Include audit logger and log page access
require_once 'includes/audit_logger.php';
auditPageView('dashboard.php');

// Get dashboard statistics
$stats = getAdminDashboardStats();
$totalUsers = $stats['total_users'];
$totalProducts = $stats['total_products'];
$totalOrders = $stats['total_orders'];
$totalRevenue = $stats['total_revenue'];
// Get additional dashboard data
try {


    // Weekly sales data for dynamic chart (last 12 weeks)
    $stmt = $pdo->query("
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
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
        GROUP BY YEARWEEK(created_at, 1)
        ORDER BY week_year
    ");
    $weeklySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly sales data for comparison
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
    $monthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);





    // Recent activity summary
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

} catch (PDOException $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    $totalUsers = $totalProducts = $totalOrders = $totalRevenue = 0;
    $recentOrders = $monthlySales = $weeklySales = $orderStatus = $topProducts = [];
    $activitySummary = ['today_orders' => 0, 'yesterday_orders' => 0, 'week_orders' => 0, 'today_revenue' => 0, 'week_revenue' => 0];
}

$pageTitle = "Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts - Inter (Professional & Readable) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <!-- Admin Layout Wrapper -->
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="admin-content">
            <!-- Header -->
            <div class="admin-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h4 class="mb-0">Dashboard Overview</h4>
                        <small class="text-muted d-none d-md-block">Real-time business insights and analytics</small>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-stats d-none d-xl-flex">
                        <div class="stat-item">
                            <small class="text-muted">Today</small>
                            <strong><?php echo $activitySummary['today_orders']; ?> orders</strong>
                        </div>
                        <div class="stat-item">
                            <small class="text-muted">This Week</small>
                            <strong>₱<?php echo number_format($activitySummary['week_revenue'], 0); ?></strong>
                        </div>
                    </div>
                    <div class="real-time-indicator">
                        <span class="status-dot"></span>
                        <small class="last-refresh">Live</small>
                    </div>
                    <button class="btn btn-admin-secondary btn-sm" onclick="refreshDashboard()" title="Refresh Dashboard">
                        <i class="fas fa-sync-alt" id="refreshIcon"></i>
                        <span class="d-none d-sm-inline ms-1">Refresh</span>
                    </button>
                    <div class="user-info">
                        <span class="text-muted d-none d-sm-inline">Welcome back,</span>
                        <strong><?php echo htmlspecialchars($currentUser['first_name']); ?>!</strong>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="admin-content-wrapper">
                <div class="content-container">

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="products.php" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Add Product</h6>
                            <small>Create new product</small>
                        </div>
                    </a>
                    <a href="orders.php" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>View Orders</h6>
                            <small>Manage all orders</small>
                        </div>
                    </a>
                    <a href="users.php" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Manage Users</h6>
                            <small>User administration</small>
                        </div>
                    </a>
                    <a href="livechat.php" class="quick-action-btn">
                        <div class="quick-action-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="quick-action-content">
                            <h6>Live Chat</h6>
                            <small>Customer support</small>
                        </div>
                    </a>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="admin-card stat-card stat-primary">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number" data-counter="<?php echo $totalUsers; ?>">0</h3>
                                    <p class="stat-label">Total Users</p>
                                    <div class="stat-change">
                                        <small class="text-success">
                                            <i class="fas fa-arrow-up"></i> Active community
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="admin-card stat-card stat-success">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number" data-counter="<?php echo $totalProducts; ?>">0</h3>
                                    <p class="stat-label">Total Products</p>
                                    <div class="stat-change">
                                        <small class="text-info">
                                            <i class="fas fa-plus"></i> Inventory ready
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="admin-card stat-card stat-warning">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number" data-counter="<?php echo $totalOrders; ?>">0</h3>
                                    <p class="stat-label">Total Orders</p>
                                    <div class="stat-change">
                                        <small class="text-primary">
                                            <i class="fas fa-clock"></i> <?php echo $activitySummary['today_orders']; ?> today
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="admin-card stat-card stat-info">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="fas fa-peso-sign"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number" data-counter="<?php echo $totalRevenue; ?>">₱0</h3>
                                    <p class="stat-label">Total Revenue</p>
                                    <div class="stat-change">
                                        <small class="text-success">
                                            <i class="fas fa-chart-line"></i> ₱<?php echo number_format($activitySummary['today_revenue'], 0); ?> today
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-xl-8">
                        <div class="admin-card chart-card">
                            <div class="card-header">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                                    <div class="chart-title-section">
                                        <h5 class="card-title mb-1">
                                            Sales Analytics
                                            <span class="live-indicator ms-2">
                                                <i class="fas fa-circle text-success" style="font-size: 8px;"></i>
                                                <small class="text-muted">Live</small>
                                            </span>
                                        </h5>
                                        <small class="text-muted">Auto-refreshes every 2 minutes - Real-time data from database</small>
                                    </div>
                                    <div class="chart-controls mt-2 mt-md-0">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="chartPeriod" id="weeklyView" value="weekly" checked>
                                            <label class="btn btn-outline-primary" for="weeklyView">Weekly</label>

                                            <input type="radio" class="btn-check" name="chartPeriod" id="monthlyView" value="monthly">
                                            <label class="btn btn-outline-primary" for="monthlyView">Monthly</label>
                                        </div>
                                        <div class="date-filter ms-2 d-none d-md-inline-block">
                                            <select class="form-select form-select-sm" id="weekFilter" style="width: auto;">
                                                <option value="all">All Weeks</option>
                                                <option value="last4">Last 4 Weeks</option>
                                                <option value="last8">Last 8 Weeks</option>
                                                <option value="last12">Last 12 Weeks</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesChart"></canvas>
                                </div>
                                <div class="chart-stats mt-3">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="chart-stat-item">
                                                <h6 class="mb-1" id="totalOrdersChart">0</h6>
                                                <small class="text-muted">Total Orders</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="chart-stat-item">
                                                <h6 class="mb-1" id="totalRevenueChart">₱0</h6>
                                                <small class="text-muted">Total Revenue</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="chart-stat-item">
                                                <h6 class="mb-1" id="avgOrderChart">₱0</h6>
                                                <small class="text-muted">Avg Order</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
                



                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Dashboard Charts -->
    <script>
        // Chart data
        const weeklySalesData = <?php echo json_encode($weeklySales); ?>;
        const monthlySalesData = <?php echo json_encode($monthlySales); ?>;

        // Initialize charts
        let salesChart;

        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');

        function initSalesChart(period = 'weekly', filter = 'all') {
            if (salesChart) {
                salesChart.destroy();
            }

            let data, labels, revenues, orders;

            if (period === 'weekly') {
                data = filterWeeklyData(weeklySalesData, filter);
                labels = data.map(item => {
                    const date = new Date(item.week_start);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                revenues = data.map(item => parseFloat(item.revenue) || 0);
                orders = data.map(item => parseInt(item.order_count) || 0);
            } else {
                data = monthlySalesData;
                labels = data.map(item => {
                    const [year, month] = item.month.split('-');
                    const date = new Date(year, month - 1);
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                });
                revenues = data.map(item => parseFloat(item.revenue) || 0);
                orders = data.map(item => parseInt(item.order_count) || 0);
            }

            salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: revenues,
                        borderColor: '#000000',
                        backgroundColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#000000',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }, {
                        label: 'Orders',
                        data: orders,
                        borderColor: '#666666',
                        backgroundColor: 'rgba(102, 102, 102, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointBackgroundColor: '#666666',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: 'Inter',
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#333333',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    if (context.datasetIndex === 0) {
                                        return 'Revenue: ₱' + context.parsed.y.toLocaleString();
                                    } else {
                                        return 'Orders: ' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Inter',
                                    size: 11
                                }
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                },
                                font: {
                                    family: 'Inter',
                                    size: 11
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                },
                                font: {
                                    family: 'Inter',
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            // Update chart stats
            updateChartStats(data);
        }

        function filterWeeklyData(data, filter) {
            if (filter === 'all') return data;

            const weeks = parseInt(filter.replace('last', ''));
            return data.slice(-weeks);
        }

        function updateChartStats(data) {
            const totalOrders = data.reduce((sum, item) => sum + parseInt(item.order_count || 0), 0);
            const totalRevenue = data.reduce((sum, item) => sum + parseFloat(item.revenue || 0), 0);
            const avgOrder = totalOrders > 0 ? totalRevenue / totalOrders : 0;

            document.getElementById('totalOrdersChart').textContent = totalOrders.toLocaleString();
            document.getElementById('totalRevenueChart').textContent = '₱' + totalRevenue.toLocaleString();
            document.getElementById('avgOrderChart').textContent = '₱' + avgOrder.toLocaleString();
        }
        


        // Real-time data management
        let lastUpdateTime = 0;
        let refreshInterval;
        let isPageVisible = true;

        // Auto-refresh configuration
        const REFRESH_INTERVALS = {
            stats: 30000,      // 30 seconds
            orders: 15000,     // 15 seconds
            products: 60000,   // 1 minute
            charts: 120000     // 2 minutes
        };

        // Update dashboard data
        async function updateDashboardData(type = 'all') {
            try {
                const response = await fetch(`api/dashboard_data.php?type=${type}&last_update=${lastUpdateTime}`);
                const data = await response.json();

                if (data.success) {
                    lastUpdateTime = data.timestamp;

                    // Update statistics
                    if (data.stats) {
                        updateStatistics(data.stats);
                    }

                    // Update recent orders
                    if (data.recent_orders) {
                        updateRecentOrders(data.recent_orders);
                    }

                    // Update top products
                    if (data.top_products) {
                        updateTopProducts(data.top_products);
                    }

                    // Update charts
                    if (data.charts) {
                        updateCharts(data.charts);
                    }

                    // Update notifications
                    if (data.notifications) {
                        updateNotifications(data.notifications);
                    }

                    // Update last refresh indicator
                    updateLastRefreshTime();
                }
            } catch (error) {
                console.error('Error updating dashboard data:', error);
            }
        }

        // Update statistics cards
        function updateStatistics(stats) {
            // Update stat numbers with animation
            animateNumber('stat-users', stats.total_users);
            animateNumber('stat-products', stats.total_products);
            animateNumber('stat-orders', stats.total_orders);
            animateNumber('stat-revenue', stats.total_revenue, '₱');

            // Update header stats
            const todayOrdersElement = document.querySelector('.header-stats .stat-item:first-child strong');
            const weekRevenueElement = document.querySelector('.header-stats .stat-item:last-child strong');

            if (todayOrdersElement) {
                todayOrdersElement.textContent = `${stats.activity.today_orders} orders`;
            }
            if (weekRevenueElement) {
                weekRevenueElement.textContent = `₱${parseInt(stats.activity.week_revenue).toLocaleString()}`;
            }
        }

        // Animate number changes
        function animateNumber(elementClass, newValue, prefix = '') {
            const elements = document.querySelectorAll(`.${elementClass}`);
            elements.forEach(element => {
                const currentValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
                if (currentValue !== newValue) {
                    element.style.transform = 'scale(1.1)';
                    element.style.color = '#28a745';

                    setTimeout(() => {
                        element.textContent = prefix + parseInt(newValue).toLocaleString();
                        element.style.transform = 'scale(1)';
                        element.style.color = '';
                    }, 200);
                }
            });
        }

        // Counter Animation
        function animateCounters() {
            const counters = document.querySelectorAll('[data-counter]');

            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-counter'));
                const isRevenue = counter.textContent.includes('₱');
                let current = 0;
                const increment = target / 100;
                const duration = 2000; // 2 seconds
                const stepTime = duration / 100;

                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }

                    if (isRevenue) {
                        counter.textContent = '₱' + Math.floor(current).toLocaleString();
                    } else {
                        counter.textContent = Math.floor(current).toLocaleString();
                    }
                }, stepTime);
            });
        }

        // Manual refresh function
        function refreshDashboard() {
            const refreshIcon = document.getElementById('refreshIcon');
            refreshIcon.classList.add('fa-spin');

            // Update all dashboard data
            updateDashboardData('all').then(() => {
                // Re-animate counters
                animateCounters();

                // Stop spinning icon
                setTimeout(() => {
                    refreshIcon.classList.remove('fa-spin');
                }, 1000);

                // Update last refresh time
                updateLastRefreshTime();
            }).catch(() => {
                refreshIcon.classList.remove('fa-spin');
            });
        }

        // Mobile sidebar functions
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if (sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            } else {
                sidebar.classList.add('show');
                overlay.classList.add('show');
            }
        }

        // Close sidebar when clicking overlay
        document.addEventListener('click', function(e) {
            if (e.target.id === 'sidebarOverlay') {
                toggleMobileSidebar();
            }
        });

        // Auto-hide sidebar on mobile when clicking nav links
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 991.98 && e.target.closest('.sidebar-nav a')) {
                const sidebar = document.getElementById('adminSidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if (sidebar.classList.contains('show')) {
                    setTimeout(() => {
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                    }, 150);
                }
            }
        });

        // Event handlers
        document.addEventListener('DOMContentLoaded', function() {
            // Animate counters on page load
            setTimeout(animateCounters, 500);

            // Initialize charts
            initSalesChart('weekly', 'all');

            // Start real-time updates
            startRealTimeUpdates();

            // Chart period toggle
            document.querySelectorAll('input[name="chartPeriod"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const period = this.value;
                    const filter = document.getElementById('weekFilter').value;
                    initSalesChart(period, filter);

                    // Show/hide week filter
                    const weekFilterContainer = document.querySelector('.date-filter');
                    if (period === 'weekly') {
                        weekFilterContainer.style.display = 'inline-block';
                    } else {
                        weekFilterContainer.style.display = 'none';
                    }
                });
            });

            // Week filter change
            document.getElementById('weekFilter').addEventListener('change', function() {
                const period = document.querySelector('input[name="chartPeriod"]:checked').value;
                const filter = this.value;
                if (period === 'weekly') {
                    initSalesChart(period, filter);
                }
            });
        });

        // Status color mapping for legend
        const statusColors = {
            'pending': '#000000',
            'shipped': '#333333',
            'delivered': '#666666',
            'cancelled': '#999999'
        };

        // Update recent orders table
        function updateRecentOrders(orders) {
            const tbody = document.querySelector('#recent-orders-table tbody');
            if (!tbody) return;

            tbody.innerHTML = '';
            orders.forEach(order => {
                const row = document.createElement('tr');
                row.className = 'order-row';
                row.innerHTML = `
                    <td>
                        <div class="customer-info">
                            <div class="customer-name">${order.first_name} ${order.last_name}</div>
                            <small class="text-muted">${order.email}</small>
                        </div>
                    </td>
                    <td>
                        <span class="order-id">#${order.id}</span>
                    </td>
                    <td>
                        <span class="amount">₱${parseFloat(order.total_price).toLocaleString()}</span>
                    </td>
                    <td>
                        <span class="status-badge status-${order.status.toLowerCase()}">${order.status}</span>
                    </td>
                    <td>
                        <small class="text-muted">${new Date(order.created_at).toLocaleDateString()}</small>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Update top products list
        function updateTopProducts(products) {
            const container = document.querySelector('.top-products-list');
            if (!container) return;

            container.innerHTML = '';
            products.forEach((product, index) => {
                const item = document.createElement('div');
                item.className = 'top-product-item';
                item.innerHTML = `
                    <div class="product-rank rank-${index + 1}">${index + 1}</div>
                    <div class="product-image">
                        ${product.image_url ?
                            `<img src="${product.image_url}" alt="${product.title}" class="product-thumb">` :
                            `<div class="product-placeholder"><i class="fas fa-image"></i></div>`
                        }
                    </div>
                    <div class="product-info">
                        <div class="product-name">${product.title}</div>
                        <div class="product-meta">
                            <span class="product-price">₱${parseFloat(product.price).toLocaleString()}</span>
                            <span class="product-category">${product.category}</span>
                        </div>
                    </div>
                    <div class="product-sales">
                        <div class="sales-count">${parseInt(product.total_sold).toLocaleString()}</div>
                        <div class="sales-revenue">
                            <small>₱${parseFloat(product.total_revenue).toLocaleString()}</small>
                        </div>
                    </div>
                `;
                container.appendChild(item);
            });
        }

        // Update charts with new data
        function updateCharts(chartData) {
            if (chartData.weekly_sales && salesChart) {
                const period = document.querySelector('input[name="chartPeriod"]:checked').value;
                const filter = document.getElementById('weekFilter').value;

                if (period === 'weekly') {
                    // Update weekly sales chart
                    const data = filterWeeklyData(chartData.weekly_sales, filter);
                    const labels = data.map(item => {
                        const date = new Date(item.week_start);
                        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    });
                    const revenues = data.map(item => parseFloat(item.revenue) || 0);
                    const orders = data.map(item => parseInt(item.order_count) || 0);

                    salesChart.data.labels = labels;
                    salesChart.data.datasets[0].data = revenues;
                    salesChart.data.datasets[1].data = orders;
                    salesChart.update('none');

                    updateChartStats(data);
                }
            }


        }

        // Update notifications
        function updateNotifications(notifications) {
            // Update low stock alert
            const lowStockBadge = document.querySelector('.low-stock-badge');
            if (lowStockBadge && notifications.low_stock_products) {
                const count = notifications.low_stock_products.length;
                lowStockBadge.textContent = count;
                lowStockBadge.style.display = count > 0 ? 'inline-block' : 'none';
            }

            // Update pending orders badge
            const pendingBadge = document.querySelector('.pending-orders-badge');
            if (pendingBadge) {
                pendingBadge.textContent = notifications.pending_orders;
                pendingBadge.style.display = notifications.pending_orders > 0 ? 'inline-block' : 'none';
            }
        }

        // Start real-time updates
        function startRealTimeUpdates() {
            // Initial load
            updateDashboardData('all');

            // Set up different refresh intervals for different data types
            setInterval(() => updateDashboardData('stats'), REFRESH_INTERVALS.stats);
            setInterval(() => updateDashboardData('orders'), REFRESH_INTERVALS.orders);
            setInterval(() => updateDashboardData('products'), REFRESH_INTERVALS.products);
            setInterval(() => updateDashboardData('charts'), REFRESH_INTERVALS.charts);
        }

        // Update last refresh time indicator
        function updateLastRefreshTime() {
            const indicator = document.querySelector('.last-refresh');
            if (indicator) {
                const now = new Date();
                indicator.textContent = `Last updated: ${now.toLocaleTimeString()}`;
            }
        }

        // Page visibility handling
        document.addEventListener('visibilitychange', function() {
            isPageVisible = !document.hidden;
            if (isPageVisible) {
                // Refresh data when page becomes visible
                updateDashboardData('all');
            }
        });

        // Apply status colors to legend
        document.querySelectorAll('.status-color').forEach(element => {
            const status = element.getAttribute('data-status');
            if (statusColors[status]) {
                element.style.backgroundColor = statusColors[status];
                element.style.width = '12px';
                element.style.height = '12px';
                element.style.borderRadius = '50%';
                element.style.display = 'inline-block';
            }
        });

        // Mobile sidebar functions
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if (sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            } else {
                sidebar.classList.add('show');
                overlay.classList.add('show');
            }
        }

        // Close sidebar when clicking overlay
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            toggleMobileSidebar();
        });
    </script>
</body>
</html>
