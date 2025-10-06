<?php
/**
 * Get Reviews Section HTML for Dynamic Updates
 */

require_once 'includes/session.php';
require_once 'db.php';
require_once 'includes/review_functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$product_id = (int)($_GET['product_id'] ?? 0);

if ($product_id <= 0) {
    echo '<div class="alert alert-danger">Invalid product ID</div>';
    exit();
}

// Get current user
$currentUser = getCurrentUser();

// Get review data for this product
$rating_summary = getProductRatingSummary($pdo, $product_id);
$reviews = getProductReviews($pdo, $product_id, 5, 0); // Get first 5 reviews
$user_can_review = false;
$user_has_reviewed = false;

if ($currentUser) {
    $user_has_reviewed = hasUserReviewedProduct($pdo, $currentUser['id'], $product_id);
    $user_can_review = !$user_has_reviewed; // Can review if hasn't reviewed yet
}
?>

<div class="row">
    <div class="col-12">
        <div class="reviews-header">
            <h3 class="reviews-title">Customer Reviews & Feedback</h3>
            <div class="reviews-summary">
                <div class="rating-overview">
                    <div class="average-rating">
                        <span class="rating-number"><?php echo number_format($rating_summary['average_rating'], 1); ?></span>
                        <?php echo generateStarRating(round($rating_summary['average_rating'])); ?>
                        <span class="rating-count">Based on <?php echo $rating_summary['total_reviews']; ?> review<?php echo $rating_summary['total_reviews'] != 1 ? 's' : ''; ?></span>
                    </div>
                    <?php if ($rating_summary['total_reviews'] > 0): ?>
                    <div class="rating-breakdown">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="rating-bar">
                            <span class="rating-label"><?php echo $i; ?> star</span>
                            <div class="bar-container">
                                <div class="bar-fill" style="width: <?php echo $rating_summary['total_reviews'] > 0 ? ($rating_summary["rating_{$i}_count"] / $rating_summary['total_reviews']) * 100 : 0; ?>%"></div>
                            </div>
                            <span class="rating-count-small"><?php echo $rating_summary["rating_{$i}_count"]; ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
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
                    <i class="fas fa-comment-slash"></i>
                </div>
                <h4>No reviews yet</h4>
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
                                <div class="review-date"><?php echo timeAgo($review['created_at']); ?><?php echo $review['is_verified_purchase'] ? ' • Verified Purchase' : ''; ?></div>
                            </div>
                        </div>
                        <div class="review-rating">
                            <?php echo generateStarRating($review['rating']); ?>
                        </div>
                        <?php if ($currentUser && $currentUser['id'] == $review['user_id']): ?>
                        <div class="review-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="editReview(<?php echo $review['id']; ?>, '<?php echo htmlspecialchars($review['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($review['review_text'], ENT_QUOTES); ?>', <?php echo $review['rating']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteReview(<?php echo $review['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="review-content">
                        <h5 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h5>
                        <p class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                        <div class="review-helpful">
                            <button class="helpful-btn" onclick="markHelpful(<?php echo $review['id']; ?>, this)" <?php echo !isLoggedIn() ? 'disabled title="Please log in to mark reviews as helpful"' : ''; ?>>
                                <i class="fas fa-thumbs-up"></i>
                                <span>Helpful (<?php echo $review['helpful_count']; ?>)</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Load More Reviews -->
        <?php if (count($reviews) >= 5): ?>
        <div class="load-more-section">
            <button class="btn btn-load-more" onclick="loadMoreReviews(<?php echo $product_id; ?>)">
                <i class="fas fa-plus"></i>
                Load More Reviews
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Write Review Section -->
        <div class="write-review-section">
            <h4>Write a Review</h4>
            <?php if (!isLoggedIn()): ?>
                <p>Please <a href="login.php">log in</a> to write a review</p>
            <?php elseif ($user_has_reviewed): ?>
                <p>Thank you for your review! You have already reviewed this product.</p>
            <?php else: ?>
                <p>Share your experience with this product</p>
                <button class="btn btn-write-review" onclick="openReviewModal(<?php echo $product_id; ?>)">
                    <i class="fas fa-edit"></i>
                    Write a Review
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
