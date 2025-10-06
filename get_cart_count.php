<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    $user_id = getUserId();
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => (int)($result['count'] ?? 0)]);
} catch (PDOException $e) {
    error_log("Get cart count error: " . $e->getMessage());
    echo json_encode(['count' => 0]);
}
?>
