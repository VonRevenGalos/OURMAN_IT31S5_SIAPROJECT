<?php
/**
 * Admin Orders Management Page
 * Comprehensive order management with filtering, bulk operations, and invoice printing
 */

// Include admin authentication
require_once 'includes/admin_auth.php';

// Require admin login
requireAdminLogin();

// Get current admin user info
$currentUser = getCurrentAdminUser();

// Database connection
require_once '../db.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

// Validate sort column to prevent SQL injection
$allowedSortColumns = ['created_at', 'id', 'total_price', 'status', 'payment_method'];
if (!in_array($sort, $allowedSortColumns)) {
    $sort = 'created_at';
}

// Build WHERE clause for filtering
$whereConditions = [];
$params = [];

if (!empty($status_filter)) {
    $whereConditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($date_from)) {
    $whereConditions[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $whereConditions[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

if (!empty($payment_method)) {
    $whereConditions[] = "o.payment_method = ?";
    $params[] = $payment_method;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*)
        FROM orders o
        JOIN users u ON o.user_id = u.id
        $whereClause
    ";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetchColumn();
    
    // Get orders with pagination
    $query = "
        SELECT o.*, u.first_name, u.last_name, u.email, ua.full_name as shipping_name,
               ua.address_line1, ua.city, ua.state, ua.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
        $whereClause
        ORDER BY o.$sort $order
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order statistics
    $statsQuery = "
        SELECT
            COUNT(*) as total_orders,
            COALESCE(SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END), 0) as pending_orders,
            COALESCE(SUM(CASE WHEN status = 'Shipped' THEN 1 ELSE 0 END), 0) as shipped_orders,
            COALESCE(SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END), 0) as delivered_orders,
            COALESCE(SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END), 0) as cancelled_orders,
            COALESCE(SUM(total_price), 0) as total_revenue,
            COALESCE(AVG(total_price), 0) as avg_order_value
        FROM orders o
        JOIN users u ON o.user_id = u.id
        $whereClause
    ";
    
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Ensure all stats values are numeric (handle null values)
    $stats = [
        'total_orders' => (int)($stats['total_orders'] ?? 0),
        'pending_orders' => (int)($stats['pending_orders'] ?? 0),
        'shipped_orders' => (int)($stats['shipped_orders'] ?? 0),
        'delivered_orders' => (int)($stats['delivered_orders'] ?? 0),
        'cancelled_orders' => (int)($stats['cancelled_orders'] ?? 0),
        'total_revenue' => (float)($stats['total_revenue'] ?? 0),
        'avg_order_value' => (float)($stats['avg_order_value'] ?? 0)
    ];
    
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
    $totalOrders = 0;
    $stats = [
        'total_orders' => 0,
        'pending_orders' => 0,
        'shipped_orders' => 0,
        'delivered_orders' => 0,
        'cancelled_orders' => 0,
        'total_revenue' => 0,
        'avg_order_value' => 0
    ];
}

$totalPages = ceil($totalOrders / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - ShoeStore Admin</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/orders.css">
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
                        <h4 class="mb-0">Orders Management</h4>
                        <small class="text-muted d-none d-md-block">Manage customer orders, update status, and generate invoices</small>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-stats d-none d-xl-flex">
                        <div class="stat-item">
                            <small class="text-muted">Total Orders</small>
                            <strong><?php echo number_format((int)$stats['total_orders']); ?></strong>
                        </div>
                        <div class="stat-item">
                            <small class="text-muted">Revenue</small>
                            <strong>₱<?php echo number_format((float)$stats['total_revenue'], 0); ?></strong>
                        </div>
                    </div>
                    <button class="btn btn-outline-secondary" onclick="exportOrders()">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-primary" onclick="refreshOrders()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="admin-content-wrapper">
                <div class="content-container">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format((int)$stats['total_orders']); ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format((int)$stats['pending_orders']); ?></div>
                            <div class="stat-label">Pending Orders</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format((int)$stats['delivered_orders']); ?></div>
                            <div class="stat-label">Delivered</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">₱<?php echo number_format((float)$stats['total_revenue'], 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="card mb-4">
                <?php if (!empty($status_filter) || !empty($search) || !empty($date_from) || !empty($date_to) || !empty($payment_method)): ?>
                <div class="card-header bg-light">
                    <small class="text-muted">
                        <i class="fas fa-filter"></i> Filters applied:
                        <?php if (!empty($status_filter)) echo "Status: $status_filter "; ?>
                        <?php if (!empty($search)) echo "Search: \"$search\" "; ?>
                        <?php if (!empty($payment_method)) echo "Payment: $payment_method "; ?>
                        <?php if (!empty($date_from)) echo "From: $date_from "; ?>
                        <?php if (!empty($date_to)) echo "To: $date_to "; ?>
                        <a href="orders.php" class="text-decoration-none ms-2">Clear all</a>
                    </small>
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <form method="GET" class="row g-3" id="filterForm">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Order ID, Customer name, Email...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Shipped" <?php echo $status_filter === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method">
                                <option value="">All Methods</option>
                                <option value="cod" <?php echo $payment_method === 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
                                <option value="card" <?php echo $payment_method === 'card' ? 'selected' : ''; ?>>Credit Card</option>
                                <option value="gcash" <?php echo $payment_method === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                                <option value="bank_transfer" <?php echo $payment_method === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="card mb-4" id="bulkActionsCard" style="display: none;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <span id="selectedCount">0</span> orders selected
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group">
                                <select class="form-select" id="bulkStatusSelect" style="width: auto;">
                                    <option value="">Change Status To...</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Shipped">Shipped</option>
                                    <option value="Delivered">Delivered</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                                <button class="btn btn-primary" onclick="applyBulkStatusChange()">
                                    <i class="fas fa-check"></i> Apply
                                </button>
                                <button class="btn btn-outline-secondary" onclick="clearSelection()">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Orders List</h5>
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-check-label me-2">
                            <input type="checkbox" class="form-check-input" id="selectAllOrders"> Select All
                        </label>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" onclick="changeSort('created_at')">
                                <i class="fas fa-calendar"></i> Date
                            </button>
                            <button class="btn btn-outline-secondary" onclick="changeSort('total_price')">
                                <i class="fas fa-dollar-sign"></i> Amount
                            </button>
                            <button class="btn btn-outline-secondary" onclick="changeSort('status')">
                                <i class="fas fa-flag"></i> Status
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" class="form-check-input" id="selectAllHeader">
                                    </th>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>No orders found matching your criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr class="order-row" data-order-id="<?php echo $order['id']; ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input order-checkbox" value="<?php echo $order['id']; ?>">
                                    </td>
                                    <td>
                                        <span class="fw-bold">#<?php echo $order['id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <div class="fw-medium"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold">₱<?php echo number_format($order['total_price'], 2); ?></span>
                                        <?php if ($order['discount_amount'] > 0): ?>
                                        <br><small class="text-success">-₱<?php echo number_format($order['discount_amount'], 2); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm status-select" data-order-id="<?php echo $order['id']; ?>" data-current-status="<?php echo $order['status']; ?>">
                                            <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Shipped" <?php echo $order['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php
                                            $paymentLabels = [
                                                'cod' => 'Cash on Delivery',
                                                'card' => 'Credit Card',
                                                'gcash' => 'GCash',
                                                'bank_transfer' => 'Bank Transfer'
                                            ];
                                            echo $paymentLabels[$order['payment_method']] ?? ucfirst($order['payment_method']);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-nowrap">
                                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                            <br><small class="text-muted"><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="printInvoice(<?php echo $order['id']; ?>)" title="Print Invoice">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <small class="text-muted">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalOrders); ?> of <?php echo $totalOrders; ?> orders
                            </small>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Orders pagination">
                                <ul class="pagination pagination-sm justify-content-end mb-0">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                    </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" onclick="printCurrentOrder()">
                        <i class="fas fa-print"></i> Print Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Print Modal -->
    <div class="modal fade" id="invoicePrintModal" tabindex="-1" aria-labelledby="invoicePrintModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoicePrintModalLabel">Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="invoiceContent">
                    <!-- Invoice content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Orders Management JavaScript -->
    <script>
        let selectedOrders = new Set();
        let currentOrderId = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Orders page loaded');
            initializeOrdersPage();

            // Add debug info
            console.log('Current URL:', window.location.href);
            console.log('Selected orders:', selectedOrders);
        });

        function initializeOrdersPage() {
            // Initialize status change handlers
            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', function() {
                    const orderId = this.dataset.orderId;
                    const newStatus = this.value;
                    const currentStatus = this.dataset.currentStatus;

                    if (newStatus !== currentStatus) {
                        updateOrderStatus(orderId, newStatus, this);
                    }
                });
            });

            // Initialize checkbox handlers
            document.getElementById('selectAllOrders').addEventListener('change', function() {
                toggleSelectAll(this.checked);
            });

            document.getElementById('selectAllHeader').addEventListener('change', function() {
                toggleSelectAll(this.checked);
            });

            document.querySelectorAll('.order-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectedOrders();
                });
            });

            // Initialize filter form auto-submit
            const filterInputs = document.querySelectorAll('#filterForm input, #filterForm select');
            filterInputs.forEach(input => {
                if (input.type === 'text') {
                    let timeout;
                    input.addEventListener('input', function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => {
                            submitFilterForm();
                        }, 800); // Increased delay for better UX
                    });
                } else if (input.type === 'date' || input.tagName === 'SELECT') {
                    input.addEventListener('change', function() {
                        submitFilterForm();
                    });
                }
            });

            // Manual form submission
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitFilterForm();
            });
        }

        function submitFilterForm() {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            const params = new URLSearchParams();

            // Add all form data to URL parameters
            for (let [key, value] of formData.entries()) {
                if (value && value.trim() !== '') {
                    params.append(key, value.trim());
                }
            }

            // Reset to page 1 when applying new filters
            params.delete('page');

            // Navigate to new URL
            if (params.toString()) {
                window.location.search = params.toString();
            } else {
                // No filters, go to base page
                window.location.href = window.location.pathname;
            }
        }

        function toggleSelectAll(checked) {
            document.querySelectorAll('.order-checkbox').forEach(checkbox => {
                checkbox.checked = checked;
            });
            document.getElementById('selectAllOrders').checked = checked;
            document.getElementById('selectAllHeader').checked = checked;
            updateSelectedOrders();
        }

        function updateSelectedOrders() {
            selectedOrders.clear();
            document.querySelectorAll('.order-checkbox:checked').forEach(checkbox => {
                selectedOrders.add(parseInt(checkbox.value));
            });

            const count = selectedOrders.size;
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('bulkActionsCard').style.display = count > 0 ? 'block' : 'none';

            // Update select all checkboxes
            const totalCheckboxes = document.querySelectorAll('.order-checkbox').length;
            const allSelected = count === totalCheckboxes && count > 0;
            document.getElementById('selectAllOrders').checked = allSelected;
            document.getElementById('selectAllHeader').checked = allSelected;
        }

        function clearSelection() {
            toggleSelectAll(false);
        }

        function updateOrderStatus(orderId, newStatus, selectElement) {
            const originalStatus = selectElement.dataset.currentStatus;

            console.log('Updating order status:', { orderId, newStatus, originalStatus });

            // Show loading state
            selectElement.disabled = true;

            const requestData = {
                action: 'update_status',
                order_id: parseInt(orderId),
                status: newStatus
            };

            console.log('Request data:', requestData);

            fetch('api/orders_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    selectElement.dataset.currentStatus = newStatus;
                    showNotification('Order status updated successfully', 'success');

                    // Optionally refresh the page to update statistics
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    selectElement.value = originalStatus;
                    showNotification(data.message || 'Failed to update order status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                selectElement.value = originalStatus;
                showNotification('An error occurred while updating order status', 'error');
            })
            .finally(() => {
                selectElement.disabled = false;
            });
        }

        function applyBulkStatusChange() {
            const newStatus = document.getElementById('bulkStatusSelect').value;
            if (!newStatus) {
                showNotification('Please select a status', 'warning');
                return;
            }

            if (selectedOrders.size === 0) {
                showNotification('Please select orders to update', 'warning');
                return;
            }

            if (!confirm(`Are you sure you want to change ${selectedOrders.size} orders to ${newStatus}?`)) {
                return;
            }

            const orderIds = Array.from(selectedOrders).map(id => parseInt(id));

            console.log('Bulk update:', { orderIds, newStatus });

            const requestData = {
                action: 'bulk_update_status',
                order_ids: orderIds,
                status: newStatus
            };

            console.log('Bulk request data:', requestData);

            fetch('api/orders_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                console.log('Bulk response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Bulk response data:', data);
                if (data.success) {
                    // Update the UI
                    orderIds.forEach(orderId => {
                        const selectElement = document.querySelector(`select[data-order-id="${orderId}"]`);
                        if (selectElement) {
                            selectElement.value = newStatus;
                            selectElement.dataset.currentStatus = newStatus;
                        }
                    });

                    clearSelection();
                    showNotification(`Successfully updated ${data.affected_rows || orderIds.length} orders`, 'success');

                    // Refresh the page to update statistics
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message || 'Failed to update orders', 'error');
                }
            })
            .catch(error => {
                console.error('Bulk update error:', error);
                showNotification('An error occurred while updating orders', 'error');
            });
        }

        function viewOrderDetails(orderId) {
            currentOrderId = orderId;
            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));

            // Reset modal content
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            modal.show();

            // Load order details
            fetch(`api/orders_data.php?action=get_order_details&order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('orderDetailsContent').innerHTML = generateOrderDetailsHTML(data.order);
                    } else {
                        document.getElementById('orderDetailsContent').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Failed to load order details: ${data.message || 'Unknown error'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('orderDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            An error occurred while loading order details.
                        </div>
                    `;
                });
        }

        function generateOrderDetailsHTML(order) {
            const paymentLabels = {
                'cod': 'Cash on Delivery',
                'card': 'Credit Card',
                'gcash': 'GCash',
                'bank_transfer': 'Bank Transfer'
            };

            let itemsHTML = '';
            order.items.forEach(item => {
                itemsHTML += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="../${item.image}" alt="${item.title}" class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                <div>
                                    <div class="fw-medium">${item.title}</div>
                                    <small class="text-muted">${item.brand} - ${item.color}</small>
                                </div>
                            </div>
                        </td>
                        <td>Size ${item.size}</td>
                        <td>${item.quantity}</td>
                        <td>₱${parseFloat(item.price).toLocaleString()}</td>
                        <td>₱${(parseFloat(item.price) * parseInt(item.quantity)).toLocaleString()}</td>
                    </tr>
                `;
            });

            return `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Order Information</h6>
                        <table class="table table-borderless table-sm">
                            <tr><td class="fw-medium">Order ID:</td><td>#${order.id}</td></tr>
                            <tr><td class="fw-medium">Status:</td><td><span class="badge bg-${getStatusColor(order.status)}">${order.status}</span></td></tr>
                            <tr><td class="fw-medium">Payment Method:</td><td>${paymentLabels[order.payment_method] || order.payment_method}</td></tr>
                            <tr><td class="fw-medium">Order Date:</td><td>${new Date(order.created_at).toLocaleString()}</td></tr>
                            <tr><td class="fw-medium">Last Updated:</td><td>${new Date(order.updated_at).toLocaleString()}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Customer Information</h6>
                        <table class="table table-borderless table-sm">
                            <tr><td class="fw-medium">Name:</td><td>${order.customer_name}</td></tr>
                            <tr><td class="fw-medium">Email:</td><td>${order.customer_email}</td></tr>
                            <tr><td class="fw-medium">Phone:</td><td>${order.phone || 'N/A'}</td></tr>
                        </table>

                        <h6 class="fw-bold mb-3 mt-4">Shipping Address</h6>
                        <address class="mb-0">
                            ${order.shipping_name || order.customer_name}<br>
                            ${order.address_line1}<br>
                            ${order.address_line2 ? order.address_line2 + '<br>' : ''}
                            ${order.city}, ${order.state} ${order.postal_code}<br>
                            ${order.country}
                        </address>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold mb-3">Order Items</h6>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Size</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHTML}
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <table class="table table-borderless">
                            <tr><td>Subtotal:</td><td class="text-end">₱${(parseFloat(order.total_price) + parseFloat(order.discount_amount || 0)).toLocaleString()}</td></tr>
                            ${order.discount_amount > 0 ? `<tr><td>Discount:</td><td class="text-end text-success">-₱${parseFloat(order.discount_amount).toLocaleString()}</td></tr>` : ''}
                            <tr class="fw-bold"><td>Total:</td><td class="text-end">₱${parseFloat(order.total_price).toLocaleString()}</td></tr>
                        </table>
                    </div>
                </div>
            `;
        }

        function getStatusColor(status) {
            const colors = {
                'Pending': 'warning',
                'Shipped': 'info',
                'Delivered': 'success',
                'Cancelled': 'danger'
            };
            return colors[status] || 'secondary';
        }

        function printInvoice(orderId) {
            currentOrderId = orderId;

            // Load invoice content
            fetch(`api/orders_data.php?action=get_invoice&order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('invoiceContent').innerHTML = data.invoice_html;
                        const modal = new bootstrap.Modal(document.getElementById('invoicePrintModal'));
                        modal.show();
                    } else {
                        showNotification('Failed to generate invoice: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while generating invoice', 'error');
                });
        }

        function printCurrentOrder() {
            if (currentOrderId) {
                printInvoice(currentOrderId);
            }
        }

        function changeSort(column) {
            const urlParams = new URLSearchParams(window.location.search);
            const currentSort = urlParams.get('sort');
            const currentOrder = urlParams.get('order');

            if (currentSort === column) {
                // Toggle order
                urlParams.set('order', currentOrder === 'ASC' ? 'DESC' : 'ASC');
            } else {
                // New column
                urlParams.set('sort', column);
                urlParams.set('order', 'DESC');
            }

            window.location.search = urlParams.toString();
        }

        function clearFilters() {
            // Clear all form inputs
            document.querySelector('input[name="search"]').value = '';
            document.querySelector('select[name="status"]').value = '';
            document.querySelector('select[name="payment_method"]').value = '';
            document.querySelector('input[name="date_from"]').value = '';
            document.querySelector('input[name="date_to"]').value = '';

            // Submit the form to reload with no filters
            window.location.href = window.location.pathname;
        }

        function refreshOrders() {
            // Preserve current filters and reload
            window.location.reload();
        }

        function exportOrders() {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('export', 'csv');
            window.open('api/orders_data.php?' + urlParams.toString(), '_blank');
        }

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

            document.body.appendChild(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #invoiceContent, #invoiceContent * {
                visibility: visible;
            }
            #invoiceContent {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .modal-header, .modal-footer {
                display: none !important;
            }
        }
    </style>
</body>
</html>
