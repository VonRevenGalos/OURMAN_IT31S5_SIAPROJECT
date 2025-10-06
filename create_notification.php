<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

// This endpoint can be called from admin side or internal processes
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$user_id = (int)($_POST['user_id'] ?? 0);
$type = $_POST['type'] ?? '';
$message = $_POST['message'] ?? '';

// Validate inputs
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

if (!in_array($type, ['order_update', 'promotion'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification type']);
    exit();
}

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit();
}

try {
    // Verify user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Create notification
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, message, is_read, created_at) 
        VALUES (?, ?, ?, 0, NOW())
    ");
    $result = $stmt->execute([$user_id, $type, $message]);
    
    if ($result) {
        $notification_id = $pdo->lastInsertId();
        
        // Get updated unread count for this user
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $count_stmt->execute([$user_id]);
        $unread_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification created successfully',
            'notification_id' => $notification_id,
            'unread_count' => $unread_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create notification']);
    }
    
} catch (PDOException $e) {
    error_log("Error creating notification: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
