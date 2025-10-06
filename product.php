<?php
require_once 'includes/session.php';
require_once 'db.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$product_id = (int)$_GET['id'];

try {
    // Fetch product details - including thumbnail fields for image gallery
    $stmt = $pdo->prepare("SELECT id, title, price, image, brand, color, stock, category, description, height, width, collection, thumbnail1, thumbnail2, thumbnail3 FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header("Location: index.php");
        exit();
    }
    
    // Clean image paths by removing carriage returns and newlines
    $product['image'] = trim($product['image'] ?? '');
    $product['thumbnail1'] = trim($product['thumbnail1'] ?? '');
    $product['thumbnail2'] = trim($product['thumbnail2'] ?? '');
    $product['thumbnail3'] = trim($product['thumbnail3'] ?? '');
    
    // Get current user
    $currentUser = getCurrentUser();
    $user_id = getUserId();

    // Get product reviews and rating summary
    $reviews = [];
    $rating_summary = [
        'total_reviews' => 0,
        'average_rating' => 0.00,
        'rating_1_count' => 0,
        'rating_2_count' => 0,
        'rating_3_count' => 0,
        'rating_4_count' => 0,
        'rating_5_count' => 0
    ];

    try {
        // Get rating summary
        $rating_stmt = $pdo->prepare("SELECT * FROM product_rating_summary WHERE product_id = ?");
        $rating_stmt->execute([$product_id]);
        $rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);

        if ($rating_data) {
            $rating_summary = $rating_data;
        }

        // Get reviews with user information
        $reviews_stmt = $pdo->prepare("
            SELECT pr.*, u.first_name, u.last_name,
                   CASE WHEN pr.is_verified_purchase = 1 THEN 'Verified Purchase' ELSE 'Unverified' END as purchase_status
            FROM product_reviews pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.product_id = ? AND pr.is_approved = 1
            ORDER BY pr.created_at DESC
        ");
        $reviews_stmt->execute([$product_id]);
        $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error fetching reviews: " . $e->getMessage());
    }

    // Check if current user can write a review (has purchased and received the product)
    $can_review = false;
    $user_existing_review = null;

    if (isLoggedIn()) {
        try {
            // Check if user has a delivered order with this product
            $purchase_check_stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'Delivered'
            ");
            $purchase_check_stmt->execute([$user_id, $product_id]);
            $has_purchased = $purchase_check_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

            // Check if user already has a review for this product
            $existing_review_stmt = $pdo->prepare("SELECT * FROM product_reviews WHERE user_id = ? AND product_id = ?");
            $existing_review_stmt->execute([$user_id, $product_id]);
            $user_existing_review = $existing_review_stmt->fetch(PDO::FETCH_ASSOC);

            $can_review = $has_purchased && !$user_existing_review;

        } catch (PDOException $e) {
            error_log("Error checking review eligibility: " . $e->getMessage());
        }
    }
    
    // Determine category page for breadcrumb
    $categoryPage = 'index.php';
    $categoryName = 'Products';
    
    // Check for women products first (more specific)
    if (strpos($product['category'], 'women') !== false || in_array($product['category'], ['womenathletics', 'womenrunning', 'womensneakers'])) {
        $categoryPage = 'women.php';
        $categoryName = 'Women';
    } 
    // Check for kids products
    elseif (strpos($product['category'], 'kids') !== false || in_array($product['category'], ['kidsathletics', 'kidsneakers', 'kidslipon'])) {
        $categoryPage = 'kids.php';
        $categoryName = 'Kids';
    }
    // Check for men products (default for sneakers, running, athletics)
    elseif (strpos($product['category'], 'men') !== false || in_array($product['category'], ['sneakers', 'running', 'athletics'])) {
        $categoryPage = 'men.php';
        $categoryName = 'Men';
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - ShoeARizz</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/product.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="product-hero">
        <div class="container-fluid">
            <div class="row g-0">
                <!-- Product Images Section -->
                <div class="col-lg-6">
                    <div class="product-gallery">
                        <!-- Main Image Container -->
                        <div class="main-image-container">
                            <div class="image-zoom-container">
                                <div class="image-loading-indicator" id="imageLoadingIndicator">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading...</span>
                                </div>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                     id="mainProductImage" class="main-product-image">
                            </div>
                            
                            <!-- Image Navigation Arrows -->
                            <button class="image-nav-btn prev-btn" onclick="previousImage()">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="image-nav-btn next-btn" onclick="nextImage()">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <!-- Thumbnail Gallery -->
                        <div class="thumbnail-gallery">
                            <div class="thumbnail-container">
                                <?php
                                // Create array of all available images
                                $thumbnails = [];

                                // Add main image as first thumbnail
                                if (!empty($product['image'])) {
                                    $thumbnails[] = $product['image'];
                                }

                                // Add additional thumbnails if they exist and are different from main image
                                if (!empty($product['thumbnail1']) && $product['thumbnail1'] !== $product['image']) {
                                    $thumbnails[] = $product['thumbnail1'];
                                }
                                if (!empty($product['thumbnail2']) && $product['thumbnail2'] !== $product['image']) {
                                    $thumbnails[] = $product['thumbnail2'];
                                }
                                if (!empty($product['thumbnail3']) && $product['thumbnail3'] !== $product['image']) {
                                    $thumbnails[] = $product['thumbnail3'];
                                }

                                // Display thumbnails
                                foreach ($thumbnails as $index => $thumbnail):
                                    $cleanThumbnail = trim($thumbnail);
                                    if (!empty($cleanThumbnail)):
                                ?>
                                <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="changeMainImage('<?php echo htmlspecialchars($cleanThumbnail); ?>', <?php echo $index; ?>)">
                                    <img src="<?php echo htmlspecialchars($cleanThumbnail); ?>"
                                         alt="Product Image <?php echo $index + 1; ?>"
                                         class="thumbnail-img"
                                         onerror="this.style.display='none'">
                                </div>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Product Details Section -->
                <div class="col-lg-6">
                    <div class="product-details-section">
                        <div class="product-details-content">
                            <!-- Breadcrumb -->
                            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                    <li class="breadcrumb-item"><a href="<?php echo $categoryPage; ?>"><?php echo $categoryName; ?></a></li>
                                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['title']); ?></li>
                                </ol>
                            </nav>
                            
                            <!-- Product Info -->
                            <div class="product-info">
                                <div class="product-brand"><?php echo htmlspecialchars($product['brand'] ?? 'Generic'); ?></div>
                                <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                                
                                <!-- Product Specifications -->
                                <div class="product-specs">
                                    <div class="spec-grid">
                                        <div class="spec-item">
                                            <span class="spec-label">Color</span>
                                            <span class="spec-value"><?php echo htmlspecialchars($product['color'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label">Height</span>
                                            <span class="spec-value"><?php echo htmlspecialchars($product['height'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label">Width</span>
                                            <span class="spec-value"><?php echo htmlspecialchars($product['width'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label">Collection</span>
                                            <span class="spec-value"><?php echo htmlspecialchars($product['collection'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Price -->
                                <div class="product-price">
                                    <span class="price-currency">₱</span>
                                    <span class="price-amount"><?php echo number_format($product['price'], 2); ?></span>
                                </div>
                                
                                <!-- Stock Status -->
                                <div class="product-stock">
                                    <?php if ($product['stock'] > 0): ?>
                                        <span class="stock-status in-stock">
                                            <i class="fas fa-check-circle"></i>
                                            In Stock (<?php echo $product['stock']; ?> available)
                                        </span>
                                    <?php else: ?>
                                        <span class="stock-status out-of-stock">
                                            <i class="fas fa-times-circle"></i>
                                            Out of Stock
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Size Selection -->
                                <div class="size-selection">
                                    <div class="size-header">
                                        <h4>Select Size</h4>
                                        <a href="sizeguide.php" class="size-guide-link" target="_blank">
                                            <i class="fas fa-ruler-combined"></i> Size Guide
                                        </a>
                                    </div>
                                    <div class="size-options">
                                        <label class="size-option">
                                            <input type="radio" name="size" value="7" required>
                                            <span class="size-label">7</span>
                                        </label>
                                        <label class="size-option">
                                            <input type="radio" name="size" value="7.5" required>
                                            <span class="size-label">7.5</span>
                                        </label>
                                        <label class="size-option">
                                            <input type="radio" name="size" value="8" required>
                                            <span class="size-label">8</span>
                                        </label>
                                        <label class="size-option">
                                            <input type="radio" name="size" value="8.5" required>
                                            <span class="size-label">8.5</span>
                                        </label>
                                        <label class="size-option">
                                            <input type="radio" name="size" value="9" required>
                                            <span class="size-label">9</span>
                                        </label>
                                        <label class="size-option">
                                            <input type="radio" name="size" value="9.5" required>
                                            <span class="size-label">9.5</span>
                                        </label>
                                        <label class="size-option">
                                            <input type="radio" name="size" value="10" required>
                                            <span class="size-label">10</span>
                                        </label>
                                        <label class="size-option">
                                            <input type="radio" name="size" value="10.5" required>
                                            <span class="size-label">10.5</span>
                                        </label>
                                        <label class="size-option">
                                            <input type="radio" name="size" value="11" required>
                                            <span class="size-label">11</span>
                                        </label>
                                        <label class="size-option">
                                            <input type="radio" name="size" value="11.5" required>
                                            <span class="size-label">11.5</span>
                                        </label>
                                        <label class="size-option">
                                            <input type="radio" name="size" value="12" required>
                                            <span class="size-label">12</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="action-buttons">
                                    <?php if (isLoggedIn()): ?>
                                        <button class="btn-add-cart" onclick="addToCartWithSize(<?php echo $product['id']; ?>, 1, this)"
                                                <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-shopping-cart"></i>
                                            Add to Cart
                                        </button>
                                        <button class="btn-favorite" onclick="addToFavorites(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-heart"></i>
                                            Add to Favorites
                                        </button>
                                    <?php else: ?>
                                        <a href="signup.php" class="btn-add-cart">
                                            <i class="fas fa-user-plus"></i>
                                            Sign Up to Add to Cart
                                        </a>
                                        <a href="signup.php" class="btn-favorite">
                                            <i class="fas fa-heart"></i>
                                            Sign Up to Add to Favorites
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Product Description -->
                                <div class="product-description">
                                    <h4>Description</h4>
                                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Features Section -->
    <div class="product-features">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h3 class="features-title">Product Features</h3>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <div class="feature-content">
                                <h5>Free Shipping</h5>
                                <p>Free shipping on orders over ₱2,000</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-undo"></i>
                            </div>
                            <div class="feature-content">
                                <h5>Easy Returns</h5>
                                <p>30-day return policy</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="feature-content">
                                <h5>Quality Guarantee</h5>
                                <p>Premium quality materials</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="feature-content">
                                <h5>24/7 Support</h5>
                                <p>Customer support available</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reviews and Feedback Section -->
    <div class="reviews-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="reviews-header">
                        <h3 class="reviews-title">Customer Reviews & Feedback</h3>
                        <div class="reviews-summary">
                            <div class="rating-overview">
                                <div class="average-rating">
                                    <span class="rating-number"><?php echo number_format($rating_summary['average_rating'], 1); ?></span>
                                    <div class="stars">
                                        <?php
                                        $avg_rating = (float)$rating_summary['average_rating'];
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= $avg_rating): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $avg_rating): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif;
                                        endfor; ?>
                                    </div>
                                    <span class="rating-count">Based on <?php echo (int)$rating_summary['total_reviews']; ?> reviews</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Review Filters -->
                    <div class="review-filters">
                        <div class="filter-buttons">
                            <button class="filter-btn active" data-filter="all">All Reviews</button>
                            <button class="filter-btn" data-filter="5">5 Stars</button>
                            <button class="filter-btn" data-filter="4">4 Stars</button>
                            <button class="filter-btn" data-filter="3">3 Stars</button>
                            <button class="filter-btn" data-filter="2">2 Stars</button>
                            <button class="filter-btn" data-filter="1">1 Star</button>
                        </div>
                        <div class="sort-options">
                            <select class="sort-select" id="reviewSort">
                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                                <option value="highest">Highest Rating</option>
                                <option value="lowest">Lowest Rating</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Reviews List -->
                    <div class="reviews-list" id="reviewsList">
                        <?php if (empty($reviews)): ?>
                            <div class="no-reviews">
                                <div class="no-reviews-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <h4>No Reviews Yet</h4>
                                <p>Be the first to review this product!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item" data-rating="<?php echo $review['rating']; ?>" data-date="<?php echo $review['created_at']; ?>">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <div class="reviewer-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="reviewer-details">
                                                <div class="reviewer-name"><?php echo htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.'); ?></div>
                                                <div class="review-date">
                                                    <?php echo $review['purchase_status']; ?> •
                                                    <?php
                                                    $review_date = new DateTime($review['created_at']);
                                                    $now = new DateTime();
                                                    $diff = $now->diff($review_date);

                                                    if ($diff->days == 0) {
                                                        echo "Today";
                                                    } elseif ($diff->days == 1) {
                                                        echo "1 day ago";
                                                    } elseif ($diff->days < 7) {
                                                        echo $diff->days . " days ago";
                                                    } elseif ($diff->days < 30) {
                                                        $weeks = floor($diff->days / 7);
                                                        echo $weeks . " week" . ($weeks > 1 ? "s" : "") . " ago";
                                                    } else {
                                                        echo $review_date->format('M j, Y');
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <div class="stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $review['rating']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <h5 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h5>
                                        <p class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                        <div class="review-helpful">
                                            <button class="helpful-btn" onclick="markHelpful(<?php echo $review['id']; ?>, this)">
                                                <i class="fas fa-thumbs-up"></i>
                                                <span>Helpful (<?php echo (int)$review['helpful_count']; ?>)</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($reviews) > 5): ?>
                    <!-- Load More Reviews -->
                    <div class="load-more-section">
                        <button class="btn btn-load-more" onclick="loadMoreReviews()">
                            <i class="fas fa-plus"></i>
                            Load More Reviews
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Write Review Section -->
                    <div class="write-review-section">
                        <?php if (!isLoggedIn()): ?>
                            <h4>Write a Review</h4>
                            <p>Please <a href="login.php">login</a> to write a review</p>
                        <?php elseif ($user_existing_review): ?>
                            <h4>Your Review</h4>
                            <p>You have already reviewed this product</p>
                            <div class="existing-review-info">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $user_existing_review['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="review-title"><?php echo htmlspecialchars($user_existing_review['title']); ?></span>
                            </div>
                        <?php elseif ($can_review): ?>
                            <h4>Write a Review</h4>
                            <p>Share your experience with this product</p>
                            <button class="btn btn-write-review" onclick="openReviewModal()">
                                <i class="fas fa-edit"></i>
                                Write a Review
                            </button>
                        <?php else: ?>
                            <h4>Write a Review</h4>
                            <p>You can only review products you have purchased and received</p>
                            <button class="btn btn-write-review" disabled>
                                <i class="fas fa-lock"></i>
                                Purchase Required to Review
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <?php if ($can_review): ?>
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalLabel">Write a Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reviewForm">
                        <input type="hidden" id="productId" value="<?php echo $product_id; ?>">

                        <div class="mb-3">
                            <label class="form-label">Rating *</label>
                            <div class="rating-input">
                                <div class="stars-input">
                                    <input type="radio" name="rating" value="5" id="star5">
                                    <label for="star5"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="4" id="star4">
                                    <label for="star4"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="3" id="star3">
                                    <label for="star3"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="2" id="star2">
                                    <label for="star2"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" value="1" id="star1">
                                    <label for="star1"><i class="fas fa-star"></i></label>
                                </div>
                                <span class="rating-text">Click to rate</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reviewTitle" class="form-label">Review Title *</label>
                            <input type="text" class="form-control" id="reviewTitle" name="title" maxlength="255" required>
                        </div>

                        <div class="mb-3">
                            <label for="reviewText" class="form-label">Your Review *</label>
                            <textarea class="form-control" id="reviewText" name="review_text" rows="5" maxlength="1000" required></textarea>
                            <div class="form-text">Share your experience with this product (max 1000 characters)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitReview()">Submit Review</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include __DIR__ . '/includes/footer.php'; ?>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/navbar.js"></script>
    <?php if (isLoggedIn()): ?>
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>
    <?php endif; ?>
    <script src="assets/js/product.js"></script>
</body>
</html>
