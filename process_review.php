<?php
/**
 * Process Review Submissions and AJAX Requests
 */

require_once 'includes/session.php';
require_once 'db.php';
require_once 'includes/review_functions.php';

// Set JSON header for AJAX responses
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to submit a review.']);
    exit();
}

$currentUser = getCurrentUser();
$user_id = $currentUser['id'];

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_review':
        handleAddReview($pdo, $user_id);
        break;

    case 'edit_review':
        handleEditReview($pdo, $user_id);
        break;

    case 'delete_review':
        handleDeleteReview($pdo, $user_id);
        break;

    case 'mark_helpful':
        handleMarkHelpful($pdo, $user_id);
        break;

    case 'load_reviews':
        handleLoadReviews($pdo);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

/**
 * Handle adding a new review
 */
function handleAddReview($pdo, $user_id) {
    // Check if this is an edit (has review_id)
    $review_id = (int)($_POST['review_id'] ?? 0);
    if ($review_id > 0) {
        handleEditReview($pdo, $user_id);
        return;
    }

    // Validate input
    $product_id = (int)($_POST['product_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $review_text = trim($_POST['review_text'] ?? '');

    // Validation
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
        return;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5 stars.']);
        return;
    }

    if (empty($title) || strlen($title) < 5) {
        echo json_encode(['success' => false, 'message' => 'Review title must be at least 5 characters long.']);
        return;
    }

    if (empty($review_text) || strlen($review_text) < 10) {
        echo json_encode(['success' => false, 'message' => 'Review text must be at least 10 characters long.']);
        return;
    }

    if (strlen($title) > 255) {
        echo json_encode(['success' => false, 'message' => 'Review title is too long.']);
        return;
    }

    if (strlen($review_text) > 2000) {
        echo json_encode(['success' => false, 'message' => 'Review text is too long.']);
        return;
    }

    // Sanitize input
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $review_text = htmlspecialchars($review_text, ENT_QUOTES, 'UTF-8');

    // Add review
    $result = addProductReview($pdo, $user_id, $product_id, $rating, $title, $review_text);
    echo json_encode($result);
}

/**
 * Handle editing a review
 */
function handleEditReview($pdo, $user_id) {
    // Validate input
    $review_id = (int)($_POST['review_id'] ?? 0);
    $product_id = (int)($_POST['product_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $review_text = trim($_POST['review_text'] ?? '');

    // Validation
    if ($review_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid review ID.']);
        return;
    }

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
        return;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5 stars.']);
        return;
    }

    if (empty($title) || strlen($title) < 5) {
        echo json_encode(['success' => false, 'message' => 'Review title must be at least 5 characters long.']);
        return;
    }

    if (empty($review_text) || strlen($review_text) < 10) {
        echo json_encode(['success' => false, 'message' => 'Review text must be at least 10 characters long.']);
        return;
    }

    if (strlen($title) > 255) {
        echo json_encode(['success' => false, 'message' => 'Review title is too long.']);
        return;
    }

    if (strlen($review_text) > 2000) {
        echo json_encode(['success' => false, 'message' => 'Review text is too long.']);
        return;
    }

    // Verify the review belongs to the current user
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM product_reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$review || $review['user_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'You can only edit your own reviews.']);
            return;
        }

        // Sanitize input
        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $review_text = htmlspecialchars($review_text, ENT_QUOTES, 'UTF-8');

        // Update review
        $stmt = $pdo->prepare("
            UPDATE product_reviews
            SET rating = ?, title = ?, review_text = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$rating, $title, $review_text, $review_id, $user_id]);

        // Update rating summary
        updateProductRatingSummary($pdo, $product_id);

        echo json_encode(['success' => true, 'message' => 'Review updated successfully!']);

    } catch (PDOException $e) {
        error_log("Error updating review: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update review. Please try again.']);
    }
}

/**
 * Handle deleting a review
 */
function handleDeleteReview($pdo, $user_id) {
    $review_id = (int)($_POST['review_id'] ?? 0);

    if ($review_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid review ID.']);
        return;
    }

    try {
        // Verify the review belongs to the current user and get product_id
        $stmt = $pdo->prepare("SELECT user_id, product_id FROM product_reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$review || $review['user_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'You can only delete your own reviews.']);
            return;
        }

        // Delete the review
        $stmt = $pdo->prepare("DELETE FROM product_reviews WHERE id = ? AND user_id = ?");
        $stmt->execute([$review_id, $user_id]);

        // Update rating summary
        updateProductRatingSummary($pdo, $review['product_id']);

        echo json_encode(['success' => true, 'message' => 'Review deleted successfully!']);

    } catch (PDOException $e) {
        error_log("Error deleting review: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete review. Please try again.']);
    }
}

/**
 * Handle marking review as helpful
 */
function handleMarkHelpful($pdo, $user_id) {
    $review_id = (int)($_POST['review_id'] ?? 0);
    
    if ($review_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid review ID.']);
        return;
    }
    
    $result = markReviewHelpful($pdo, $user_id, $review_id);
    echo json_encode($result);
}

/**
 * Handle loading reviews with pagination and filtering
 */
function handleLoadReviews($pdo) {
    $product_id = (int)($_GET['product_id'] ?? 0);
    $page = (int)($_GET['page'] ?? 1);
    $rating_filter = $_GET['rating_filter'] ?? 'all';
    $sort_by = $_GET['sort_by'] ?? 'newest';
    $limit = 5; // Reviews per page
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
        return;
    }
    
    $offset = ($page - 1) * $limit;
    $reviews = getProductReviews($pdo, $product_id, $limit, $offset, $rating_filter, $sort_by);
    
    // Generate HTML for reviews
    $html = '';
    foreach ($reviews as $review) {
        $html .= generateReviewHTML($review);
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'has_more' => count($reviews) === $limit
    ]);
}

/**
 * Generate HTML for a single review
 */
function generateReviewHTML($review) {
    $reviewer_name = htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.');
    $time_ago = timeAgo($review['created_at']);
    $verified_badge = $review['is_verified_purchase'] ? ' • Verified Purchase' : '';
    $stars = generateStarRating($review['rating']);
    
    return '
    <div class="review-item" data-rating="' . $review['rating'] . '" data-date="' . $review['created_at'] . '">
        <div class="review-header">
            <div class="reviewer-info">
                <div class="reviewer-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="reviewer-details">
                    <div class="reviewer-name">' . $reviewer_name . '</div>
                    <div class="review-date">' . $time_ago . $verified_badge . '</div>
                </div>
            </div>
            <div class="review-rating">
                ' . $stars . '
            </div>
        </div>
        <div class="review-content">
            <h5 class="review-title">' . htmlspecialchars($review['title']) . '</h5>
            <p class="review-text">' . nl2br(htmlspecialchars($review['review_text'])) . '</p>
            <div class="review-helpful">
                <button class="helpful-btn" onclick="markHelpful(' . $review['id'] . ', this)">
                    <i class="fas fa-thumbs-up"></i>
                    <span>Helpful (' . $review['helpful_count'] . ')</span>
                </button>
            </div>
        </div>
    </div>';
}
?>
