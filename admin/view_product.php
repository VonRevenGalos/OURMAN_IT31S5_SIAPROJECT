<?php
/**
 * View Product Details Page
 * Display detailed product information
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

// Get product ID
$productId = (int) ($_GET['id'] ?? 0);

if (!$productId) {
    header('Location: all_products.php');
    exit();
}

// Database connection
require_once '../db.php';

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: all_products.php?error=Product not found');
    exit();
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - Product Details</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    <style>
        .product-detail-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .product-image-gallery {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            text-align: center;
        }
        
        .main-image {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .thumbnail-gallery {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .thumbnail:hover,
        .thumbnail.active {
            border-color: #007bff;
            transform: scale(1.05);
        }
        
        .product-info {
            padding: 30px;
        }
        
        .product-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 15px;
        }
        
        .product-price {
            font-size: 32px;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .product-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .meta-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 15px;
            border-radius: 12px;
        }
        
        .meta-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .stock-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .stock-high {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .stock-medium {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .stock-low {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .stock-out {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
        
        .related-products {
            margin-top: 40px;
        }
        
        .related-product-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .related-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .related-product-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .product-info {
                padding: 20px;
            }
            
            .product-title {
                font-size: 24px;
            }
            
            .product-price {
                font-size: 28px;
            }
            
            .product-meta {
                grid-template-columns: 1fr;
            }
            
            .thumbnail-gallery {
                gap: 8px;
            }
            
            .thumbnail {
                width: 60px;
                height: 60px;
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
                    <button class="btn btn-link d-md-none mobile-menu-btn" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h4 class="mb-0">Product Details</h4>
                        <small class="text-muted"><?php echo htmlspecialchars($product['title']); ?></small>
                    </div>
                </div>
                <div class="header-right">
                    <a href="all_products.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary ms-2">
                        <i class="fas fa-edit"></i> Edit Product
                    </a>
                </div>
            </div>
            
            <!-- Product Details -->
            <div class="container-fluid">
                <div class="product-detail-card">
                    <div class="row g-0">
                        <!-- Product Images -->
                        <div class="col-12 col-lg-6">
                            <div class="product-image-gallery">
                                <?php 
                                $mainImage = '../' . trim($product['image']);
                                $imageExists = !empty($product['image']) && file_exists($mainImage);
                                ?>
                                
                                <?php if ($imageExists): ?>
                                    <img src="<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="main-image" id="mainImage">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center" style="height: 400px;">
                                        <i class="fas fa-image fa-5x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Thumbnail Gallery -->
                                <div class="thumbnail-gallery">
                                    <?php if ($imageExists): ?>
                                        <img src="<?php echo htmlspecialchars($mainImage); ?>" alt="Main" class="thumbnail active" onclick="changeMainImage(this.src)">
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                        <?php 
                                        $thumbField = "thumbnail$i";
                                        if (!empty($product[$thumbField])) {
                                            $thumbPath = '../' . trim($product[$thumbField]);
                                            if (file_exists($thumbPath)):
                                        ?>
                                            <img src="<?php echo htmlspecialchars($thumbPath); ?>" alt="Thumbnail <?php echo $i; ?>" class="thumbnail" onclick="changeMainImage(this.src)">
                                        <?php 
                                            endif;
                                        }
                                        ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Information -->
                        <div class="col-12 col-lg-6">
                            <div class="product-info">
                                <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                                <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                
                                <!-- Stock Status -->
                                <?php
                                $stockClass = 'stock-out';
                                $stockText = 'Out of Stock';
                                $stockIcon = 'fas fa-times-circle';
                                
                                if ($product['stock'] > 10) {
                                    $stockClass = 'stock-high';
                                    $stockText = 'In Stock';
                                    $stockIcon = 'fas fa-check-circle';
                                } elseif ($product['stock'] > 5) {
                                    $stockClass = 'stock-medium';
                                    $stockText = 'Low Stock';
                                    $stockIcon = 'fas fa-exclamation-triangle';
                                } elseif ($product['stock'] > 0) {
                                    $stockClass = 'stock-low';
                                    $stockText = 'Very Low Stock';
                                    $stockIcon = 'fas fa-exclamation-triangle';
                                }
                                ?>
                                <div class="stock-indicator <?php echo $stockClass; ?> mb-4">
                                    <i class="<?php echo $stockIcon; ?>"></i>
                                    <?php echo $stockText; ?> (<?php echo $product['stock']; ?> units)
                                </div>
                                
                                <!-- Product Meta Information -->
                                <div class="product-meta">
                                    <div class="meta-item">
                                        <div class="meta-label">Category</div>
                                        <div class="meta-value"><?php echo ucfirst(htmlspecialchars($product['category'])); ?></div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Brand</div>
                                        <div class="meta-value"><?php echo htmlspecialchars($product['brand']); ?></div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Color</div>
                                        <div class="meta-value"><?php echo htmlspecialchars($product['color']); ?></div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Height</div>
                                        <div class="meta-value"><?php echo ucfirst(htmlspecialchars($product['height'])); ?></div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Width</div>
                                        <div class="meta-value"><?php echo ucfirst(htmlspecialchars($product['width'])); ?></div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Collection</div>
                                        <div class="meta-value"><?php echo htmlspecialchars($product['collection']); ?></div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Product ID</div>
                                        <div class="meta-value">#<?php echo $product['id']; ?></div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Date Added</div>
                                        <div class="meta-value"><?php echo date('M j, Y', strtotime($product['date_added'])); ?></div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <?php if (!empty($product['description'])): ?>
                                <div class="mb-4">
                                    <h6 class="mb-2">Description</h6>
                                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="d-flex gap-3">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit Product
                                    </a>
                                    <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Related Products -->
                <?php if (!empty($relatedProducts)): ?>
                <div class="related-products">
                    <h5 class="mb-4">Related Products in <?php echo ucfirst(htmlspecialchars($product['category'])); ?></h5>
                    <div class="row g-3">
                        <?php foreach ($relatedProducts as $related): ?>
                        <div class="col-6 col-md-3">
                            <div class="related-product-card">
                                <?php 
                                $relatedImage = '../' . trim($related['image']);
                                if (!empty($related['image']) && file_exists($relatedImage)):
                                ?>
                                    <img src="<?php echo htmlspecialchars($relatedImage); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="related-product-image">
                                <?php else: ?>
                                    <div class="related-product-image d-flex align-items-center justify-content-center bg-light">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <h6 class="mb-2"><?php echo htmlspecialchars($related['title']); ?></h6>
                                <div class="text-success fw-bold mb-2">₱<?php echo number_format($related['price'], 2); ?></div>
                                <small class="text-muted">Stock: <?php echo $related['stock']; ?></small>
                                <div class="mt-2">
                                    <a href="view_product.php?id=<?php echo $related['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Change main image
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            event.target.classList.add('active');
        }
        
        // Confirm delete
        function confirmDelete(productId) {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                window.location.href = `delete_product.php?id=${productId}`;
            }
        }
    </script>
</body>
</html>
