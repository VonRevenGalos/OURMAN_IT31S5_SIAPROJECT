<?php
require_once 'includes/session.php';
require_once 'db.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = getUserId();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_as_read':
                $notification_id = (int)$_POST['notification_id'];
                if ($notification_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
                    exit();
                }

                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$notification_id, $user_id]);

                // Get updated unread count
                $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
                $count_stmt->execute([$user_id]);
                $unread_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

                echo json_encode([
                    'success' => $result,
                    'unread_count' => $unread_count
                ]);
                exit();

            case 'mark_all_read':
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
                $result = $stmt->execute([$user_id]);
                echo json_encode([
                    'success' => $result,
                    'unread_count' => 0
                ]);
                exit();

            case 'delete_notification':
                $notification_id = (int)$_POST['notification_id'];
                if ($notification_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
                    exit();
                }

                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$notification_id, $user_id]);

                // Get updated unread count
                $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
                $count_stmt->execute([$user_id]);
                $unread_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

                echo json_encode([
                    'success' => $result,
                    'unread_count' => $unread_count
                ]);
                exit();
        }
    }
}

// Fetch user's notifications
try {
    $stmt = $pdo->prepare("
        SELECT n.*,
               CASE
                   WHEN n.type = 'order_update' AND n.message LIKE '%Order #%' THEN
                       SUBSTRING(n.message, LOCATE('Order #', n.message), 10)
                   ELSE 'General'
               END as order_reference,
               CASE
                   WHEN n.type = 'order_update' AND n.message LIKE '%Order #%' THEN
                       CAST(SUBSTRING(n.message, LOCATE('Order #', n.message) + 7, LOCATE(' ', n.message, LOCATE('Order #', n.message) + 7) - LOCATE('Order #', n.message) - 7) AS UNSIGNED)
                   ELSE NULL
               END as order_id
        FROM notifications n
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unread count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $count_stmt->execute([$user_id]);
    $unread_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

} catch (PDOException $e) {
    error_log("Notifications fetch error: " . $e->getMessage());
    $notifications = [];
    $unread_count = 0;
}

// Get current user
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - ShoeARizz</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
</head>
<body>
    <!-- Include Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5 pt-4">
        <div class="row">
            <div class="col-12">
                <!-- Page Header -->
                <div class="page-header mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="page-title">
                                <i class="fas fa-bell me-2"></i>Notifications
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo $unread_count; ?> unread</span>
                                <?php endif; ?>
                            </h2>
                            <p class="page-subtitle">Stay updated with your order status and important updates</p>
                        </div>
                        <?php if (!empty($notifications)): ?>
                        <div class="page-actions">
                            <button type="button" class="btn btn-outline-primary" onclick="markAllAsRead()">
                                <i class="fas fa-check-double"></i> Mark All Read
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="notifications-container">
                    <?php if (empty($notifications)): ?>
                        <!-- Empty State -->
                        <div class="empty-state text-center py-5">
                            <div class="empty-icon mb-3">
                                <i class="fas fa-bell-slash fa-3x text-muted"></i>
                            </div>
                            <h4 class="empty-title">No notifications yet</h4>
                            <p class="empty-text text-muted">
                                You'll receive notifications here when there are updates to your orders or important announcements.
                            </p>
                            <a href="index.php" class="btn btn-primary mt-3">
                                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Notifications List -->
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>"
                                     data-notification-id="<?php echo $notification['id']; ?>">
                                    <div class="notification-content">
                                        <div class="notification-header">
                                            <div class="notification-type">
                                                <?php if ($notification['type'] === 'order_update'): ?>
                                                    <i class="fas fa-box text-primary"></i>
                                                    <span class="type-label">Order Update</span>
                                                <?php else: ?>
                                                    <i class="fas fa-info-circle text-info"></i>
                                                    <span class="type-label">Announcement</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="notification-time">
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>

                                        <div class="notification-body">
                                            <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <?php if ($notification['type'] === 'order_update'): ?>
                                                <div class="notification-reference">
                                                    <small class="text-muted">
                                                        <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($notification['order_reference']); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="notification-actions">
                                            <?php if ($notification['type'] === 'order_update'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="viewOrderDetails('<?php echo htmlspecialchars($notification['order_reference'], ENT_QUOTES); ?>')"
                                                        data-order-ref="<?php echo htmlspecialchars($notification['order_reference'], ENT_QUOTES); ?>">
                                                    <i class="fas fa-eye"></i> View Order
                                                </button>

                                                <?php
                                                // Check if this is a delivered order notification and user can review
                                                if ($notification['order_id'] && strpos($notification['message'], 'Delivered') !== false):
                                                    // Check if order exists and is delivered
                                                    $order_check_stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ? AND status = 'Delivered'");
                                                    $order_check_stmt->execute([$notification['order_id'], $user_id]);
                                                    $order_exists = $order_check_stmt->fetch(PDO::FETCH_ASSOC);

                                                    if ($order_exists):
                                                        // Get order items for review
                                                        $items_stmt = $pdo->prepare("
                                                            SELECT oi.product_id, p.title
                                                            FROM order_items oi
                                                            JOIN products p ON oi.product_id = p.id
                                                            WHERE oi.order_id = ?
                                                        ");
                                                        $items_stmt->execute([$notification['order_id']]);
                                                        $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                        foreach ($order_items as $item):
                                                            // Check if user already reviewed this product
                                                            $review_check_stmt = $pdo->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
                                                            $review_check_stmt->execute([$user_id, $item['product_id']]);
                                                            $existing_review = $review_check_stmt->fetch(PDO::FETCH_ASSOC);

                                                            if (!$existing_review):
                                                ?>
                                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                                        onclick="reviewProduct(<?php echo $item['product_id']; ?>)"
                                                                        title="Review <?php echo htmlspecialchars($item['title']); ?>">
                                                                    <i class="fas fa-star"></i> Review Product
                                                                </button>
                                                <?php
                                                            endif;
                                                        endforeach;
                                                    endif;
                                                endif;
                                                ?>
                                            <?php endif; ?>

                                            <?php if (!$notification['is_read']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                                    <i class="fas fa-check"></i> Mark Read
                                                </button>
                                            <?php endif; ?>

                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>

                                    <?php if (!$notification['is_read']): ?>
                                        <div class="unread-indicator"></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Global Scripts -->
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>
    <script src="assets/js/global-notifications.js"></script>

    <!-- Page Scripts -->
    <script src="assets/js/notifications.js"></script>
</body>
</html>