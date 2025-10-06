<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
    exit();
}

$user_id = getUserId();

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and validate input data
$product_id = (int)($_POST['product_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$title = trim($_POST['title'] ?? '');
$review_text = trim($_POST['review_text'] ?? '');

// Validation
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5 stars']);
    exit();
}

if (empty($title) || strlen($title) > 255) {
    echo json_encode(['success' => false, 'message' => 'Review title is required and must be less than 255 characters']);
    exit();
}

if (empty($review_text) || strlen($review_text) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Review text is required and must be less than 1000 characters']);
    exit();
}

try {
    // Check if product exists
    $product_check_stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $product_check_stmt->execute([$product_id]);
    if (!$product_check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    // Check if user has purchased and received this product
    $purchase_check_stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'Delivered'
    ");
    $purchase_check_stmt->execute([$user_id, $product_id]);
    $has_purchased = $purchase_check_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

    if (!$has_purchased) {
        echo json_encode(['success' => false, 'message' => 'You can only review products you have purchased and received']);
        exit();
    }

    // Check if user already has a review for this product
    $existing_review_stmt = $pdo->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
    $existing_review_stmt->execute([$user_id, $product_id]);
    if ($existing_review_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
        exit();
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Insert the review
    $insert_stmt = $pdo->prepare("
        INSERT INTO product_reviews (product_id, user_id, rating, title, review_text, is_verified_purchase, is_approved, created_at) 
        VALUES (?, ?, ?, ?, ?, 1, 1, NOW())
    ");
    $insert_stmt->execute([$product_id, $user_id, $rating, $title, $review_text]);

    // Update or insert product rating summary
    $summary_check_stmt = $pdo->prepare("SELECT product_id FROM product_rating_summary WHERE product_id = ?");
    $summary_check_stmt->execute([$product_id]);
    
    if ($summary_check_stmt->fetch()) {
        // Update existing summary
        $update_summary_stmt = $pdo->prepare("
            UPDATE product_rating_summary 
            SET total_reviews = (
                    SELECT COUNT(*) FROM product_reviews 
                    WHERE product_id = ? AND is_approved = 1
                ),
                average_rating = (
                    SELECT AVG(rating) FROM product_reviews 
                    WHERE product_id = ? AND is_approved = 1
                ),
                rating_1_count = (
                    SELECT COUNT(*) FROM product_reviews 
                    WHERE product_id = ? AND rating = 1 AND is_approved = 1
                ),
                rating_2_count = (
                    SELECT COUNT(*) FROM product_reviews 
                    WHERE product_id = ? AND rating = 2 AND is_approved = 1
                ),
                rating_3_count = (
                    SELECT COUNT(*) FROM product_reviews 
                    WHERE product_id = ? AND rating = 3 AND is_approved = 1
                ),
                rating_4_count = (
                    SELECT COUNT(*) FROM product_reviews 
                    WHERE product_id = ? AND rating = 4 AND is_approved = 1
                ),
                rating_5_count = (
                    SELECT COUNT(*) FROM product_reviews 
                    WHERE product_id = ? AND rating = 5 AND is_approved = 1
                ),
                last_updated = NOW()
            WHERE product_id = ?
        ");
        $update_summary_stmt->execute([
            $product_id, $product_id, $product_id, $product_id, 
            $product_id, $product_id, $product_id, $product_id
        ]);
    } else {
        // Insert new summary
        $insert_summary_stmt = $pdo->prepare("
            INSERT INTO product_rating_summary (
                product_id, total_reviews, average_rating, 
                rating_1_count, rating_2_count, rating_3_count, rating_4_count, rating_5_count,
                last_updated
            ) 
            SELECT 
                ?, 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1_count,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2_count,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3_count,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4_count,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5_count,
                NOW()
            FROM product_reviews 
            WHERE product_id = ? AND is_approved = 1
        ");
        $insert_summary_stmt->execute([$product_id, $product_id]);
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Review submitted successfully! Thank you for your feedback.'
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Error submitting review: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while submitting your review. Please try again.']);
}
?>
