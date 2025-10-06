<?php
/**
 * All Products Management Page
 * Real-time product display with responsive design
 */

// Include admin authentication
require_once 'includes/admin_auth.php';

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
require_once '../db.php';

// Include audit logger and log page access
require_once 'includes/audit_logger.php';
auditPageView('all_products.php');

// Get filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$color = $_GET['color'] ?? '';
$sort = $_GET['sort'] ?? 'date_added';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(100, (int)($_GET['limit'] ?? 12))); // Limit between 1-100
$offset = ($page - 1) * $limit;

// Validate sort column to prevent SQL injection
$allowedSortColumns = ['date_added', 'title', 'price', 'stock', 'category', 'brand', 'color'];
if (!in_array($sort, $allowedSortColumns)) {
    $sort = 'date_added';
}

// Validate order direction
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Build WHERE clause
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(title LIKE ? OR description LIKE ? OR brand LIKE ? OR category LIKE ? OR color LIKE ? OR collection LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $whereConditions[] = "category = ?";
    $params[] = $category;
}

if (!empty($brand)) {
    $whereConditions[] = "brand = ?";
    $params[] = $brand;
}

if (!empty($color)) {
    $whereConditions[] = "color = ?";
    $params[] = $color;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM products $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalProducts / $limit);

// Get products with filters
$sql = "SELECT * FROM products $whereClause ORDER BY `$sort` $order LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);

// Bind WHERE clause parameters
foreach ($params as $index => $param) {
    $stmt->bindValue($index + 1, $param);
}

// Bind LIMIT and OFFSET as integers (using positional parameters)
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get filter options
$categoriesStmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

$brandsStmt = $pdo->query("SELECT DISTINCT brand FROM products ORDER BY brand");
$brands = $brandsStmt->fetchAll(PDO::FETCH_COLUMN);

$colorsStmt = $pdo->query("SELECT DISTINCT color FROM products ORDER BY color");
$colors = $colorsStmt->fetchAll(PDO::FETCH_COLUMN);

// Get product statistics
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(stock) as total_stock,
        COUNT(CASE WHEN stock <= 5 THEN 1 END) as low_stock,
        COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock,
        AVG(price) as avg_price,
        MIN(price) as min_price,
        MAX(price) as max_price
    FROM products
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products - Admin Panel</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <style>
        .products-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .filter-section {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .product-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .product-image {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stock-high {
            background: rgba(40, 167, 69, 0.9);
            color: white;
        }
        
        .stock-medium {
            background: rgba(255, 193, 7, 0.9);
            color: #000;
        }
        
        .stock-low {
            background: rgba(220, 53, 69, 0.9);
            color: white;
        }
        
        .stock-out {
            background: rgba(108, 117, 125, 0.9);
            color: white;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .meta-badge {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #6c757d;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .product-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            flex: 1;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .pagination-wrapper {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .live-indicator i {
            animation: pulse 2s infinite;
        }
        
        @media (max-width: 768px) {
            .products-header {
                padding: 20px;
            }
            
            .filter-section {
                padding: 15px;
            }
            
            .product-image {
                height: 180px;
            }
            
            .product-info {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .stat-item {
                padding: 12px;
            }
            
            .stat-number {
                font-size: 16px;
            }
        }
        
        @media (max-width: 576px) {
            .product-actions {
                flex-direction: column;
            }
            
            .btn-action {
                font-size: 11px;
                padding: 6px 10px;
            }
        }
    </style>
</head>
<body>
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
                        <h4 class="mb-0">All Products</h4>
                        <small class="text-muted">Manage your product inventory with real-time updates</small>
                    </div>
                </div>
                <div class="header-right">
                    <div class="realtime-indicator">
                        <span class="realtime-dot"></span>
                        <small>Live Updates</small>
                    </div>
                    <button type="button" class="btn-admin btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus"></i>
                        Add Product
                    </button>
                </div>
            </div>
            
            <!-- Products Content -->
            <div class="admin-content-wrapper">
                <div class="content-container">
                <!-- Page Title -->
                <div class="page-title">
                    <h1>Product Inventory</h1>
                    <nav class="breadcrumb">
                        <span class="breadcrumb-item">Admin</span>
                        <span class="breadcrumb-item">Products</span>
                        <span class="breadcrumb-item active">All Products</span>
                    </nav>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="admin-card">
                            <div class="admin-card-body text-center">
                                <div class="stat-number text-primary" id="totalProducts"><?php echo number_format($stats['total_products']); ?></div>
                                <div class="stat-label">Total Products</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="admin-card">
                            <div class="admin-card-body text-center">
                                <div class="stat-number text-success" id="totalStock"><?php echo number_format($stats['total_stock']); ?></div>
                                <div class="stat-label">Total Stock</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="admin-card">
                            <div class="admin-card-body text-center">
                                <div class="stat-number text-warning" id="lowStock"><?php echo $stats['low_stock']; ?></div>
                                <div class="stat-label">Low Stock</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="admin-card">
                            <div class="admin-card-body text-center">
                                <div class="stat-number text-danger" id="outOfStock"><?php echo $stats['out_of_stock']; ?></div>
                                <div class="stat-label">Out of Stock</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">Filter Products</h5>
                    </div>
                    <div class="admin-card-body">
                        <form method="GET" id="filterForm" class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label">Search Products</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, brand...">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($cat)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Brand</label>
                            <select class="form-select" name="brand">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $brandOption): ?>
                                <option value="<?php echo htmlspecialchars($brandOption); ?>" <?php echo $brand === $brandOption ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brandOption); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Color</label>
                            <select class="form-select" name="color">
                                <option value="">All Colors</option>
                                <?php foreach ($colors as $colorOption): ?>
                                <option value="<?php echo htmlspecialchars($colorOption); ?>" <?php echo $color === $colorOption ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($colorOption); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Sort By</label>
                            <select class="form-select" name="sort">
                                <option value="date_added" <?php echo $sort === 'date_added' ? 'selected' : ''; ?>>Date Added</option>
                                <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Name</option>
                                <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Price</option>
                                <option value="stock" <?php echo $sort === 'stock' ? 'selected' : ''; ?>>Stock</option>
                                <option value="category" <?php echo $sort === 'category' ? 'selected' : ''; ?>>Category</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-1">
                            <label class="form-label">Order</label>
                            <select class="form-select" name="order">
                                <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>↓</option>
                                <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>↑</option>
                            </select>
                        </div>
                        <input type="hidden" name="page" value="1">
                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                    </form>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="results-info">
                            <small class="text-muted">
                                Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $totalProducts); ?>
                                of <?php echo number_format($totalProducts); ?> products
                            </small>
                        </div>
                        <div class="view-options">
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="limit" id="limit12" value="12" <?php echo $limit === 12 ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-secondary" for="limit12">12</label>

                                <input type="radio" class="btn-check" name="limit" id="limit24" value="24" <?php echo $limit === 24 ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-secondary" for="limit24">24</label>

                                <input type="radio" class="btn-check" name="limit" id="limit48" value="48" <?php echo $limit === 48 ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-secondary" for="limit48">48</label>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">Products</h5>
                        <small class="text-muted">
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $totalProducts); ?>
                            of <?php echo number_format($totalProducts); ?> products
                        </small>
                    </div>
                    <div class="admin-card-body">
                        <div class="row g-4" id="productsGrid">
                    <?php if (empty($products)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No products found</h5>
                            <p class="text-muted">Try adjusting your filters or search terms.</p>
                        </div>
                    </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                            <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                                <div class="product-image">
                                    <?php
                                    $imagePath = '../' . trim($product['image']);
                                    $imageExists = !empty($product['image']) && file_exists($imagePath);
                                    ?>
                                    <?php if ($imageExists): ?>
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center h-100">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Stock Badge -->
                                    <?php
                                    $stockClass = 'stock-out';
                                    $stockText = 'Out of Stock';
                                    if ($product['stock'] > 10) {
                                        $stockClass = 'stock-high';
                                        $stockText = 'In Stock';
                                    } elseif ($product['stock'] > 5) {
                                        $stockClass = 'stock-medium';
                                        $stockText = 'Low Stock';
                                    } elseif ($product['stock'] > 0) {
                                        $stockClass = 'stock-low';
                                        $stockText = 'Very Low';
                                    }
                                    ?>
                                    <span class="stock-badge <?php echo $stockClass; ?>">
                                        <?php echo $stockText; ?>
                                    </span>
                                </div>

                                <div class="product-info">
                                    <h6 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h6>
                                    <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>

                                    <div class="product-meta">
                                        <span class="meta-badge"><?php echo htmlspecialchars($product['category']); ?></span>
                                        <span class="meta-badge"><?php echo htmlspecialchars($product['brand']); ?></span>
                                        <span class="meta-badge"><?php echo htmlspecialchars($product['color']); ?></span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <small class="text-muted">Stock: <strong><?php echo $product['stock']; ?></strong></small>
                                        <small class="text-muted">ID: #<?php echo $product['id']; ?></small>
                                    </div>

                                    <div class="product-actions">
                                        <button class="btn btn-outline-primary btn-action" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-outline-success btn-action" onclick="editProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-outline-danger btn-action" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <nav aria-label="Products pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <!-- Previous Page -->
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Next Page -->
                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                            (<?php echo number_format($totalProducts); ?> total products)
                        </small>
                    </div>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addProductForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_title" class="form-label">Product Title *</label>
                                    <input type="text" class="form-control" id="add_title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add_price" class="form-label">Price *</label>
                                    <input type="number" class="form-control" id="add_price" name="price" step="0.01" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add_stock" class="form-label">Stock *</label>
                                    <input type="number" class="form-control" id="add_stock" name="stock" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                                </div>
                            </div>

                            <!-- Category and Attributes -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_gender" class="form-label">Gender *</label>
                                    <select class="form-select" id="add_gender" name="gender" required onchange="updateCategoryOptions('add')">
                                        <option value="">Select Gender</option>
                                        <option value="men">Men</option>
                                        <option value="women">Women</option>
                                        <option value="kids">Kids</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_category" class="form-label">Category *</label>
                                    <select class="form-select" id="add_category" name="category" required>
                                        <option value="">Select Category</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_color" class="form-label">Color *</label>
                                    <select class="form-select" id="add_color" name="color" required>
                                        <option value="">Select Color</option>
                                        <option value="Black">Black</option>
                                        <option value="Blue">Blue</option>
                                        <option value="Brown">Brown</option>
                                        <option value="Green">Green</option>
                                        <option value="Gray">Gray</option>
                                        <option value="Multi-Colour">Multi-Colour</option>
                                        <option value="Orange">Orange</option>
                                        <option value="Pink">Pink</option>
                                        <option value="Purple">Purple</option>
                                        <option value="Red">Red</option>
                                        <option value="White">White</option>
                                        <option value="Yellow">Yellow</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_height" class="form-label">Height *</label>
                                    <select class="form-select" id="add_height" name="height" required>
                                        <option value="">Select Height</option>
                                        <option value="low top">Low Top</option>
                                        <option value="mid top">Mid Top</option>
                                        <option value="high top">High Top</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Additional Attributes -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_width" class="form-label">Width *</label>
                                    <select class="form-select" id="add_width" name="width" required>
                                        <option value="">Select Width</option>
                                        <option value="regular">Regular</option>
                                        <option value="wide">Wide</option>
                                        <option value="extra wide">Extra Wide</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add_brand" class="form-label">Brand</label>
                                    <input type="text" class="form-control" id="add_brand" name="brand" value="Generic">
                                </div>
                                <div class="mb-3">
                                    <label for="add_collection" class="form-label">Collection</label>
                                    <select class="form-select" id="add_collection" name="collection">
                                        <option value="Standard">Standard</option>
                                        <option value="Air Rizz">Air Rizz</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Image Uploads -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_image" class="form-label">Main Image * (JPG/WEBP only)</label>
                                    <input type="file" class="form-control" id="add_image" name="image" accept=".jpg,.jpeg,.webp" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add_thumbnail1" class="form-label">Thumbnail 1 (JPG/WEBP only)</label>
                                    <input type="file" class="form-control" id="add_thumbnail1" name="thumbnail1" accept=".jpg,.jpeg,.webp">
                                </div>
                                <div class="mb-3">
                                    <label for="add_thumbnail2" class="form-label">Thumbnail 2 (JPG/WEBP only)</label>
                                    <input type="file" class="form-control" id="add_thumbnail2" name="thumbnail2" accept=".jpg,.jpeg,.webp">
                                </div>
                                <div class="mb-3">
                                    <label for="add_thumbnail3" class="form-label">Thumbnail 3 (JPG/WEBP only)</label>
                                    <input type="file" class="form-control" id="add_thumbnail3" name="thumbnail3" accept=".jpg,.jpeg,.webp">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editProductForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit_product_id" name="product_id">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_title" class="form-label">Product Title *</label>
                                    <input type="text" class="form-control" id="edit_title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_price" class="form-label">Price *</label>
                                    <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_stock" class="form-label">Stock *</label>
                                    <input type="number" class="form-control" id="edit_stock" name="stock" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                                </div>
                            </div>

                            <!-- Category and Attributes -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_gender" class="form-label">Gender *</label>
                                    <select class="form-select" id="edit_gender" name="gender" required onchange="updateCategoryOptions('edit')">
                                        <option value="">Select Gender</option>
                                        <option value="men">Men</option>
                                        <option value="women">Women</option>
                                        <option value="kids">Kids</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_category" class="form-label">Category *</label>
                                    <select class="form-select" id="edit_category" name="category" required>
                                        <option value="">Select Category</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_color" class="form-label">Color *</label>
                                    <select class="form-select" id="edit_color" name="color" required>
                                        <option value="">Select Color</option>
                                        <option value="Black">Black</option>
                                        <option value="Blue">Blue</option>
                                        <option value="Brown">Brown</option>
                                        <option value="Green">Green</option>
                                        <option value="Gray">Gray</option>
                                        <option value="Multi-Colour">Multi-Colour</option>
                                        <option value="Orange">Orange</option>
                                        <option value="Pink">Pink</option>
                                        <option value="Purple">Purple</option>
                                        <option value="Red">Red</option>
                                        <option value="White">White</option>
                                        <option value="Yellow">Yellow</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_height" class="form-label">Height *</label>
                                    <select class="form-select" id="edit_height" name="height" required>
                                        <option value="">Select Height</option>
                                        <option value="low top">Low Top</option>
                                        <option value="mid top">Mid Top</option>
                                        <option value="high top">High Top</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Additional Attributes -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_width" class="form-label">Width *</label>
                                    <select class="form-select" id="edit_width" name="width" required>
                                        <option value="">Select Width</option>
                                        <option value="regular">Regular</option>
                                        <option value="wide">Wide</option>
                                        <option value="extra wide">Extra Wide</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_brand" class="form-label">Brand</label>
                                    <input type="text" class="form-control" id="edit_brand" name="brand">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_collection" class="form-label">Collection</label>
                                    <select class="form-select" id="edit_collection" name="collection">
                                        <option value="Standard">Standard</option>
                                        <option value="Air Rizz">Air Rizz</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Image Uploads -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_image" class="form-label">Main Image (JPG/WEBP only)</label>
                                    <input type="file" class="form-control" id="edit_image" name="image" accept=".jpg,.jpeg,.webp">
                                    <small class="text-muted">Leave empty to keep current image</small>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_thumbnail1" class="form-label">Thumbnail 1 (JPG/WEBP only)</label>
                                    <input type="file" class="form-control" id="edit_thumbnail1" name="thumbnail1" accept=".jpg,.jpeg,.webp">
                                    <small class="text-muted">Leave empty to keep current image</small>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_thumbnail2" class="form-label">Thumbnail 2 (JPG/WEBP only)</label>
                                    <input type="file" class="form-control" id="edit_thumbnail2" name="thumbnail2" accept=".jpg,.jpeg,.webp">
                                    <small class="text-muted">Leave empty to keep current image</small>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_thumbnail3" class="form-label">Thumbnail 3 (JPG/WEBP only)</label>
                                    <input type="file" class="form-control" id="edit_thumbnail3" name="thumbnail3" accept=".jpg,.jpeg,.webp">
                                    <small class="text-muted">Leave empty to keep current image</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProductModalLabel">Delete Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this product?</p>
                    <div id="deleteProductInfo"></div>
                    <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteProduct">Delete Product</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Real-time Products JavaScript -->
    <script>
        // Real-time update configuration
        const REFRESH_INTERVAL = 60000; // 1 minute
        let updateInterval;
        let lastUpdateTime = 0;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, loadProducts function available:', typeof loadProducts);
            // Auto-submit form on filter changes
            setupFilterHandlers();

            // Start real-time updates
            startRealTimeUpdates();

            // Setup search functionality
            setupSearch();

            // Setup limit change handlers
            setupLimitHandlers();
        });

        // Setup filter change handlers
        function setupFilterHandlers() {
            const form = document.getElementById('filterForm');
            const selects = form.querySelectorAll('select');

            selects.forEach(select => {
                select.addEventListener('change', function() {
                    // Reset to page 1 when filters change
                    form.querySelector('input[name="page"]').value = 1;
                    form.submit();
                });
            });
        }

        // Setup search functionality
        function setupSearch() {
            const searchInput = document.querySelector('input[name="search"]');
            const form = document.getElementById('filterForm');
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    form.querySelector('input[name="page"]').value = 1;
                    form.submit();
                }, 500); // Debounce search
            });
        }

        // Setup limit change handlers
        function setupLimitHandlers() {
            const limitRadios = document.querySelectorAll('input[name="limit"]');
            const form = document.getElementById('filterForm');

            limitRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    form.querySelector('input[name="limit"]').value = this.value;
                    form.querySelector('input[name="page"]').value = 1;
                    form.submit();
                });
            });
        }

        // Start real-time updates
        function startRealTimeUpdates() {
            updateInterval = setInterval(updateProductStats, REFRESH_INTERVAL);
            updateProductStats(); // Initial update
        }

        // Update product statistics
        async function updateProductStats() {
            try {
                const response = await fetch('api/products_data.php');
                const data = await response.json();

                if (data.success) {
                    // Update statistics
                    document.getElementById('totalProducts').textContent = data.stats.total_products.toLocaleString();
                    document.getElementById('totalStock').textContent = data.stats.total_stock.toLocaleString();
                    document.getElementById('lowStock').textContent = data.stats.low_stock;
                    document.getElementById('outOfStock').textContent = data.stats.out_of_stock;

                    lastUpdateTime = Date.now();

                    // Update product cards if any stock changes
                    if (data.updated_products) {
                        updateProductCards(data.updated_products);
                    }
                }
            } catch (error) {
                console.error('Error updating product stats:', error);
            }
        }

        // Reload products after CRUD operations
        function loadProducts() {
            try {
                // Since this is a server-side rendered page, the most reliable way
                // to refresh the product list is to reload the page
                window.location.reload();
            } catch (error) {
                console.error('Error reloading page:', error);
                // Fallback: try to refresh manually
                setTimeout(() => {
                    window.location.href = window.location.href;
                }, 100);
            }
        }

        // Update individual product cards
        function updateProductCards(updatedProducts) {
            updatedProducts.forEach(product => {
                const card = document.querySelector(`[data-product-id="${product.id}"]`);
                if (card) {
                    // Update stock badge
                    const stockBadge = card.querySelector('.stock-badge');
                    const stockInfo = card.querySelector('small strong');

                    if (stockBadge && stockInfo) {
                        // Update stock number
                        stockInfo.textContent = product.stock;

                        // Update stock badge
                        let stockClass = 'stock-out';
                        let stockText = 'Out of Stock';
                        if (product.stock > 10) {
                            stockClass = 'stock-high';
                            stockText = 'In Stock';
                        } else if (product.stock > 5) {
                            stockClass = 'stock-medium';
                            stockText = 'Low Stock';
                        } else if (product.stock > 0) {
                            stockClass = 'stock-low';
                            stockText = 'Very Low';
                        }

                        stockBadge.className = `stock-badge ${stockClass}`;
                        stockBadge.textContent = stockText;

                        // Add update animation
                        card.style.transform = 'scale(1.02)';
                        setTimeout(() => {
                            card.style.transform = '';
                        }, 200);
                    }
                }
            });
        }

        // Product action functions
        function viewProduct(productId) {
            // Open product details modal or navigate to product page
            window.open(`view_product.php?id=${productId}`, '_blank');
        }

        function editProduct(productId) {
            // Navigate to edit product page
            window.location.href = `edit_product.php?id=${productId}`;
        }

        // Page visibility handling
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Pause updates when page is hidden
                clearInterval(updateInterval);
            } else {
                // Resume updates when page becomes visible
                startRealTimeUpdates();
            }
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            clearInterval(updateInterval);
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

        // Product Management Functions
        function updateCategoryOptions(prefix) {
            const genderSelect = document.getElementById(prefix + '_gender');
            const categorySelect = document.getElementById(prefix + '_category');
            const gender = genderSelect.value;

            // Clear existing options
            categorySelect.innerHTML = '<option value="">Select Category</option>';

            if (gender === 'men') {
                categorySelect.innerHTML += '<option value="sneakers">Sneakers</option>';
                categorySelect.innerHTML += '<option value="running">Running</option>';
                categorySelect.innerHTML += '<option value="athletics">Athletics</option>';
            } else if (gender === 'women') {
                categorySelect.innerHTML += '<option value="womensneakers">Sneakers</option>';
                categorySelect.innerHTML += '<option value="womenrunning">Running</option>';
                categorySelect.innerHTML += '<option value="womenathletics">Athletics</option>';
            } else if (gender === 'kids') {
                categorySelect.innerHTML += '<option value="kidsneakers">Sneakers</option>';
                categorySelect.innerHTML += '<option value="kidsathletics">Athletics</option>';
                categorySelect.innerHTML += '<option value="kidslipon">Slip-On</option>';
            }
        }

        function editProduct(productId) {
            // Fetch product data
            fetch(`api/product_management.php?action=get&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;

                        // Populate form fields
                        document.getElementById('edit_product_id').value = product.id;
                        document.getElementById('edit_title').value = product.title;
                        document.getElementById('edit_price').value = product.price;
                        document.getElementById('edit_stock').value = product.stock;
                        document.getElementById('edit_description').value = product.description || '';
                        document.getElementById('edit_gender').value = product.gender;

                        // Update category options and set value
                        updateCategoryOptions('edit');
                        setTimeout(() => {
                            document.getElementById('edit_category').value = product.category;
                        }, 100);

                        document.getElementById('edit_color').value = product.color;
                        document.getElementById('edit_height').value = product.height;
                        document.getElementById('edit_width').value = product.width;
                        document.getElementById('edit_brand').value = product.brand || '';
                        document.getElementById('edit_collection').value = product.collection || 'Standard';

                        // Show modal
                        new bootstrap.Modal(document.getElementById('editProductModal')).show();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load product data');
                });
        }

        function deleteProduct(productId) {
            // Fetch product data for confirmation
            fetch(`api/product_management.php?action=get&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;

                        // Show product info in delete modal
                        document.getElementById('deleteProductInfo').innerHTML = `
                            <div class="alert alert-warning">
                                <strong>Product:</strong> ${product.title}<br>
                                <strong>Price:</strong> ₱${parseFloat(product.price).toLocaleString()}<br>
                                <strong>Stock:</strong> ${product.stock} units<br>
                                <strong>Category:</strong> ${product.category}
                            </div>
                        `;

                        // Set up delete confirmation
                        document.getElementById('confirmDeleteProduct').onclick = function() {
                            performDelete(productId);
                        };

                        // Show modal
                        new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load product data');
                });
        }

        function performDelete(productId) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('product_id', productId);

            // Show loading state
            const deleteBtn = document.getElementById('confirmDeleteProduct');
            const originalText = deleteBtn.textContent;
            deleteBtn.textContent = 'Deleting...';
            deleteBtn.disabled = true;

            fetch('api/product_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                console.log('Content-Type:', response.headers.get('content-type'));

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // Try to parse as JSON directly first
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => JSON.stringify(data));
                }

                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                console.log('Response length:', text.length);
                console.log('First 50 chars:', text.substring(0, 50));
                console.log('Last 50 chars:', text.substring(text.length - 50));

                // Trim whitespace from response
                const trimmedText = text.trim();
                console.log('Trimmed response:', trimmedText);

                try {
                    // Clean the response more thoroughly
                    let cleanText = trimmedText;

                    // Remove any BOM or invisible characters
                    cleanText = cleanText.replace(/^\uFEFF/, ''); // Remove BOM
                    cleanText = cleanText.replace(/[\u0000-\u001F\u007F-\u009F]/g, ''); // Remove control characters
                    cleanText = cleanText.replace(/^\s+|\s+$/g, ''); // Trim again

                    console.log('Cleaned text:', cleanText);

                    const data = JSON.parse(cleanText);
                    if (data.success) {
                        alert('Product "' + (data.product_title || 'Unknown') + '" deleted successfully!');
                        try {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteProductModal'));
                            if (modal) modal.hide();
                        } catch (e) {
                            console.warn('Could not hide modal:', e);
                        }
                        loadProducts(); // Reload products
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', text);
                    console.error('Trimmed text:', trimmedText);
                    console.error('Response bytes:', Array.from(text).map(c => c.charCodeAt(0)));

                    alert('Server response error. The operation may have succeeded but there was a response parsing issue. Please refresh the page to see the latest data.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete product: ' + error.message);
            })
            .finally(() => {
                // Reset button state
                deleteBtn.textContent = originalText;
                deleteBtn.disabled = false;
            });
        }

        function viewProduct(productId) {
            // For now, just show an alert. You can implement a view modal later
            alert('View product functionality - Product ID: ' + productId);
        }

        // Form submission handlers
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'add');

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Adding Product...';
            submitBtn.disabled = true;

            fetch('api/product_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                console.log('Content-Type:', response.headers.get('content-type'));

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // Try to parse as JSON directly first
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => JSON.stringify(data));
                }

                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                console.log('Response length:', text.length);
                console.log('First 50 chars:', text.substring(0, 50));
                console.log('Last 50 chars:', text.substring(text.length - 50));

                // Trim whitespace from response
                const trimmedText = text.trim();
                console.log('Trimmed response:', trimmedText);

                try {
                    // Clean the response more thoroughly
                    let cleanText = trimmedText;

                    // Remove any BOM or invisible characters
                    cleanText = cleanText.replace(/^\uFEFF/, ''); // Remove BOM
                    cleanText = cleanText.replace(/[\u0000-\u001F\u007F-\u009F]/g, ''); // Remove control characters
                    cleanText = cleanText.replace(/^\s+|\s+$/g, ''); // Trim again

                    console.log('Cleaned text:', cleanText);

                    const data = JSON.parse(cleanText);
                    if (data.success) {
                        alert('Product added successfully!\nImages uploaded: ' +
                              (data.images_uploaded ? Object.keys(data.images_uploaded).filter(k => data.images_uploaded[k]).length : 0));
                        try {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                            if (modal) modal.hide();
                            this.reset();
                        } catch (e) {
                            console.warn('Could not hide modal or reset form:', e);
                        }
                        loadProducts(); // Reload products
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', text);
                    console.error('Trimmed text:', trimmedText);
                    console.error('Response bytes:', Array.from(text).map(c => c.charCodeAt(0)));

                    alert('Server response error. The operation may have succeeded but there was a response parsing issue. Please refresh the page to see the latest data.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add product: ' + error.message);
            })
            .finally(() => {
                // Reset button state
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });

        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'edit');

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Updating Product...';
            submitBtn.disabled = true;

            fetch('api/product_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                console.log('Content-Type:', response.headers.get('content-type'));

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // Try to parse as JSON directly first
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => JSON.stringify(data));
                }

                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                console.log('Response length:', text.length);
                console.log('First 50 chars:', text.substring(0, 50));
                console.log('Last 50 chars:', text.substring(text.length - 50));

                // Trim whitespace from response
                const trimmedText = text.trim();
                console.log('Trimmed response:', trimmedText);

                try {
                    // Clean the response more thoroughly
                    let cleanText = trimmedText;

                    // Remove any BOM or invisible characters
                    cleanText = cleanText.replace(/^\uFEFF/, ''); // Remove BOM
                    cleanText = cleanText.replace(/[\u0000-\u001F\u007F-\u009F]/g, ''); // Remove control characters
                    cleanText = cleanText.replace(/^\s+|\s+$/g, ''); // Trim again

                    console.log('Cleaned text:', cleanText);

                    const data = JSON.parse(cleanText);
                    if (data.success) {
                        alert('Product updated successfully!\nImages updated: ' +
                              (data.images_updated ? Object.keys(data.images_updated).filter(k => data.images_updated[k]).length : 0));
                        try {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editProductModal'));
                            if (modal) modal.hide();
                        } catch (e) {
                            console.warn('Could not hide modal:', e);
                        }
                        loadProducts(); // Reload products
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', text);
                    console.error('Trimmed text:', trimmedText);
                    console.error('Response bytes:', Array.from(text).map(c => c.charCodeAt(0)));

                    alert('Server response error. The operation may have succeeded but there was a response parsing issue. Please refresh the page to see the latest data.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update product: ' + error.message);
            })
            .finally(() => {
                // Reset button state
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>
