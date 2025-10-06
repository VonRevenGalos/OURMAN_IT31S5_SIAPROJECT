<?php
/**
 * Admin Audit Logger
 * Comprehensive logging system for all admin activities
 */

// Include database connection
require_once __DIR__ . '/../../db.php';

/**
 * Log admin activity to the audit table
 * 
 * @param string $actionType - Type of action (login, logout, view, create, update, delete, export, import, system)
 * @param string $actionCategory - Category of action (authentication, products, orders, users, etc.)
 * @param string $actionDescription - Detailed description of the action
 * @param string|null $targetType - Type of target object (product, order, user, etc.)
 * @param string|null $targetId - ID of the target object
 * @param array|null $oldValues - Previous values (for updates)
 * @param array|null $newValues - New values (for creates/updates)
 * @param string $severity - Severity level (low, medium, high, critical)
 * @param string $status - Status of action (success, failed, warning)
 * @return bool - True if logged successfully, false otherwise
 */
function logAdminActivity(
    $actionType,
    $actionCategory,
    $actionDescription,
    $targetType = null,
    $targetId = null,
    $oldValues = null,
    $newValues = null,
    $severity = 'low',
    $status = 'success'
) {
    global $pdo;
    
    try {
        // Get current admin user info
        $adminInfo = getCurrentAdminUserForAudit();
        if (!$adminInfo) {
            return false; // Can't log without admin info
        }
        
        // Prepare audit data
        $auditData = [
            'admin_id' => $adminInfo['id'],
            'admin_email' => $adminInfo['email'],
            'admin_name' => $adminInfo['first_name'] . ' ' . $adminInfo['last_name'],
            'action_type' => $actionType,
            'action_category' => $actionCategory,
            'action_description' => $actionDescription,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'page_url' => $_SERVER['REQUEST_URI'] ?? null,
            'session_id' => session_id(),
            'severity' => $severity,
            'status' => $status
        ];
        
        // Insert into audit log
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
        
        return $stmt->execute($auditData);
        
    } catch (PDOException $e) {
        // Log to error log as fallback
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current admin user info for audit logging
 * Works with different admin session formats
 */
function getCurrentAdminUserForAudit() {
    // Check for temporary audit data (for failed logins)
    if (isset($_SESSION['temp_audit_email'])) {
        return [
            'id' => $_SESSION['temp_audit_id'] ?? 0,
            'email' => $_SESSION['temp_audit_email'],
            'first_name' => $_SESSION['temp_audit_name'] ?? 'Unknown',
            'last_name' => ''
        ];
    }

    // Try to get admin info from session
    if (isset($_SESSION['admin_user_id'])) {
        return [
            'id' => $_SESSION['admin_user_id'],
            'email' => $_SESSION['admin_email'] ?? 'unknown',
            'first_name' => $_SESSION['admin_first_name'] ?? 'Unknown',
            'last_name' => $_SESSION['admin_last_name'] ?? 'Admin'
        ];
    }

    // Try alternative session format
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    return null;
}

/**
 * Convenience functions for common audit actions
 */

function auditLogin($status = 'success', $details = '') {
    return logAdminActivity(
        'login',
        'authentication',
        'Admin login attempt' . ($details ? ': ' . $details : ''),
        null,
        null,
        null,
        null,
        'medium',
        $status
    );
}

function auditLogout() {
    return logAdminActivity(
        'logout',
        'authentication',
        'Admin logout',
        null,
        null,
        null,
        null,
        'low',
        'success'
    );
}

function auditPageView($pageName) {
    return logAdminActivity(
        'view',
        'navigation',
        'Accessed admin page: ' . $pageName,
        'page',
        $pageName,
        null,
        null,
        'low',
        'success'
    );
}

function auditProductAction($action, $productId, $oldData = null, $newData = null) {
    $descriptions = [
        'create' => 'Created new product',
        'update' => 'Updated product information',
        'delete' => 'Deleted product',
        'view' => 'Viewed product details'
    ];
    
    return logAdminActivity(
        $action,
        'products',
        $descriptions[$action] ?? 'Product action',
        'product',
        $productId,
        $oldData,
        $newData,
        $action === 'delete' ? 'high' : 'medium'
    );
}

function auditOrderAction($action, $orderId, $oldData = null, $newData = null) {
    $descriptions = [
        'create' => 'Created new order',
        'update' => 'Updated order status/information',
        'delete' => 'Cancelled/deleted order',
        'view' => 'Viewed order details'
    ];
    
    return logAdminActivity(
        $action,
        'orders',
        $descriptions[$action] ?? 'Order action',
        'order',
        $orderId,
        $oldData,
        $newData,
        $action === 'delete' ? 'high' : 'medium'
    );
}

function auditUserAction($action, $userId, $oldData = null, $newData = null) {
    $descriptions = [
        'create' => 'Created new user account',
        'update' => 'Updated user information',
        'delete' => 'Deleted user account',
        'view' => 'Viewed user details',
        'suspend' => 'Suspended user account',
        'unsuspend' => 'Unsuspended user account'
    ];
    
    return logAdminActivity(
        $action,
        'users',
        $descriptions[$action] ?? 'User action',
        'user',
        $userId,
        $oldData,
        $newData,
        in_array($action, ['delete', 'suspend']) ? 'high' : 'medium'
    );
}

function auditSystemAction($action, $description, $severity = 'medium') {
    return logAdminActivity(
        'system',
        'system',
        $description,
        'system',
        null,
        null,
        null,
        $severity
    );
}

function auditDataExport($type, $description) {
    return logAdminActivity(
        'export',
        'data',
        'Exported data: ' . $description,
        $type,
        null,
        null,
        null,
        'medium'
    );
}

function auditDataImport($type, $description, $status = 'success') {
    return logAdminActivity(
        'import',
        'data',
        'Imported data: ' . $description,
        $type,
        null,
        null,
        null,
        'medium',
        $status
    );
}

/**
 * Get audit statistics for dashboard
 */
function getAuditStats() {
    global $pdo;
    
    try {
        $stats = [];
        
        // Total activities today
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM admin_audit_log 
            WHERE DATE(created_at) = CURDATE()
        ");
        $stats['today_activities'] = $stmt->fetchColumn();
        
        // Total activities this week
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM admin_audit_log 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stats['week_activities'] = $stmt->fetchColumn();
        
        // Critical activities today
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM admin_audit_log 
            WHERE DATE(created_at) = CURDATE() 
            AND severity = 'critical'
        ");
        $stats['critical_today'] = $stmt->fetchColumn();
        
        // Failed activities today
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM admin_audit_log 
            WHERE DATE(created_at) = CURDATE() 
            AND status = 'failed'
        ");
        $stats['failed_today'] = $stmt->fetchColumn();
        
        return $stats;
        
    } catch (PDOException $e) {
        return [
            'today_activities' => 0,
            'week_activities' => 0,
            'critical_today' => 0,
            'failed_today' => 0
        ];
    }
}
?>
