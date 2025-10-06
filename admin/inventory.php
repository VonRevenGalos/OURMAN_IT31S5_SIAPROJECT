<?php
/**
 * Professional Inventory Management System
 * Clean, Modern UI with Real-time Updates
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
        case 'update_stock':
            if (isset($_POST['product_id']) && isset($_POST['new_stock'])) {
                $productId = (int)$_POST['product_id'];
                $newStock = max(0, (int)$_POST['new_stock']);

                try {
                    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                    $stmt->execute([$newStock, $productId]);

                    // Get updated product info
                    $productStmt = $pdo->prepare("SELECT title, stock FROM products WHERE id = ?");
                    $productStmt->execute([$productId]);
                    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Stock updated successfully',
                        'product' => $product
                    ]);
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            exit();

        case 'get_inventory_data':
            $search = $_POST['search'] ?? '';
            $category = $_POST['category'] ?? '';
            $stockFilter = $_POST['stock_filter'] ?? '';
            $sortBy = $_POST['sort'] ?? 'title';
            $sortOrder = $_POST['order'] ?? 'ASC';
            $page = max(1, (int)($_POST['page'] ?? 1));

            try {
                $result = getInventoryData($pdo, $search, $category, $stockFilter, $sortBy, $sortOrder, $page);
                echo json_encode(['success' => true, 'data' => $result]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();

        case 'get_stats':
            try {
                $stats = getInventoryStats($pdo);
                echo json_encode(['success' => true, 'stats' => $stats]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();
    }
}

// Helper Functions
function getInventoryStats($pdo) {
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total_products,
            SUM(stock) as total_stock,
            COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock,
            COUNT(CASE WHEN stock <= 5 AND stock > 0 THEN 1 END) as low_stock,
            COUNT(CASE WHEN stock > 5 THEN 1 END) as in_stock,
            AVG(price) as avg_price,
            SUM(stock * price) as total_value
        FROM products
    ");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getInventoryData($pdo, $search = '', $category = '', $stockFilter = '', $sortBy = 'title', $sortOrder = 'ASC', $page = 1) {
    $itemsPerPage = 20;
    $offset = ($page - 1) * $itemsPerPage;

    // Build WHERE conditions
    $whereConditions = [];
    $params = [];

    if (!empty($search)) {
        $whereConditions[] = "(title LIKE ? OR brand LIKE ? OR collection LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if (!empty($category)) {
        $whereConditions[] = "category = ?";
        $params[] = $category;
    }

    if (!empty($stockFilter)) {
        switch ($stockFilter) {
            case 'low':
                $whereConditions[] = "stock <= 5 AND stock > 0";
                break;
            case 'out':
                $whereConditions[] = "stock = 0";
                break;
            case 'in_stock':
                $whereConditions[] = "stock > 0";
                break;
        }
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Validate sort parameters
    $validSortColumns = ['title', 'category', 'stock', 'price', 'brand', 'date_added'];
    if (!in_array($sortBy, $validSortColumns)) {
        $sortBy = 'title';
    }

    $validSortOrders = ['ASC', 'DESC'];
    if (!in_array($sortOrder, $validSortOrders)) {
        $sortOrder = 'ASC';
    }

    // Count total products
    $countQuery = "SELECT COUNT(*) FROM products $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $itemsPerPage);

    // Get products
    $query = "SELECT * FROM products $whereClause ORDER BY $sortBy $sortOrder LIMIT $itemsPerPage OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'products' => $products,
        'totalProducts' => $totalProducts,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ];
}

// Initialize data for page load
try {
    $stats = getInventoryStats($pdo);

    // Get categories for filter dropdown
    $categoriesStmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Inventory initialization error: " . $e->getMessage());
    $stats = [
        'total_products' => 0,
        'total_stock' => 0,
        'out_of_stock' => 0,
        'low_stock' => 0,
        'in_stock' => 0,
        'avg_price' => 0,
        'total_value' => 0
    ];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - ShoeStore Admin</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/inventory.css">
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
                        <h4 class="mb-0">Inventory Management</h4>
                        <small class="text-muted">Monitor and manage product stock levels in real-time</small>
                    </div>
                </div>
                <div class="header-right">
                    <div class="realtime-indicator">
                        <span class="realtime-dot"></span>
                        <small>Live Updates</small>
                    </div>
                    <button type="button" class="btn-admin btn-admin-success" onclick="refreshInventory()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Inventory Content -->
            <div class="admin-content-wrapper">
                <div class="content-container">
                    <!-- Statistics Dashboard -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-boxes fa-2x text-primary"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="totalProducts"><?php echo number_format($stats['total_products']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Total Products</p>
                                            <small class="text-white-50">Active inventory items</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-warehouse fa-2x text-success"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="totalStock"><?php echo number_format($stats['total_stock']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Total Stock</p>
                                            <small class="text-white-50">Units available</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="lowStock"><?php echo number_format($stats['low_stock']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Low Stock</p>
                                            <small class="text-white-50">≤5 units remaining</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3">
                                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-number text-white mb-1" id="outOfStock"><?php echo number_format($stats['out_of_stock']); ?></h3>
                                            <p class="stat-label text-white-50 mb-0">Out of Stock</p>
                                            <small class="text-white-50">Needs restocking</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h5 class="admin-card-title mb-0">
                                <i class="fas fa-filter me-2"></i>Filters
                            </h5>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="categoryFilter" class="form-label">Category</label>
                                    <select class="form-select" id="categoryFilter">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category); ?>">
                                                <?php echo ucfirst(htmlspecialchars($category)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="stockFilter" class="form-label">Stock Status</label>
                                    <select class="form-select" id="stockFilter">
                                        <option value="">All Stock</option>
                                        <option value="in_stock">In Stock</option>
                                        <option value="low">Low Stock (≤5)</option>
                                        <option value="out">Out of Stock</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="sortBy" class="form-label">Sort By</label>
                                    <select class="form-select" id="sortBy">
                                        <option value="title">Product Name</option>
                                        <option value="category">Category</option>
                                        <option value="stock">Stock Level</option>
                                        <option value="price">Price</option>
                                        <option value="brand">Brand</option>
                                        <option value="date_added">Date Added</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="sortOrder" class="form-label">Order</label>
                                    <select class="form-select" id="sortOrder">
                                        <option value="ASC">Ascending</option>
                                        <option value="DESC">Descending</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Table -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="admin-card-title mb-0">
                                    <i class="fas fa-list me-2"></i>Product Inventory
                                </h5>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-muted" id="tableInfo">
                                        Loading...
                                    </div>
                                    <div class="spinner-border spinner-border-sm d-none" id="loadingSpinner" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="admin-card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Brand</th>
                                            <th>Price</th>
                                            <th>Current Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inventoryTableBody">
                                        <!-- Dynamic content will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted" id="paginationInfo">
                            <!-- Pagination info will be loaded here -->
                        </div>
                        <nav aria-label="Inventory pagination">
                            <ul class="pagination mb-0" id="paginationControls">
                                <!-- Pagination controls will be loaded here -->
                            </ul>
                        </nav>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Inventory JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize inventory management
            let currentPage = 1;
            let isLoading = false;

            // Load initial data
            loadInventoryData(1);

            // Setup event handlers
            setupEventHandlers();

            function setupEventHandlers() {
                // Filter changes
                ['categoryFilter', 'stockFilter', 'sortBy', 'sortOrder'].forEach(id => {
                    document.getElementById(id).addEventListener('change', function() {
                        currentPage = 1;
                        loadInventoryData(1);
                    });
                });

                // Stock input changes with auto-save
                document.addEventListener('input', function(e) {
                    if (e.target.classList.contains('stock-input')) {
                        handleStockInputChange(e.target);
                    }
                });
            }

            function loadInventoryData(page = 1) {
                if (isLoading) return;

                isLoading = true;
                showLoading(true);

                const formData = new FormData();
                formData.append('action', 'get_inventory_data');
                formData.append('search', ''); // No search functionality
                formData.append('category', document.getElementById('categoryFilter').value);
                formData.append('stock_filter', document.getElementById('stockFilter').value);
                formData.append('sort', document.getElementById('sortBy').value);
                formData.append('order', document.getElementById('sortOrder').value);
                formData.append('page', page);

                fetch('inventory.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderInventoryTable(data.data.products);
                        renderPagination(data.data);
                        updateTableInfo(data.data);
                        currentPage = page;
                    } else {
                        showNotification('Error loading inventory data: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error occurred. Please try again.', 'error');
                })
                .finally(() => {
                    isLoading = false;
                    showLoading(false);
                });
            }

            function renderInventoryTable(products) {
                const tbody = document.getElementById('inventoryTableBody');

                if (products.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-box-open fs-1 text-muted mb-3"></i>
                                <p class="text-muted mb-0">No products found matching your criteria.</p>
                            </td>
                        </tr>
                    `;
                    return;
                }

                tbody.innerHTML = products.map(product => {
                    const stockClass = getStockClass(product.stock);
                    const stockText = getStockText(product.stock);

                    return `
                        <tr data-product-id="${product.id}">
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../${product.image}"
                                         alt="${product.title}"
                                         class="product-image me-3"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;"
                                         onerror="this.src='../assets/img/placeholder.jpg'">
                                    <div>
                                        <h6 class="mb-1">${product.title}</h6>
                                        <small class="text-muted">ID: ${product.id}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    ${product.category.charAt(0).toUpperCase() + product.category.slice(1)}
                                </span>
                            </td>
                            <td>${product.brand}</td>
                            <td>
                                <strong>₱${parseFloat(product.price).toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>
                            </td>
                            <td>
                                <input type="number"
                                       class="form-control stock-input"
                                       value="${product.stock}"
                                       min="0"
                                       data-original="${product.stock}"
                                       data-product-id="${product.id}"
                                       style="width: 80px;">
                            </td>
                            <td>
                                <span class="stock-badge ${stockClass}">
                                    ${stockText}
                                </span>
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-update btn-sm update-stock-btn"
                                        data-product-id="${product.id}"
                                        style="display: none;">
                                    <i class="fas fa-save me-1"></i>Update
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function getStockClass(stock) {
                if (stock === 0) return 'stock-out';
                if (stock <= 5) return 'stock-low';
                return 'stock-good';
            }

            function getStockText(stock) {
                if (stock === 0) return 'Out of Stock';
                if (stock <= 5) return 'Low Stock';
                return 'In Stock';
            }

            function handleStockInputChange(input) {
                const productId = input.dataset.productId;
                const originalValue = parseInt(input.dataset.original);
                const currentValue = parseInt(input.value) || 0;
                const updateBtn = input.closest('tr').querySelector('.update-stock-btn');

                if (currentValue !== originalValue) {
                    updateBtn.style.display = 'inline-block';
                    updateBtn.onclick = () => updateStock(productId, currentValue, input);
                } else {
                    updateBtn.style.display = 'none';
                }
            }

            function updateStock(productId, newStock, inputElement) {
                const updateBtn = inputElement.closest('tr').querySelector('.update-stock-btn');
                updateBtn.disabled = true;
                updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';

                const formData = new FormData();
                formData.append('action', 'update_stock');
                formData.append('product_id', productId);
                formData.append('new_stock', newStock);

                fetch('inventory.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        inputElement.dataset.original = newStock;
                        updateBtn.style.display = 'none';

                        // Update stock status badge
                        const statusBadge = inputElement.closest('tr').querySelector('.stock-badge');
                        statusBadge.className = `stock-badge ${getStockClass(newStock)}`;
                        statusBadge.textContent = getStockText(newStock);

                        showNotification('Stock updated successfully!', 'success');

                        // Refresh stats
                        refreshStats();
                    } else {
                        inputElement.value = inputElement.dataset.original;
                        showNotification('Error updating stock: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    inputElement.value = inputElement.dataset.original;
                    showNotification('Network error occurred. Please try again.', 'error');
                })
                .finally(() => {
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = '<i class="fas fa-save me-1"></i>Update';
                });
            }

            function renderPagination(data) {
                const paginationControls = document.getElementById('paginationControls');

                if (data.totalPages <= 1) {
                    paginationControls.innerHTML = '';
                    return;
                }

                let paginationHTML = '';

                // Previous button
                if (data.currentPage > 1) {
                    paginationHTML += `
                        <li class="page-item">
                            <a class="page-link" href="#" onclick="loadInventoryData(${data.currentPage - 1}); return false;">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    `;
                }

                // Page numbers
                const startPage = Math.max(1, data.currentPage - 2);
                const endPage = Math.min(data.totalPages, data.currentPage + 2);

                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `
                        <li class="page-item ${i === data.currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="loadInventoryData(${i}); return false;">
                                ${i}
                            </a>
                        </li>
                    `;
                }

                // Next button
                if (data.currentPage < data.totalPages) {
                    paginationHTML += `
                        <li class="page-item">
                            <a class="page-link" href="#" onclick="loadInventoryData(${data.currentPage + 1}); return false;">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    `;
                }

                paginationControls.innerHTML = paginationHTML;
            }

            function updateTableInfo(data) {
                const tableInfo = document.getElementById('tableInfo');
                const paginationInfo = document.getElementById('paginationInfo');

                const start = (data.currentPage - 1) * 20 + 1;
                const end = Math.min(data.currentPage * 20, data.totalProducts);

                const infoText = `Showing ${start}-${end} of ${data.totalProducts.toLocaleString()} products`;

                tableInfo.textContent = infoText;
                paginationInfo.textContent = infoText;
            }

            function refreshStats() {
                fetch('inventory.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'get_stats' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalProducts').textContent = data.stats.total_products.toLocaleString();
                        document.getElementById('totalStock').textContent = data.stats.total_stock.toLocaleString();
                        document.getElementById('lowStock').textContent = data.stats.low_stock.toLocaleString();
                        document.getElementById('outOfStock').textContent = data.stats.out_of_stock.toLocaleString();
                    }
                })
                .catch(error => console.error('Error refreshing stats:', error));
            }

            function showLoading(show) {
                const spinner = document.getElementById('loadingSpinner');
                if (show) {
                    spinner.classList.remove('d-none');
                } else {
                    spinner.classList.add('d-none');
                }
            }

            function showNotification(message, type = 'info') {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
                notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                notification.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.body.appendChild(notification);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 5000);
            }

            // Global functions
            window.refreshInventory = function() {
                loadInventoryData(currentPage);
                refreshStats();
            };

            window.loadInventoryData = loadInventoryData;
        });
    </script>
</body>
</html>