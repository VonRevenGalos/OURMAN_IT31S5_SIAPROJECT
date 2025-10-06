<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to perform this action']);
    exit();
}

$user_id = getUserId();

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'mark_helpful') {
    $review_id = (int)($_POST['review_id'] ?? 0);
    
    if ($review_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
        exit();
    }
    
    try {
        // Check if review exists
        $review_check_stmt = $pdo->prepare("SELECT id FROM product_reviews WHERE id = ? AND is_approved = 1");
        $review_check_stmt->execute([$review_id]);
        if (!$review_check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Review not found']);
            exit();
        }
        
        // Check if user already marked this review as helpful
        $existing_stmt = $pdo->prepare("SELECT id, is_helpful FROM review_helpfulness WHERE review_id = ? AND user_id = ?");
        $existing_stmt->execute([$review_id, $user_id]);
        $existing = $existing_stmt->fetch(PDO::FETCH_ASSOC);
        
        $pdo->beginTransaction();
        
        if ($existing) {
            // Toggle existing helpfulness
            $new_helpful = !$existing['is_helpful'];
            $update_stmt = $pdo->prepare("UPDATE review_helpfulness SET is_helpful = ? WHERE id = ?");
            $update_stmt->execute([$new_helpful, $existing['id']]);
            $action_taken = $new_helpful ? 'added' : 'removed';
        } else {
            // Insert new helpfulness record
            $insert_stmt = $pdo->prepare("INSERT INTO review_helpfulness (review_id, user_id, is_helpful, created_at) VALUES (?, ?, 1, NOW())");
            $insert_stmt->execute([$review_id, $user_id]);
            $action_taken = 'added';
        }
        
        // Update helpful count in product_reviews table
        $count_stmt = $pdo->prepare("
            UPDATE product_reviews 
            SET helpful_count = (
                SELECT COUNT(*) FROM review_helpfulness 
                WHERE review_id = ? AND is_helpful = 1
            ) 
            WHERE id = ?
        ");
        $count_stmt->execute([$review_id, $review_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'action' => $action_taken,
            'message' => $action_taken === 'added' ? 'Marked as helpful' : 'Removed helpful mark'
        ]);
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        
        error_log("Error updating helpful status: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while updating helpful status']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
