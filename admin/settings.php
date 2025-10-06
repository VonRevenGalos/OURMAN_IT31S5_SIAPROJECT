<?php
require_once 'includes/admin_auth.php';
require_once '../db.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    header('Location: adminlogin.php');
    exit();
}

// Validate admin session
if (!validateAdminSession()) {
    header('Location: adminlogin.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_payment_settings':
                $codEnabled = isset($_POST['cod_enabled']) ? 1 : 0;
                $bankEnabled = isset($_POST['bank_enabled']) ? 1 : 0;
                $cardEnabled = isset($_POST['card_enabled']) ? 1 : 0;
                $gcashEnabled = isset($_POST['gcash_enabled']) ? 1 : 0;
                
                // Update payment settings
                $settings = [
                    'payment_cod_enabled' => $codEnabled,
                    'payment_bank_enabled' => $bankEnabled,
                    'payment_card_enabled' => $cardEnabled,
                    'payment_gcash_enabled' => $gcashEnabled
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value, setting_type, description) 
                        VALUES (?, ?, 'boolean', ?) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP
                    ");
                    $descriptions = [
                        'payment_cod_enabled' => 'Enable Cash on Delivery payment method',
                        'payment_bank_enabled' => 'Enable Bank Transfer payment method',
                        'payment_card_enabled' => 'Enable Credit/Debit Card payment method',
                        'payment_gcash_enabled' => 'Enable GCash payment method'
                    ];
                    $stmt->execute([$key, $value, $descriptions[$key]]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Payment settings updated successfully']);
                exit();
                
            case 'get_payment_settings':
                $stmt = $pdo->prepare("
                    SELECT setting_key, setting_value 
                    FROM settings 
                    WHERE setting_key IN ('payment_cod_enabled', 'payment_bank_enabled', 'payment_card_enabled', 'payment_gcash_enabled')
                ");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $settings = [];
                foreach ($results as $row) {
                    $settings[$row['setting_key']] = (bool)$row['setting_value'];
                }
                
                echo json_encode(['success' => true, 'settings' => $settings]);
                exit();
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit();
        }
        
    } catch (PDOException $e) {
        error_log("Settings error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        exit();
    }
}

// Get current settings for display
try {
    $stmt = $pdo->prepare("
        SELECT setting_key, setting_value, setting_type 
        FROM settings 
        WHERE setting_key IN ('payment_cod_enabled', 'payment_bank_enabled', 'payment_card_enabled', 'payment_gcash_enabled', 'currency_rates')
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $currentSettings = [];
    foreach ($results as $row) {
        if ($row['setting_type'] === 'json') {
            $currentSettings[$row['setting_key']] = json_decode($row['setting_value'], true);
        } else {
            $currentSettings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    // Set defaults if not found
    $paymentDefaults = [
        'payment_cod_enabled' => '1',
        'payment_bank_enabled' => '1',
        'payment_card_enabled' => '1',
        'payment_gcash_enabled' => '1'
    ];
    
    foreach ($paymentDefaults as $key => $default) {
        if (!isset($currentSettings[$key])) {
            $currentSettings[$key] = $default;
        }
    }
    
    // Default currency rates
    if (!isset($currentSettings['currency_rates'])) {
        $currentSettings['currency_rates'] = [
            'USD' => 0.018,
            'GBP' => 0.014,
            'CAD' => 0.024
        ];
    }
    
} catch (PDOException $e) {
    error_log("Settings fetch error: " . $e->getMessage());
    $currentSettings = [
        'payment_cod_enabled' => '1',
        'payment_bank_enabled' => '1',
        'payment_card_enabled' => '1',
        'payment_gcash_enabled' => '1',
        'currency_rates' => ['USD' => 0.018, 'GBP' => 0.014, 'CAD' => 0.024]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
            --admin-dark: #2c3e50;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        .admin-body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .admin-content {
            flex: 1;
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }

        .admin-header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--admin-dark);
            cursor: pointer;
        }

        .header-title h4 {
            margin: 0;
            color: var(--admin-dark);
            font-weight: 600;
        }

        .header-title small {
            color: #6c757d;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .realtime-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #28a745;
            font-size: 0.875rem;
        }

        .realtime-dot {
            width: 8px;
            height: 8px;
            background-color: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .admin-content-wrapper {
            padding: 2rem;
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .settings-card-header {
            background: var(--admin-dark);
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: none;
        }

        .settings-card-header h5 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .settings-card-body {
            padding: 2rem;
        }

        .payment-method-item {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .payment-method-item:hover {
            border-color: var(--admin-accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .payment-method-item.enabled {
            border-color: var(--admin-success);
            background: #f8fff9;
        }

        .payment-method-item.disabled {
            border-color: var(--admin-danger);
            background: #fff8f8;
            opacity: 0.7;
        }

        .payment-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .payment-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .payment-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .payment-details h6 {
            margin: 0;
            font-weight: 600;
            color: var(--admin-dark);
        }

        .payment-details p {
            margin: 0;
            color: #6c757d;
            font-size: 0.875rem;
        }

        .form-switch .form-check-input {
            width: 3rem;
            height: 1.5rem;
            border-radius: 1rem;
        }

        .form-switch .form-check-input:checked {
            background-color: var(--admin-success);
            border-color: var(--admin-success);
        }

        .currency-display {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .currency-rate {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .currency-rate:last-child {
            border-bottom: none;
        }

        .currency-flag {
            width: 24px;
            height: 16px;
            border-radius: 2px;
            margin-right: 0.5rem;
        }

        .btn-admin {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-admin-primary {
            background-color: var(--admin-primary);
            color: white;
        }

        .btn-admin-primary:hover {
            background-color: var(--admin-secondary);
            color: white;
            transform: translateY(-1px);
        }

        .btn-admin-success {
            background-color: var(--admin-success);
            color: white;
        }

        .btn-admin-success:hover {
            background-color: #229954;
            color: white;
            transform: translateY(-1px);
        }

        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            .admin-content {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .admin-content-wrapper {
                padding: 1rem;
            }
            
            .settings-card-body {
                padding: 1.5rem;
            }
            
            .payment-method-item {
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .payment-toggle {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .payment-info {
                width: 100%;
            }
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Header -->
            <div class="admin-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h4 class="mb-0">Website Settings</h4>
                        <small class="text-muted">Configure payment methods and currency display</small>
                    </div>
                </div>
                <div class="header-right">
                    <div class="realtime-indicator">
                        <span class="realtime-dot"></span>
                        <small>Live Settings</small>
                    </div>
                    <button type="button" class="btn-admin btn-admin-success" onclick="saveAllSettings()">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="admin-content-wrapper">
                <div class="content-container">

                    <!-- Payment Methods Settings -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h5>
                                <i class="fas fa-credit-card"></i>
                                Payment Methods Configuration
                            </h5>
                        </div>
                        <div class="settings-card-body">
                            <p class="text-muted mb-4">
                                Enable or disable payment methods for your customers. Disabled methods will not appear in the checkout process.
                            </p>

                            <div class="row">
                                <!-- Cash on Delivery -->
                                <div class="col-lg-6 mb-3">
                                    <div class="payment-method-item" id="cod-item">
                                        <div class="payment-toggle">
                                            <div class="payment-info">
                                                <div class="payment-icon" style="background-color: #28a745;">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </div>
                                                <div class="payment-details">
                                                    <h6>Cash on Delivery</h6>
                                                    <p>Pay when you receive the order</p>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="cod_enabled"
                                                       <?php echo $currentSettings['payment_cod_enabled'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="cod_enabled"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bank Transfer -->
                                <div class="col-lg-6 mb-3">
                                    <div class="payment-method-item" id="bank-item">
                                        <div class="payment-toggle">
                                            <div class="payment-info">
                                                <div class="payment-icon" style="background-color: #007bff;">
                                                    <i class="fas fa-university"></i>
                                                </div>
                                                <div class="payment-details">
                                                    <h6>Bank Transfer</h6>
                                                    <p>Transfer to bank account</p>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="bank_enabled"
                                                       <?php echo $currentSettings['payment_bank_enabled'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="bank_enabled"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Credit/Debit Card -->
                                <div class="col-lg-6 mb-3">
                                    <div class="payment-method-item" id="card-item">
                                        <div class="payment-toggle">
                                            <div class="payment-info">
                                                <div class="payment-icon" style="background-color: #6f42c1;">
                                                    <i class="fas fa-credit-card"></i>
                                                </div>
                                                <div class="payment-details">
                                                    <h6>Credit/Debit Card</h6>
                                                    <p>Visa, Mastercard, etc.</p>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="card_enabled"
                                                       <?php echo $currentSettings['payment_card_enabled'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="card_enabled"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- GCash -->
                                <div class="col-lg-6 mb-3">
                                    <div class="payment-method-item" id="gcash-item">
                                        <div class="payment-toggle">
                                            <div class="payment-info">
                                                <div class="payment-icon" style="background-color: #007bff;">
                                                    <i class="fas fa-mobile-alt"></i>
                                                </div>
                                                <div class="payment-details">
                                                    <h6>GCash</h6>
                                                    <p>Mobile wallet payment</p>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="gcash_enabled"
                                                       <?php echo $currentSettings['payment_gcash_enabled'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="gcash_enabled"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Changes will be applied immediately to the checkout page. At least one payment method must remain enabled.
                            </div>
                        </div>
                    </div>

                    <!-- Currency Display Settings -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h5>
                                <i class="fas fa-coins"></i>
                                Currency Display
                            </h5>
                        </div>
                        <div class="settings-card-body">
                            <p class="text-muted mb-4">
                                Currency conversion rates for display purposes. These are static values and do not affect actual pricing.
                            </p>

                            <div class="row">
                                <div class="col-lg-8">
                                    <h6 class="mb-3">Current Exchange Rates (from PHP)</h6>

                                    <!-- USD -->
                                    <div class="currency-display">
                                        <div class="currency-rate">
                                            <div class="d-flex align-items-center">
                                                <div class="currency-flag" style="background: linear-gradient(to bottom, #b22234 0%, #b22234 50%, #ffffff 50%, #ffffff 100%); position: relative;">
                                                    <div style="position: absolute; top: 0; left: 0; width: 40%; height: 40%; background: #3c3b6e;"></div>
                                                </div>
                                                <strong>US Dollar (USD)</strong>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary">₱1.00 = $<?php echo number_format($currentSettings['currency_rates']['USD'], 3); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- GBP -->
                                    <div class="currency-display">
                                        <div class="currency-rate">
                                            <div class="d-flex align-items-center">
                                                <div class="currency-flag" style="background: linear-gradient(45deg, #012169 25%, transparent 25%), linear-gradient(-45deg, #012169 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #012169 75%), linear-gradient(-45deg, transparent 75%, #012169 75%); background-size: 8px 8px; background-color: #ffffff;"></div>
                                                <strong>British Pound (GBP)</strong>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-success">₱1.00 = £<?php echo number_format($currentSettings['currency_rates']['GBP'], 3); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CAD -->
                                    <div class="currency-display">
                                        <div class="currency-rate">
                                            <div class="d-flex align-items-center">
                                                <div class="currency-flag" style="background: linear-gradient(to right, #ff0000 33%, #ffffff 33%, #ffffff 66%, #ff0000 66%); position: relative;">
                                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #ff0000; font-size: 8px;">🍁</div>
                                                </div>
                                                <strong>Canadian Dollar (CAD)</strong>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-warning text-dark">₱1.00 = C$<?php echo number_format($currentSettings['currency_rates']['CAD'], 3); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Static Display Only:</strong> These currency rates are for display purposes only and do not affect actual product pricing or transactions.
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                        <div class="card-body text-center">
                                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                                            <h5>Live Rates</h5>
                                            <p class="mb-0">Currency rates are updated manually and are for reference only.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Admin JS -->
    <script src="assets/js/admin.js"></script>

    <script>
        // Initialize settings page
        document.addEventListener('DOMContentLoaded', function() {
            updatePaymentMethodStates();

            // Add event listeners to payment method toggles
            const toggles = document.querySelectorAll('.form-check-input');
            toggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    updatePaymentMethodStates();
                    validatePaymentMethods();
                });
            });
        });

        function updatePaymentMethodStates() {
            const methods = ['cod', 'bank', 'card', 'gcash'];

            methods.forEach(method => {
                const checkbox = document.getElementById(method + '_enabled');
                const item = document.getElementById(method + '-item');

                if (checkbox && item) {
                    if (checkbox.checked) {
                        item.classList.remove('disabled');
                        item.classList.add('enabled');
                    } else {
                        item.classList.remove('enabled');
                        item.classList.add('disabled');
                    }
                }
            });
        }

        function validatePaymentMethods() {
            const checkboxes = document.querySelectorAll('.form-check-input');
            const enabledCount = Array.from(checkboxes).filter(cb => cb.checked).length;

            if (enabledCount === 0) {
                showAlert('error', 'At least one payment method must be enabled');
                // Re-enable the last unchecked method
                event.target.checked = true;
                updatePaymentMethodStates();
                return false;
            }

            return true;
        }

        async function saveAllSettings() {
            if (!validatePaymentMethods()) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_payment_settings');

            // Get payment method states
            const methods = ['cod', 'bank', 'card', 'gcash'];
            methods.forEach(method => {
                const checkbox = document.getElementById(method + '_enabled');
                if (checkbox && checkbox.checked) {
                    formData.append(method + '_enabled', '1');
                }
            });

            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', 'Settings saved successfully! Changes are now live on the checkout page.');
                } else {
                    showAlert('error', result.message || 'Failed to save settings');
                }
            } catch (error) {
                console.error('Error saving settings:', error);
                showAlert('error', 'An error occurred while saving settings');
            }
        }

        function showAlert(type, message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert-floating');
            existingAlerts.forEach(alert => alert.remove());

            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show alert-floating`;
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 350px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;

            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alertDiv);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && overlay) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            }
        }
    </script>
</body>
</html>
