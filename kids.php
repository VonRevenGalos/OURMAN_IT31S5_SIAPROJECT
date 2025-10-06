<?php
require_once 'includes/session.php';

// Get filter and sort parameters
$color = $_GET['color'] ?? [];
$height = $_GET['height'] ?? [];
$width = $_GET['width'] ?? [];
$brand = $_GET['brand'] ?? [];
$collection = $_GET['collection'] ?? [];
$category = $_GET['category'] ?? [];
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$sort = $_GET['sort'] ?? 'newness';

// Convert single values to arrays for consistency
if (!is_array($color)) $color = $color ? [$color] : [];
if (!is_array($height)) $height = $height ? [$height] : [];
if (!is_array($width)) $width = $width ? [$width] : [];
if (!is_array($brand)) $brand = $brand ? [$brand] : [];
if (!is_array($collection)) $collection = $collection ? [$collection] : [];
if (!is_array($category)) $category = $category ? [$category] : [];

// Build the SQL query
$whereConditions = [];
$params = [];

// Add category filter (kids' products only - kidsathletics, kidsneakers, kidslipon)
$whereConditions[] = "category IN ('kidsathletics', 'kidsneakers', 'kidslipon')";

// Add other filters
if (!empty($color)) {
    $placeholders = str_repeat('?,', count($color) - 1) . '?';
    $whereConditions[] = "color IN ($placeholders)";
    $params = array_merge($params, $color);
}

if (!empty($height)) {
    $placeholders = str_repeat('?,', count($height) - 1) . '?';
    $whereConditions[] = "height IN ($placeholders)";
    $params = array_merge($params, $height);
}

if (!empty($width)) {
    $placeholders = str_repeat('?,', count($width) - 1) . '?';
    $whereConditions[] = "width IN ($placeholders)";
    $params = array_merge($params, $width);
}

if (!empty($brand)) {
    $placeholders = str_repeat('?,', count($brand) - 1) . '?';
    $whereConditions[] = "brand IN ($placeholders)";
    $params = array_merge($params, $brand);
}

if (!empty($collection)) {
    $placeholders = str_repeat('?,', count($collection) - 1) . '?';
    $whereConditions[] = "collection IN ($placeholders)";
    $params = array_merge($params, $collection);
}

if (!empty($category)) {
    $placeholders = str_repeat('?,', count($category) - 1) . '?';
    $whereConditions[] = "category IN ($placeholders)";
    $params = array_merge($params, $category);
}

// Add price range filter
if (!empty($price_min) && is_numeric($price_min)) {
    $whereConditions[] = "price >= ?";
    $params[] = $price_min;
}

if (!empty($price_max) && is_numeric($price_max)) {
    $whereConditions[] = "price <= ?";
    $params[] = $price_max;
}

// Build the complete query - Optimized to select only needed fields
$sql = "SELECT id, title, price, image, brand, color, stock, category, height, width, collection FROM products";
if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $orderBy = 'ORDER BY price ASC';
        break;
    case 'price_high':
        $orderBy = 'ORDER BY price DESC';
        break;
    case 'newness':
        $orderBy = 'ORDER BY date_added DESC';
        break;
    case 'az':
        $orderBy = 'ORDER BY title ASC';
        break;
    case 'clear':
    default:
        $orderBy = 'ORDER BY id ASC';
        break;
}

$sql .= " " . $orderBy;

// Execute the query
try {
    require_once 'db.php';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get filter options
    $colors = ['Black', 'Blue', 'Brown', 'Green', 'Gray', 'Multi-Colour', 'Orange', 'Pink', 'Purple', 'Red', 'White', 'Yellow'];
    $heights = ['low top', 'mid top', 'high top'];
    $widths = ['regular', 'wide', 'extra wide'];
    $brands = [];
    $collections = [];
    $categories = ['kidsathletics', 'kidsneakers', 'kidslipon'];
    
    // Get filter options optimized - single query approach
    $filterStmt = $pdo->prepare("
        SELECT brand, collection, price
        FROM products
        WHERE category IN ('kidsathletics', 'kidsneakers', 'kidslipon')
    ");
    $filterStmt->execute();
    $filterData = $filterStmt->fetchAll(PDO::FETCH_ASSOC);

    // Extract unique values
    $brands = array_unique(array_filter(array_column($filterData, 'brand')));
    $collections = array_unique(array_filter(array_column($filterData, 'collection')));
    sort($brands);
    sort($collections);

    // Get price range
    $prices = array_column($filterData, 'price');
    $priceRange = [
        'min_price' => !empty($prices) ? min($prices) : 150,
        'max_price' => !empty($prices) ? max($prices) : 15000
    ];
    $minPrice = $priceRange['min_price'] ?? 150;
    $maxPrice = $priceRange['max_price'] ?? 15000;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $products = [];
    $colors = [];
    $heights = [];
    $widths = [];
    $brands = [];
    $collections = [];
    $categories = [];
    $minPrice = 150;
    $maxPrice = 15000;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kids' Shoes - ShoeARizz</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/kids.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
        <div class="kids-container">
            <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-header">
                        <h1 class="page-title">
                            <i class="fas fa-child me-3"></i>Kids' Collection
                        </h1>
                        <p class="page-subtitle">Fun and comfortable shoes for active kids</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Toggle Button (Mobile) -->
            <div class="row d-lg-none mb-3">
                <div class="col-12">
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter me-2"></i>Filters
                    </button>
                </div>
            </div>
            
            <!-- Main Content Row -->
            <div class="row">
                <!-- Filters Sidebar (Desktop) -->
                <div class="col-lg-3 d-none d-lg-block" id="sidebarContainer">
                    <div class="filters-sidebar" id="filtersSidebar">
                        <div class="filters-header">
                            <h3 class="filters-title">
                                <i class="fas fa-filter me-2"></i>Filters
                            </h3>
                            <div class="filter-actions">
                                <button class="btn btn-sm btn-outline-secondary" onclick="clearAllFilters()">
                                    <i class="fas fa-times me-1"></i>Clear All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary d-none" id="hideFiltersBtn" onclick="toggleFilters()">
                                    <i class="fas fa-eye me-1"></i>Show Filter
                                </button>
                            </div>
                        </div>
                        
                        <form method="GET" id="filterForm">
                            <!-- Price Range Filter -->
                            <div class="filter-group">
                                <h4 class="filter-title">Price Range</h4>
                                <div class="price-range-container">
                                    <div class="price-display">
                                        <span id="priceMinDisplay">₱<?php echo number_format($price_min ?: $minPrice); ?></span>
                                        <span id="priceMaxDisplay">₱<?php echo number_format($price_max ?: $maxPrice); ?></span>
                                    </div>
                                    <div class="dual-range-slider">
                                        <input type="range" class="form-range" id="priceRangeMin" 
                                               min="<?php echo $minPrice; ?>" max="<?php echo $maxPrice; ?>" 
                                               value="<?php echo $price_min ?: $minPrice; ?>" step="50">
                                        <input type="range" class="form-range" id="priceRangeMax" 
                                               min="<?php echo $minPrice; ?>" max="<?php echo $maxPrice; ?>" 
                                               value="<?php echo $price_max ?: $maxPrice; ?>" step="50">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Category Filter -->
                            <div class="filter-group">
                                <h4 class="filter-title">Category</h4>
                                <div class="filter-options">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="category[]" value="kidsathletics" 
                                               id="category_kidsathletics" <?php echo (in_array('kidsathletics', $category)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category_kidsathletics">
                                            Kids Athletics
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="category[]" value="kidsneakers" 
                                               id="category_kidsneakers" <?php echo (in_array('kidsneakers', $category)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category_kidsneakers">
                                            Kids Sneakers
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="category[]" value="kidslipon" 
                                               id="category_kidslipon" <?php echo (in_array('kidslipon', $category)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category_kidslipon">
                                            Kids Slip-On
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Color Filter -->
                            <div class="filter-group">
                                <h4 class="filter-title">Color</h4>
                                <div class="filter-options">
                                    <?php foreach ($colors as $col): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="color[]" value="<?php echo htmlspecialchars($col); ?>" 
                                                   id="color_<?php echo strtolower(str_replace(' ', '_', $col)); ?>"
                                                   <?php echo (in_array($col, $color)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="color_<?php echo strtolower(str_replace(' ', '_', $col)); ?>">
                                                <span class="color-indicator" style="background-color: <?php echo strtolower($col); ?>;"></span>
                                                <?php echo htmlspecialchars($col); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Height Filter -->
                            <div class="filter-group">
                                <h4 class="filter-title">Height</h4>
                                <div class="filter-options">
                                    <?php foreach ($heights as $h): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="height[]" value="<?php echo htmlspecialchars($h); ?>" 
                                                   id="height_<?php echo strtolower(str_replace(' ', '_', $h)); ?>"
                                                   <?php echo (in_array($h, $height)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="height_<?php echo strtolower(str_replace(' ', '_', $h)); ?>">
                                                <?php echo htmlspecialchars($h); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Width Filter -->
                            <div class="filter-group">
                                <h4 class="filter-title">Width</h4>
                                <div class="filter-options">
                                    <?php foreach ($widths as $w): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="width[]" value="<?php echo htmlspecialchars($w); ?>" 
                                                   id="width_<?php echo strtolower(str_replace(' ', '_', $w)); ?>"
                                                   <?php echo (in_array($w, $width)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="width_<?php echo strtolower(str_replace(' ', '_', $w)); ?>">
                                                <?php echo htmlspecialchars($w); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Brand Filter -->
                            <div class="filter-group">
                                <h4 class="filter-title">Brand</h4>
                                <div class="filter-options">
                                    <?php foreach ($brands as $b): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="brand[]" value="<?php echo htmlspecialchars($b); ?>" 
                                                   id="brand_<?php echo strtolower(str_replace(' ', '_', $b)); ?>"
                                                   <?php echo (in_array($b, $brand)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="brand_<?php echo strtolower(str_replace(' ', '_', $b)); ?>">
                                                <?php echo htmlspecialchars($b); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Collection Filter -->
                            <div class="filter-group">
                                <h4 class="filter-title">Collection</h4>
                                <div class="filter-options">
                                    <?php foreach ($collections as $col): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="collection[]" value="<?php echo htmlspecialchars($col); ?>" 
                                                   id="collection_<?php echo strtolower(str_replace(' ', '_', $col)); ?>"
                                                   <?php echo (in_array($col, $collection)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="collection_<?php echo strtolower(str_replace(' ', '_', $col)); ?>">
                                                <?php echo htmlspecialchars($col); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                        </form>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="col-lg-9" id="productsContainer">
                    <!-- Sort and Results Header -->
                    <div class="products-header">
                        <div class="results-info">
                            <span class="results-count"><?php echo count($products); ?> products found</span>
                        </div>
                        <div class="sort-controls">
                            <!-- Filter Toggle Button (Desktop) -->
                            <button type="button" class="btn btn-outline-primary me-3 d-none d-lg-inline-block" id="showFiltersBtn" onclick="toggleFilters()">
                                <i class="fas fa-eye-slash me-1"></i>Hide Filter
                            </button>
                            
                            <select name="sort" class="form-select" id="sortSelect">
                                <option value="clear" <?php echo ($sort === 'clear' || $sort === '') ? 'selected' : ''; ?>>Default Sort</option>
                                <option value="newness" <?php echo ($sort === 'newness') ? 'selected' : ''; ?>>Newness</option>
                                <option value="price_low" <?php echo ($sort === 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo ($sort === 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="az" <?php echo ($sort === 'az') ? 'selected' : ''; ?>>A-Z</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Products Grid -->
                    <div class="products-grid">
                        <div class="row">
                            <?php if (empty($products)): ?>
                                <div class="col-12">
                                    <div class="no-products text-center py-5">
                                        <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                                        <h3>No products found</h3>
                                        <p class="text-muted">Try adjusting your filters to see more results.</p>
                                        <button class="btn btn-primary" onclick="clearAllFilters()">Clear All Filters</button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                                        <div class="product-card h-100">
                                            <div class="product-image">
                                                <img src="<?php echo htmlspecialchars($product['image'] ?? 'assets/img/placeholder.jpg'); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                                     class="img-fluid">
                                                <div class="product-overlay">
                                                    <button class="btn btn-outline-light btn-sm" onclick="addToFavorites(<?php echo $product['id']; ?>)">
                                                        <i class="fas fa-heart"></i>
                                                    </button>
                                                    <button class="btn btn-outline-light btn-sm" onclick="quickView(<?php echo $product['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="product-info">
                                                <div class="product-brand"><?php echo htmlspecialchars($product['brand'] ?? 'Generic'); ?></div>
                                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                                <div class="product-details">
                                                    <span class="product-color"><?php echo htmlspecialchars($product['color'] ?? 'N/A'); ?></span>
                                                    <span class="product-height"><?php echo htmlspecialchars($product['height'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="product-details">
                                                    <span class="product-width"><?php echo htmlspecialchars($product['width'] ?? 'N/A'); ?></span>
                                                    <span class="product-collection"><?php echo htmlspecialchars($product['collection'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                                <div class="product-stock">
                                                    <?php if ($product['stock'] > 0): ?>
                                                        <span class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>In Stock
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-danger">
                                                            <i class="fas fa-times-circle me-1"></i>Out of Stock
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (isLoggedIn()): ?>
                                                    <button class="btn btn-primary w-100 mt-2"
                                                            onclick="addToCart(<?php echo $product['id']; ?>, 1, null, this)"
                                                            <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                                    </button>
                                                <?php else: ?>
                                                    <a href="signup.php" class="btn btn-primary w-100 mt-2">
                                                        <i class="fas fa-user-plus me-2"></i>Sign Up to Add to Cart
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">
                        <i class="fas fa-filter me-2"></i>Filters
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" id="mobileFilterForm">
                        <!-- Price Range Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Price Range</h4>
                            <div class="price-range-container">
                                <div class="price-display">
                                    <span id="mobilePriceMinDisplay">₱<?php echo number_format($price_min ?: $minPrice); ?></span>
                                    <span id="mobilePriceMaxDisplay">₱<?php echo number_format($price_max ?: $maxPrice); ?></span>
                                </div>
                                <div class="dual-range-slider">
                                    <input type="range" class="form-range" id="mobilePriceRangeMin" name="price_min"
                                           min="<?php echo $minPrice; ?>" max="<?php echo $maxPrice; ?>" 
                                           value="<?php echo $price_min ?: $minPrice; ?>" step="50">
                                    <input type="range" class="form-range" id="mobilePriceRangeMax" name="price_max"
                                           min="<?php echo $minPrice; ?>" max="<?php echo $maxPrice; ?>" 
                                           value="<?php echo $price_max ?: $maxPrice; ?>" step="50">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Category</h4>
                            <div class="filter-options">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="category[]" value="kidsathletics" 
                                           id="mobile_category_kidsathletics" <?php echo (in_array('kidsathletics', $category)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="mobile_category_kidsathletics">
                                        Kids Athletics
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="category[]" value="kidsneakers" 
                                           id="mobile_category_kidsneakers" <?php echo (in_array('kidsneakers', $category)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="mobile_category_kidsneakers">
                                        Kids Sneakers
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="category[]" value="kidslipon" 
                                           id="mobile_category_kidslipon" <?php echo (in_array('kidslipon', $category)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="mobile_category_kidslipon">
                                        Kids Slip-On
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Color Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Color</h4>
                            <div class="filter-options">
                                <?php foreach ($colors as $col): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="color[]" value="<?php echo htmlspecialchars($col); ?>" 
                                               id="mobile_color_<?php echo strtolower(str_replace(' ', '_', $col)); ?>"
                                               <?php echo (in_array($col, $color)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mobile_color_<?php echo strtolower(str_replace(' ', '_', $col)); ?>">
                                            <span class="color-indicator" style="background-color: <?php echo strtolower($col); ?>;"></span>
                                            <?php echo htmlspecialchars($col); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Height Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Height</h4>
                            <div class="filter-options">
                                <?php foreach ($heights as $h): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="height[]" value="<?php echo htmlspecialchars($h); ?>" 
                                               id="mobile_height_<?php echo strtolower(str_replace(' ', '_', $h)); ?>"
                                               <?php echo (in_array($h, $height)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mobile_height_<?php echo strtolower(str_replace(' ', '_', $h)); ?>">
                                            <?php echo htmlspecialchars($h); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Width Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Width</h4>
                            <div class="filter-options">
                                <?php foreach ($widths as $w): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="width[]" value="<?php echo htmlspecialchars($w); ?>" 
                                               id="mobile_width_<?php echo strtolower(str_replace(' ', '_', $w)); ?>"
                                               <?php echo (in_array($w, $width)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mobile_width_<?php echo strtolower(str_replace(' ', '_', $w)); ?>">
                                            <?php echo htmlspecialchars($w); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Brand Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Brand</h4>
                            <div class="filter-options">
                                <?php foreach ($brands as $b): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="brand[]" value="<?php echo htmlspecialchars($b); ?>" 
                                               id="mobile_brand_<?php echo strtolower(str_replace(' ', '_', $b)); ?>"
                                               <?php echo (in_array($b, $brand)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mobile_brand_<?php echo strtolower(str_replace(' ', '_', $b)); ?>">
                                            <?php echo htmlspecialchars($b); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Collection Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Collection</h4>
                            <div class="filter-options">
                                <?php foreach ($collections as $col): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="collection[]" value="<?php echo htmlspecialchars($col); ?>" 
                                               id="mobile_collection_<?php echo strtolower(str_replace(' ', '_', $col)); ?>"
                                               <?php echo (in_array($col, $collection)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mobile_collection_<?php echo strtolower(str_replace(' ', '_', $col)); ?>">
                                            <?php echo htmlspecialchars($col); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Sort Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Sort By</h4>
                            <select name="sort" class="form-select" id="mobileSortSelect">
                                <option value="clear" <?php echo ($sort === 'clear' || $sort === '') ? 'selected' : ''; ?>>Default Sort</option>
                                <option value="newness" <?php echo ($sort === 'newness') ? 'selected' : ''; ?>>Newness</option>
                                <option value="price_low" <?php echo ($sort === 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo ($sort === 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="az" <?php echo ($sort === 'az') ? 'selected' : ''; ?>>A-Z</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="clearAllFilters()">
                        <i class="fas fa-times me-1"></i>Clear All
                    </button>
                    <button type="button" class="btn btn-primary" onclick="applyMobileFilters()">
                        <i class="fas fa-check me-1"></i>Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/navbar.js"></script>
    <!-- Search JS is already included in navbar.php -->
    <?php if (isLoggedIn()): ?>
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>
    <?php endif; ?>
    <script src="assets/js/kids.js"></script>
</body>
</html>
