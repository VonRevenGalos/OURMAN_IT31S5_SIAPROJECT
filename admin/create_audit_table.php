<?php
/**
 * Create Audit Table Script
 * Run this once to create the admin_audit_log table
 */

require_once '../db.php';

try {
    // Create the admin_audit_log table
    $sql = "
    CREATE TABLE IF NOT EXISTS `admin_audit_log` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `admin_id` int(11) NOT NULL,
      `admin_email` varchar(255) NOT NULL,
      `admin_name` varchar(255) NOT NULL,
      `action_type` enum('login','logout','view','create','update','delete','export','import','system') NOT NULL,
      `action_category` varchar(100) NOT NULL,
      `action_description` text NOT NULL,
      `target_type` varchar(100) DEFAULT NULL,
      `target_id` varchar(100) DEFAULT NULL,
      `old_values` json DEFAULT NULL,
      `new_values` json DEFAULT NULL,
      `ip_address` varchar(45) NOT NULL,
      `user_agent` text DEFAULT NULL,
      `page_url` varchar(500) DEFAULT NULL,
      `session_id` varchar(128) DEFAULT NULL,
      `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'low',
      `status` enum('success','failed','warning') NOT NULL DEFAULT 'success',
      `created_at` timestamp NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `admin_id` (`admin_id`),
      KEY `action_type` (`action_type`),
      KEY `action_category` (`action_category`),
      KEY `target_type` (`target_type`),
      KEY `target_id` (`target_id`),
      KEY `ip_address` (`ip_address`),
      KEY `severity` (`severity`),
      KEY `status` (`status`),
      KEY `created_at` (`created_at`),
      KEY `admin_email` (`admin_email`),
      CONSTRAINT `admin_audit_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $pdo->exec($sql);
    echo "✅ Admin audit log table created successfully!<br>";
    
    // Test the audit logging system
    session_start();
    
    // Simulate admin session for testing
    $_SESSION['admin_user_id'] = 1;
    $_SESSION['admin_email'] = 'test@admin.com';
    $_SESSION['admin_first_name'] = 'Test';
    $_SESSION['admin_last_name'] = 'Admin';
    
    require_once 'includes/audit_logger.php';
    
    // Test logging
    $testResult = logAdminActivity(
        'system',
        'setup',
        'Audit system initialized and tested',
        'system',
        'audit_table',
        null,
        null,
        'medium',
        'success'
    );
    
    if ($testResult) {
        echo "✅ Audit logging test successful!<br>";
    } else {
        echo "❌ Audit logging test failed!<br>";
    }
    
    // Clean up test session
    unset($_SESSION['admin_user_id'], $_SESSION['admin_email'], $_SESSION['admin_first_name'], $_SESSION['admin_last_name']);
    
    echo "<br><strong>Audit system is ready to use!</strong><br>";
    echo "<a href='audit.php'>View Audit Log</a> | <a href='dashboard.php'>Go to Dashboard</a>";
    
} catch (PDOException $e) {
    echo "❌ Error creating audit table: " . $e->getMessage();
}
?>
