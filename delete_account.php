<?php
/**
 * Delete Account Handler
 * Permanently deletes user account and all associated data
 */

require_once 'includes/session.php';

// Require login
requireLogin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit();
}

// Verify CSRF token    
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['profile_error'] = "Invalid security token. Please try again.";
    header('Location: profile.php');
    exit();
}

// Verify confirmation
if (!isset($_POST['confirm_delete']) || $_POST['confirm_delete'] !== 'DELETE') {
    $_SESSION['profile_error'] = "Account deletion not confirmed properly.";
    header('Location: profile.php');
    exit();
}

// Additional security: Check if user has recent activity (prevent hijacked sessions)
$currentTime = time();
$sessionStartTime = $_SESSION['login_time'] ?? $currentTime;
$sessionDuration = $currentTime - $sessionStartTime;

// If session is older than 24 hours, require re-authentication
if ($sessionDuration > 86400) { // 24 hours
    $_SESSION['profile_error'] = "For security reasons, please log in again before deleting your account.";
    header('Location: logout.php');
    exit();
}

// Prevent admin users from self-deleting (they should use admin panel)
if (isset($currentUser['role']) && $currentUser['role'] === 'admin') {
    $_SESSION['profile_error'] = "Admin accounts cannot be deleted through this method. Please contact system administrator.";
    header('Location: profile.php');
    exit();
}

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

try {
    // Start transaction for data integrity
    $pdo->beginTransaction();
    
    // Log the account deletion attempt
    error_log("Account deletion initiated for user ID: {$userId}, Email: {$currentUser['email']}, IP: " . $_SERVER['REMOTE_ADDR']);

    // Log to admin audit system if available (for tracking user self-deletions)
    try {
        if (file_exists('admin/includes/audit_logger.php')) {
            require_once 'admin/includes/audit_logger.php';
            // Create a special audit entry for user self-deletion
            $auditData = [
                'admin_id' => $userId,
                'admin_email' => $currentUser['email'],
                'admin_name' => $currentUser['first_name'] . ' ' . $currentUser['last_name'],
                'action_type' => 'delete',
                'action_category' => 'self_service',
                'action_description' => 'User self-deleted their account',
                'target_type' => 'user',
                'target_id' => $userId,
                'old_values' => json_encode([
                    'email' => $currentUser['email'],
                    'username' => $currentUser['username'],
                    'role' => $currentUser['role'] ?? 'buyer'
                ]),
                'new_values' => null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'page_url' => $_SERVER['REQUEST_URI'] ?? '/delete_account.php',
                'session_id' => session_id(),
                'severity' => 'high',
                'status' => 'success'
            ];

            // Insert directly into audit log
            $stmt = $pdo->prepare("
                INSERT INTO admin_audit_log (
                    admin_id, admin_email, admin_name, action_type, action_category,
                    action_description, target_type, target_id, old_values, new_values,
                    ip_address, user_agent, page_url, session_id, severity, status
                ) VALUES (
                    :admin_id, :admin_email, :admin_name, :action_type, :action_category,
                    :action_description, :target_type, :target_id, :old_values, :new_values,
                    :ip_address, :user_agent, :page_url, :session_id, :severity, :status
                )
            ");
            $stmt->execute($auditData);
        }
    } catch (Exception $e) {
        // Don't fail the deletion if audit logging fails
        error_log("Failed to log account deletion to audit system: " . $e->getMessage());
    }
    
    // Delete user data in proper order (foreign key constraints will handle most of this automatically)
    // Note: Most tables have CASCADE DELETE constraints, but we'll be explicit for important data
    
    // 1. Delete user addresses
    $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE user_id = ?");
    $stmt->execute([$userId]);
    $addressesDeleted = $stmt->rowCount();
    
    // 2. Delete cart items
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartItemsDeleted = $stmt->rowCount();
    
    // 3. Delete favorites
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ?");
    $stmt->execute([$userId]);
    $favoritesDeleted = $stmt->rowCount();
    
    // 4. Delete notifications (if table exists)
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->execute([$userId]);
        $notificationsDeleted = $stmt->rowCount();
    } catch (PDOException $e) {
        // Table might not exist, continue
        $notificationsDeleted = 0;
    }
    
    // 5. Delete chat messages and sessions (if tables exist)
    try {
        $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE sender_id = ?");
        $stmt->execute([$userId]);
        $chatMessagesDeleted = $stmt->rowCount();
        
        $stmt = $pdo->prepare("DELETE FROM chat_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $chatSessionsDeleted = $stmt->rowCount();
    } catch (PDOException $e) {
        // Tables might not exist, continue
        $chatMessagesDeleted = 0;
        $chatSessionsDeleted = 0;
    }
    
    // 6. Delete product reviews and helpfulness (if tables exist)
    try {
        $stmt = $pdo->prepare("DELETE FROM review_helpfulness WHERE user_id = ?");
        $stmt->execute([$userId]);
        $reviewHelpfulnessDeleted = $stmt->rowCount();
        
        $stmt = $pdo->prepare("DELETE FROM product_reviews WHERE user_id = ?");
        $stmt->execute([$userId]);
        $reviewsDeleted = $stmt->rowCount();
    } catch (PDOException $e) {
        // Tables might not exist, continue
        $reviewHelpfulnessDeleted = 0;
        $reviewsDeleted = 0;
    }
    
    // 7. Handle orders - we'll keep order records for business purposes but anonymize them
    // Update orders to remove personal connection but keep for business records
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET user_id = NULL, 
            shipping_address_id = NULL,
            updated_at = NOW()
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $ordersAnonymized = $stmt->rowCount();
    
    // 8. Finally, delete the user account
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userDeleted = $stmt->rowCount();
    
    if ($userDeleted === 0) {
        throw new Exception("Failed to delete user account");
    }
    
    // Commit the transaction
    $pdo->commit();
    
    // Log successful deletion
    error_log("Account successfully deleted for user ID: {$userId}, Email: {$currentUser['email']}. " .
              "Data deleted: {$addressesDeleted} addresses, {$cartItemsDeleted} cart items, " .
              "{$favoritesDeleted} favorites, {$notificationsDeleted} notifications, " .
              "{$chatMessagesDeleted} chat messages, {$chatSessionsDeleted} chat sessions, " .
              "{$reviewsDeleted} reviews, {$reviewHelpfulnessDeleted} review helpfulness. " .
              "{$ordersAnonymized} orders anonymized.");
    
    // Clear all session data
    session_unset();
    session_destroy();
    
    // Start new session for success message
    session_start();
    $_SESSION['account_deleted'] = true;
    $_SESSION['deletion_success'] = "Your account has been permanently deleted. Thank you for using ShoeARizz.";
    
    // Redirect to home page
    header('Location: index.php');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollback();
    
    // Log error
    error_log("Account deletion failed for user ID: {$userId}, Email: {$currentUser['email']}. Error: " . $e->getMessage());
    
    // Set error message
    $_SESSION['profile_error'] = "Failed to delete account. Please try again or contact support.";
    header('Location: profile.php');
    exit();
}
?>
