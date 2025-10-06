<?php
include 'db.php'; // ✅ Database connection
include 'includes/session.php'; // ✅ Session management

// ✅ Fetch featured products for carousel
$stmt = $pdo->query("SELECT * FROM products WHERE category IN ('running', 'sneakers', 'athletics') LIMIT 15");
$carouselItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch products for new showcase section
// Newest Products (based on date_added)
$stmt = $pdo->prepare("
    SELECT p.*, COALESCE(prs.average_rating, 0) as avg_rating, COALESCE(prs.total_reviews, 0) as review_count
    FROM products p
    LEFT JOIN product_rating_summary prs ON p.id = prs.product_id
    ORDER BY p.date_added DESC
    LIMIT 8
");
$stmt->execute();
$newestProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Best Selling Products (based on order quantity)
$stmt = $pdo->prepare("
    SELECT p.*, COALESCE(prs.average_rating, 0) as avg_rating, COALESCE(prs.total_reviews, 0) as review_count,
           COALESCE(SUM(oi.quantity), 0) as total_sold
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN product_rating_summary prs ON p.id = prs.product_id
    GROUP BY p.id
    ORDER BY total_sold DESC, p.date_added DESC
    LIMIT 8
");
$stmt->execute();
$bestSellingProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Featured Products (high ratings - 4.0+ average rating with at least 1 review)
$stmt = $pdo->prepare("
    SELECT p.*, prs.average_rating as avg_rating, prs.total_reviews as review_count
    FROM products p
    INNER JOIN product_rating_summary prs ON p.id = prs.product_id
    WHERE prs.average_rating >= 4.0 AND prs.total_reviews > 0
    ORDER BY prs.average_rating DESC, prs.total_reviews DESC, p.date_added DESC
    LIMIT 8
");
$stmt->execute();
$featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no featured products with high ratings, fallback to newest products
if (empty($featuredProducts)) {
    $featuredProducts = array_slice($newestProducts, 0, 8);
}

// Handle logout and account deletion success messages
$logoutSuccess = $_SESSION['logout_success'] ?? '';
$loginSuccess = $_SESSION['login_success'] ?? '';
$deletionSuccess = $_SESSION['deletion_success'] ?? '';
$accountDeleted = $_SESSION['account_deleted'] ?? false;
unset($_SESSION['logout_success'], $_SESSION['login_success'], $_SESSION['deletion_success'], $_SESSION['account_deleted']);
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>ShoeARizz Philippines - Step into Success</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/search.css">
    <link rel="stylesheet" href="assets/css/index.css">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero-section {
            height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/img/herobanner.webp');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            animation: fadeInUp 1s ease-out;
        }

        .hero-content p {
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        .hero-btn {
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease-out 0.6s both;
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        }

        .hero-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.4);
            color: white;
        }

        /* Carousel Section */
        .carousel-section {
            padding: 100px 0;
            background: #f8f9fa;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.2rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
        }

        .product-carousel .carousel-item img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .product-carousel .carousel-item img:hover {
            transform: scale(1.05);
        }

        /* Parallax Section */
        .parallax-section {
            height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/img/parallax.webp');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
        }

        .parallax-content h2 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .parallax-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.2rem;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .parallax-content h2 {
                font-size: 2.5rem;
            }

            .parallax-section,
            .hero-section {
                background-attachment: scroll;
            }
        }
    </style>
</head>

<body id="page-top">
<?php include __DIR__ . '/includes/navbar.php'; ?>

<?php if ($logoutSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 0; border-radius: 0; position: fixed; top: 80px; left: 0; right: 0; z-index: 1050;">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($logoutSuccess); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($loginSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 0; border-radius: 0; position: fixed; top: 80px; left: 0; right: 0; z-index: 1050;">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($loginSuccess); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($deletionSuccess): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert" style="margin: 0; border-radius: 0; position: fixed; top: 80px; left: 0; right: 0; z-index: 1050;">
        <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($deletionSuccess); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <h1>ShoeARizz</h1>
        <p>Step into Success with Premium Footwear</p>
        <a href="men.php" class="hero-btn">SHOP NOW</a>
    </div>
</section>
<!-- Featured Carousel Section -->
<section class="featured-carousel-section">
    <div class="container-fluid px-0">
        <div class="section-title text-center py-5">
            <h2>Featured Collection</h2>
            <p>Discover our handpicked selection of premium footwear</p>
        </div>

        <div id="featuredCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4000">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="assets/img/carousel1.webp" class="d-block w-100 featured-carousel-img" alt="Featured Shoe 1">
                </div>
                <div class="carousel-item">
                    <img src="assets/img/carousel2.webp" class="d-block w-100 featured-carousel-img" alt="Featured Shoe 2">
                </div>
                <div class="carousel-item">
                    <img src="assets/img/carousel3.webp" class="d-block w-100 featured-carousel-img" alt="Featured Shoe 3">
                </div>
                <div class="carousel-item">
                    <img src="assets/img/carousel4.webp" class="d-block w-100 featured-carousel-img" alt="Featured Shoe 4">
                </div>
                <div class="carousel-item">
                    <img src="assets/img/carousel5.webp" class="d-block w-100 featured-carousel-img" alt="Featured Shoe 5">
                </div>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#featuredCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#featuredCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>

            <div class="carousel-indicators">
                <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="2"></button>
                <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="3"></button>
                <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="4"></button>
            </div>
        </div>
    </div>
</section>
<!-- Products Showcase Section -->
<section class="products-showcase py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <!-- Section Header -->
        <div class="section-header text-center mb-5">
            <h2 class="section-title">Discover Our Collection</h2>
            <p class="section-subtitle">Premium footwear curated just for you</p>
            <div class="title-divider"></div>
        </div>

        <!-- Product Category Tabs -->
        <div class="product-tabs-container">
            <ul class="nav nav-pills product-tabs justify-content-center mb-4" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="newest-tab" data-bs-toggle="pill" data-bs-target="#newest" type="button" role="tab">
                        <i class="fas fa-star me-2"></i>
                        <span>Newest</span>
                        <small class="tab-count"><?php echo count($newestProducts); ?></small>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="bestselling-tab" data-bs-toggle="pill" data-bs-target="#bestselling" type="button" role="tab">
                        <i class="fas fa-fire me-2"></i>
                        <span>Best Selling</span>
                        <small class="tab-count"><?php echo count($bestSellingProducts); ?></small>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="featured-tab" data-bs-toggle="pill" data-bs-target="#featured" type="button" role="tab">
                        <i class="fas fa-crown me-2"></i>
                        <span>Featured</span>
                        <small class="tab-count"><?php echo count($featuredProducts); ?></small>
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="productTabsContent">
                <!-- Newest Products -->
                <div class="tab-pane fade show active" id="newest" role="tabpanel">
                    <div class="products-grid">
                        <?php foreach ($newestProducts as $product): ?>
                            <div class="product-card-modern">
                                <div class="product-badge">
                                    <span class="badge-new">New</span>
                                </div>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-link">
                                    <div class="product-image-container">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                             class="product-image-modern"
                                             alt="<?php echo htmlspecialchars($product['title']); ?>"
                                             loading="lazy">
                                        <div class="product-overlay">
                                            <div class="overlay-content">
                                                <i class="fas fa-eye"></i>
                                                <span>View Details</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="product-info-modern">
                                        <div class="product-category"><?php echo ucfirst(htmlspecialchars($product['category'])); ?></div>
                                        <h5 class="product-title-modern"><?php echo htmlspecialchars($product['title']); ?></h5>
                                        <div class="product-rating">
                                            <?php
                                            $rating = floatval($product['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $rating): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif ($i - 0.5 <= $rating): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif;
                                            endfor; ?>
                                            <span class="rating-count">(<?php echo $product['review_count']; ?>)</span>
                                        </div>
                                        <div class="product-price-modern">₱<?php echo number_format($product['price'], 2); ?></div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Best Selling Products -->
                <div class="tab-pane fade" id="bestselling" role="tabpanel">
                    <div class="products-grid">
                        <?php foreach ($bestSellingProducts as $product): ?>
                            <div class="product-card-modern">
                                <div class="product-badge">
                                    <span class="badge-bestseller">Best Seller</span>
                                    <?php if ($product['total_sold'] > 0): ?>
                                        <span class="badge-sold"><?php echo $product['total_sold']; ?> sold</span>
                                    <?php endif; ?>
                                </div>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-link">
                                    <div class="product-image-container">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                             class="product-image-modern"
                                             alt="<?php echo htmlspecialchars($product['title']); ?>"
                                             loading="lazy">
                                        <div class="product-overlay">
                                            <div class="overlay-content">
                                                <i class="fas fa-eye"></i>
                                                <span>View Details</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="product-info-modern">
                                        <div class="product-category"><?php echo ucfirst(htmlspecialchars($product['category'])); ?></div>
                                        <h5 class="product-title-modern"><?php echo htmlspecialchars($product['title']); ?></h5>
                                        <div class="product-rating">
                                            <?php
                                            $rating = floatval($product['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $rating): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif ($i - 0.5 <= $rating): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif;
                                            endfor; ?>
                                            <span class="rating-count">(<?php echo $product['review_count']; ?>)</span>
                                        </div>
                                        <div class="product-price-modern">₱<?php echo number_format($product['price'], 2); ?></div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Featured Products -->
                <div class="tab-pane fade" id="featured" role="tabpanel">
                    <div class="products-grid">
                        <?php foreach ($featuredProducts as $product): ?>
                            <div class="product-card-modern">
                                <div class="product-badge">
                                    <span class="badge-featured">Featured</span>
                                    <?php if ($product['avg_rating'] >= 4.0): ?>
                                        <span class="badge-rating">★ <?php echo number_format($product['avg_rating'], 1); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-link">
                                    <div class="product-image-container">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                             class="product-image-modern"
                                             alt="<?php echo htmlspecialchars($product['title']); ?>"
                                             loading="lazy">
                                        <div class="product-overlay">
                                            <div class="overlay-content">
                                                <i class="fas fa-eye"></i>
                                                <span>View Details</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="product-info-modern">
                                        <div class="product-category"><?php echo ucfirst(htmlspecialchars($product['category'])); ?></div>
                                        <h5 class="product-title-modern"><?php echo htmlspecialchars($product['title']); ?></h5>
                                        <div class="product-rating">
                                            <?php
                                            $rating = floatval($product['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $rating): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif ($i - 0.5 <= $rating): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif;
                                            endfor; ?>
                                            <span class="rating-count">(<?php echo $product['review_count']; ?>)</span>
                                        </div>
                                        <div class="product-price-modern">₱<?php echo number_format($product['price'], 2); ?></div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- View All Products Button -->
        <div class="text-center mt-5">
            <a href="men.php" class="btn btn-view-all">
                <span>View All Products</span>
                <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Parallax Section -->
<section class="parallax-section" id="parallax">
    <div class="parallax-content">
        <h2>Be Fancy in Your Own Style</h2>
        <p>Discover the perfect blend of comfort and elegance</p>
        <a href="men.php" class="hero-btn">EXPLORE COLLECTION</a>
    </div>
</section>



<!-- Video Section -->
<section class="py-5" style="background: #f8f9fa;">
    <div class="container">
        <div class="section-title">
            <h2>Experience ShoeARizz</h2>
            <p>Watch our latest collection in action</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="video-container position-relative">
                    <video
                        id="promoVideo"
                        autoplay
                        loop
                        muted
                        playsinline
                        preload="metadata"
                        class="responsive-video w-100"
                        style="border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-height: 70vh; object-fit: cover;"
                        onloadstart="this.volume=0"
                        oncanplay="this.muted=true;this.play().catch(e=>console.log('Autoplay prevented:', e))">
                        <source src="assets/img/promovideo.mp4" type="video/mp4">
                        <p class="text-center mt-3">
                            Your browser does not support the video tag.
                            <a href="assets/img/promovideo.mp4" target="_blank" class="text-primary">Click here to download the video</a>
                        </p>
                    </video>

                    <!-- Play/Pause Button Overlay for Mobile -->
                    <div class="video-controls position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10;">
                        <button id="videoPlayBtn" class="btn btn-light btn-lg rounded-circle d-none" style="width: 60px; height: 60px; opacity: 0.8;" onclick="toggleVideo()">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global Cart and Favorites JS -->
<script src="assets/js/global-cart.js"></script>
<script src="assets/js/global-favorites.js"></script>

<!-- Video Control Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('promoVideo');
    const playBtn = document.getElementById('videoPlayBtn');

    if (video && playBtn) {
        // Check if autoplay is working
        const checkAutoplay = () => {
            const promise = video.play();
            if (promise !== undefined) {
                promise.then(() => {
                    // Autoplay started successfully
                    playBtn.classList.add('d-none');
                }).catch(error => {
                    // Autoplay was prevented
                    console.log('Autoplay prevented, showing play button');
                    playBtn.classList.remove('d-none');
                    playBtn.innerHTML = '<i class="fas fa-play"></i>';
                });
            }
        };

        // Try autoplay when video metadata is loaded
        video.addEventListener('loadedmetadata', checkAutoplay);

        // Handle video play/pause
        video.addEventListener('play', () => {
            playBtn.innerHTML = '<i class="fas fa-pause"></i>';
            setTimeout(() => playBtn.classList.add('d-none'), 2000);
        });

        video.addEventListener('pause', () => {
            playBtn.innerHTML = '<i class="fas fa-play"></i>';
            playBtn.classList.remove('d-none');
        });

        // Handle click on video container
        video.addEventListener('click', toggleVideo);

        // Handle visibility change (pause when tab is not active)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                video.pause();
            } else if (video.paused) {
                video.play().catch(e => console.log('Play prevented:', e));
            }
        });

        // Intersection Observer for autoplay when video comes into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    video.play().catch(e => console.log('Autoplay prevented:', e));
                } else {
                    video.pause();
                }
            });
        }, { threshold: 0.5 });

        observer.observe(video);
    }
});

function toggleVideo() {
    const video = document.getElementById('promoVideo');
    const playBtn = document.getElementById('videoPlayBtn');

    if (video.paused) {
        video.play().then(() => {
            playBtn.innerHTML = '<i class="fas fa-pause"></i>';
            setTimeout(() => playBtn.classList.add('d-none'), 2000);
        }).catch(e => {
            console.log('Play failed:', e);
            playBtn.classList.remove('d-none');
        });
    } else {
        video.pause();
        playBtn.innerHTML = '<i class="fas fa-play"></i>';
        playBtn.classList.remove('d-none');
    }
}
</script>

<!-- Additional Styles for New Components -->
<style>
    /* Product Cards */
    .product-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
    }

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    }

    .product-image {
        width: 100%;
        height: 300px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .product-card:hover .product-image {
        transform: scale(1.05);
    }

    .product-info {
        padding: 20px;
    }

    .product-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .product-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #007bff;
        margin: 0;
    }

    /* Modern Products Showcase */
    .products-showcase {
        position: relative;
        overflow: hidden;
    }

    .section-header {
        position: relative;
        z-index: 2;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1rem;
        position: relative;
    }

    .section-subtitle {
        font-size: 1.1rem;
        color: #6c757d;
        margin-bottom: 2rem;
    }

    .title-divider {
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, #007bff, #0056b3);
        margin: 0 auto;
        border-radius: 2px;
    }

    /* Product Tabs */
    .product-tabs-container {
        position: relative;
    }

    .product-tabs {
        border: none;
        background: white;
        border-radius: 50px;
        padding: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 3rem;
    }

    .product-tabs .nav-link {
        border: none;
        border-radius: 40px;
        padding: 12px 24px;
        font-weight: 600;
        color: #6c757d;
        background: transparent;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .product-tabs .nav-link:hover {
        color: #007bff;
        background: rgba(0,123,255,0.1);
    }

    .product-tabs .nav-link.active {
        color: white;
        background: linear-gradient(135deg, #007bff, #0056b3);
        box-shadow: 0 5px 15px rgba(0,123,255,0.3);
    }

    .tab-count {
        background: rgba(255,255,255,0.2);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        margin-left: 4px;
    }

    .product-tabs .nav-link.active .tab-count {
        background: rgba(255,255,255,0.3);
    }

    /* Products Grid */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        padding: 1rem 0;
    }

    .product-card-modern {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        transition: all 0.4s ease;
        position: relative;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .product-card-modern:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .product-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 3;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .product-badge span {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-new {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .badge-bestseller {
        background: linear-gradient(135deg, #fd7e14, #e63946);
        color: white;
    }

    .badge-featured {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: white;
    }

    .badge-sold, .badge-rating {
        background: rgba(0,0,0,0.7);
        color: white;
        font-size: 0.7rem;
    }

    .product-image-container {
        position: relative;
        overflow: hidden;
        height: 280px;
    }

    .product-image-modern {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .product-card-modern:hover .product-image-modern {
        transform: scale(1.1);
    }

    .product-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,123,255,0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s ease;
    }

    .product-card-modern:hover .product-overlay {
        opacity: 1;
    }

    .overlay-content {
        text-align: center;
        color: white;
        transform: translateY(20px);
        transition: transform 0.3s ease;
    }

    .product-card-modern:hover .overlay-content {
        transform: translateY(0);
    }

    .overlay-content i {
        font-size: 2rem;
        margin-bottom: 8px;
        display: block;
    }

    .product-info-modern {
        padding: 1.5rem;
    }

    .product-category {
        font-size: 0.8rem;
        color: #007bff;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    .product-title-modern {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 12px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-rating {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
    }

    .product-rating i {
        color: #ffc107;
        font-size: 0.9rem;
    }

    .rating-count {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .product-price-modern {
        font-size: 1.3rem;
        font-weight: 700;
        color: #007bff;
        margin: 0;
    }

    .product-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .product-link:hover {
        color: inherit;
        text-decoration: none;
    }

    /* View All Button */
    .btn-view-all {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        border: none;
        padding: 15px 40px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 8px 25px rgba(0,123,255,0.3);
    }

    .btn-view-all:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(0,123,255,0.4);
        color: white;
        text-decoration: none;
    }

    /* Responsive Design for Products Showcase */
    @media (max-width: 768px) {
        .section-title {
            font-size: 2rem;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .product-tabs {
            flex-direction: column;
            padding: 12px;
            border-radius: 20px;
        }

        .product-tabs .nav-link {
            padding: 12px 20px;
            margin-bottom: 8px;
            justify-content: center;
        }

        .product-tabs .nav-link:last-child {
            margin-bottom: 0;
        }

        .product-image-container {
            height: 220px;
        }

        .product-info-modern {
            padding: 1.25rem;
        }

        .btn-view-all {
            padding: 12px 30px;
            font-size: 1rem;
        }
    }

    @media (max-width: 576px) {
        .products-grid {
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }

        .section-title {
            font-size: 1.75rem;
        }

        .section-subtitle {
            font-size: 1rem;
        }

        .product-tabs .nav-link {
            font-size: 0.9rem;
            padding: 10px 16px;
        }

        .tab-count {
            font-size: 0.7rem;
        }

        .product-image-container {
            height: 200px;
        }

        .product-badge {
            top: 10px;
            left: 10px;
        }

        .product-badge span {
            padding: 4px 8px;
            font-size: 0.7rem;
        }
    }


    /* Parallax Effect */
    .parallax-section {
        background-attachment: fixed;
    }

    @media (max-width: 768px) {
        .parallax-section {
            background-attachment: scroll;
        }
    }
</style>

<script>
// Parallax scrolling effect - Only for parallax section
window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const parallaxSection = document.querySelector('.parallax-section');

    if (parallaxSection) {
        const rect = parallaxSection.getBoundingClientRect();
        const isVisible = rect.top < window.innerHeight && rect.bottom > 0;

        if (isVisible) {
            const speed = scrolled * 0.3;
            parallaxSection.style.backgroundPosition = `center ${speed}px`;
        }
    }
});

// Auto-start carousels with infinite loop
document.addEventListener('DOMContentLoaded', function() {
    // Featured carousel - fade effect
    const featuredCarousel = new bootstrap.Carousel(document.getElementById('featuredCarousel'), {
        interval: 4000,
        wrap: true,
        pause: false
    });

    // Product tabs functionality
    const productTabs = document.querySelectorAll('#productTabs button');
    productTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            // Add animation to product cards when tab is shown
            const targetPane = document.querySelector(e.target.getAttribute('data-bs-target'));
            const cards = targetPane.querySelectorAll('.product-card-modern');

            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    });
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

</body>
</html>