<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in', 'count' => 0]);
    exit();
}

$user_id = getUserId();

try {
    // Get unread notification count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $count = (int)($result['count'] ?? 0);
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching notification count: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'count' => 0
    ]);
}
?>
