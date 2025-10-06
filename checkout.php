<?php
require_once 'includes/session.php';
require_once 'includes/settings_helper.php';
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = getUserId();

// Get cart items (check for selected items from sessionStorage via JavaScript)
$selected_cart_ids = [];
if (isset($_GET['selected']) && !empty($_GET['selected'])) {
    $selected_cart_ids = explode(',', $_GET['selected']);
    $selected_cart_ids = array_map('intval', $selected_cart_ids);
}

try {
    if (!empty($selected_cart_ids)) {
        // Get only selected items
        $placeholders = str_repeat('?,', count($selected_cart_ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT c.cart_id, c.product_id, c.quantity, c.size,
                   p.title, p.price, p.image, p.brand, p.color, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ? AND c.cart_id IN ($placeholders)
            ORDER BY c.date_added DESC
        ");
        $params = array_merge([$user_id], $selected_cart_ids);
        $stmt->execute($params);
    } else {
        // Get all cart items
        $stmt = $pdo->prepare("
            SELECT c.cart_id, c.product_id, c.quantity, c.size,
                   p.title, p.price, p.image, p.brand, p.color, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.date_added DESC
        ");
        $stmt->execute([$user_id]);
    }

    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        header("Location: cart.php");
        exit();
    }

} catch (PDOException $e) {
    error_log("Error fetching cart items: " . $e->getMessage());
    $cart_items = [];
}

// Get user addresses
try {
    $stmt = $pdo->prepare("
        SELECT * FROM user_addresses 
        WHERE user_id = ? 
        ORDER BY is_default DESC, created_at DESC
    ");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching addresses: " . $e->getMessage());
    $addresses = [];
}

// Calculate totals
$subtotal = 0;
$items_without_size = [];
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    if (empty($item['size'])) {
        $items_without_size[] = $item['title'];
    }
}

// Initialize voucher variables
$voucher_code = null;
$discount_amount = 0;
$discount_percentage = 0;

// Check for applied voucher from session storage (will be handled by JavaScript)
// For now, calculate without voucher - JavaScript will update if voucher is applied

$shipping_fee = 150; // Fixed shipping fee
$tax_rate = 0.12; // 12% VAT
$tax = $subtotal * $tax_rate;
$total = $subtotal + $shipping_fee + $tax;

$currentUser = getCurrentUser();

// Get payment method settings
$paymentSettings = getPaymentSettings();

// Check if at least one payment method is enabled
if (!hasEnabledPaymentMethods()) {
    // If no payment methods are enabled, show error and redirect
    echo "<script>alert('Payment methods are currently unavailable. Please try again later.'); window.location.href = 'cart.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ShoeARizz</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #000000;
            line-height: 1.6;
        }

        .checkout-container {
            background-color: #ffffff;
            min-height: 100vh;
            padding: 2rem 0;
        }

        .checkout-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
        }

        .checkout-header h1 {
            color: #000000;
            font-weight: 600;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .checkout-header p {
            color: #666666;
            font-weight: 400;
            font-size: 1.1rem;
        }

        .section-container {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .section-title {
            color: #000000;
            font-weight: 600;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .product-item {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .product-details {
            flex: 1;
        }

        .product-title {
            color: #000000;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .product-meta {
            color: #666666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .product-size {
            color: #000000;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .size-badge {
            background-color: #000000;
            color: #ffffff;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .size-missing {
            background-color: #dc3545;
            color: #ffffff;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .product-quantity {
            color: #000000;
            font-weight: 500;
            text-align: center;
            min-width: 60px;
        }

        .product-price {
            color: #000000;
            font-weight: 600;
            font-size: 1.1rem;
            text-align: right;
            min-width: 100px;
        }

        .order-summary {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 2rem;
            position: sticky;
            top: 2rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            color: #000000;
        }

        .summary-row:not(:last-child) {
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-total {
            font-weight: 600;
            font-size: 1.2rem;
            padding-top: 1rem;
            border-top: 2px solid #000000;
        }

        .btn-checkout {
            background-color: #000000;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-checkout:hover {
            background-color: #333333;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-checkout:disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
            transform: none;
        }

        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000000;
            font-weight: 500;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: #000000;
        }

        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #ffffff;
            height: 100%;
        }

        .payment-option:hover {
            border-color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .payment-option.selected {
            border-color: #000000;
            background-color: #f8f9fa;
        }

        .payment-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f5f5f5;
            border-color: #cccccc;
        }

        .payment-option.disabled:hover {
            border-color: #cccccc;
            transform: none;
            box-shadow: none;
        }

        .payment-option.disabled .payment-content {
            color: #999999;
        }

        .payment-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .payment-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: #000000;
            margin-bottom: 0.25rem;
        }

        .payment-desc {
            font-size: 0.9rem;
            color: #666666;
        }

        .form-check-input {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }

        /* Address Selection Styles */
        .address-selection {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .address-option {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #ffffff;
            position: relative;
        }

        .address-option:hover {
            border-color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .address-option.selected {
            border-color: #000000;
            background-color: #f8f9fa;
        }

        .address-radio {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 20px;
            height: 20px;
            accent-color: #000000;
        }

        .address-content {
            margin-right: 2rem;
        }

        .address-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .address-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #000000;
        }

        .default-badge {
            background-color: #000000;
            color: #ffffff;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .address-details {
            color: #666666;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .address-line {
            margin-bottom: 0.25rem;
        }

        .address-phone {
            margin-top: 0.5rem;
            color: #000000;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .address-option {
                padding: 1rem;
            }

            .address-content {
                margin-right: 1.5rem;
            }

            .address-name {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="checkout-container">
        <div class="container">
            <div class="checkout-header">
                <h1>Checkout</h1>
                <p>Review your order and complete your purchase</p>
            </div>

            <!-- Check if user has no shipping address and redirect -->
            <?php if (empty($addresses)): ?>
                <script>
                    alert('Please add a shipping address before proceeding to checkout.');
                    window.location.href = 'profile.php';
                </script>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Size Validation Warning -->
                    <?php if (!empty($items_without_size)): ?>
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Size Selection Required</h5>
                        <p class="mb-2">Please select sizes for the following items before proceeding:</p>
                        <ul class="mb-2">
                            <?php foreach ($items_without_size as $item_title): ?>
                            <li><?php echo htmlspecialchars($item_title); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="cart.php" class="btn btn-warning">
                            <i class="fas fa-arrow-left me-2"></i>Back to Cart
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Order Items -->
                    <div class="section-container">
                        <h2 class="section-title">Order Items</h2>
                        <?php foreach ($cart_items as $item): ?>
                        <div class="product-item">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                 class="product-image"
                                 onerror="this.src='assets/img/placeholder.jpg'">

                            <div class="product-details">
                                <div class="product-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="product-meta">
                                    <?php echo htmlspecialchars($item['brand']); ?> •
                                    <?php echo htmlspecialchars($item['color']); ?>
                                </div>
                                <div class="product-size">
                                    Size:
                                    <?php if ($item['size']): ?>
                                        <span class="size-badge"><?php echo htmlspecialchars($item['size']); ?></span>
                                    <?php else: ?>
                                        <span class="size-missing">Not Selected</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="product-quantity">
                                Qty: <?php echo $item['quantity']; ?>
                            </div>

                            <div class="product-price">
                                ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Shipping Address -->
                    <div class="section-container">
                        <h2 class="section-title">
                            Shipping Address
                            <span class="text-muted" style="font-size: 0.9rem; font-weight: 400;">
                                (<?php echo count($addresses); ?> address<?php echo count($addresses) !== 1 ? 'es' : ''; ?> available)
                            </span>
                        </h2>
                        <?php if (!empty($addresses)): ?>
                            <div class="address-selection">
                                <?php
                                // Determine which address should be selected by default
                                $default_address_id = null;
                                foreach ($addresses as $address) {
                                    if ($address['is_default']) {
                                        $default_address_id = $address['id'];
                                        break;
                                    }
                                }
                                // If no default, use the first address
                                if (!$default_address_id) {
                                    $default_address_id = $addresses[0]['id'];
                                }
                                ?>
                                <?php foreach ($addresses as $index => $address): ?>
                                    <div class="address-option" onclick="selectAddress(<?php echo $address['id']; ?>)">
                                        <input type="radio" name="shipping_address" value="<?php echo $address['id']; ?>"
                                               <?php echo ($address['id'] == $default_address_id) ? 'checked' : ''; ?>
                                               class="form-check-input address-radio">
                                        <div class="address-content">
                                            <div class="address-header">
                                                <span class="address-name"><?php echo htmlspecialchars($address['full_name']); ?></span>
                                                <?php if ($address['is_default']): ?>
                                                    <span class="default-badge">Default</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="address-details">
                                                <div class="address-line"><?php echo htmlspecialchars($address['address_line1']); ?></div>
                                                <?php if ($address['address_line2']): ?>
                                                    <div class="address-line"><?php echo htmlspecialchars($address['address_line2']); ?></div>
                                                <?php endif; ?>
                                                <div class="address-line">
                                                    <?php echo htmlspecialchars($address['city']); ?>,
                                                    <?php echo htmlspecialchars($address['state']); ?>
                                                    <?php echo htmlspecialchars($address['postal_code']); ?>
                                                </div>
                                                <div class="address-line"><?php echo htmlspecialchars($address['country']); ?></div>
                                                <?php if ($address['phone']): ?>
                                                    <div class="address-phone">
                                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($address['phone']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <a href="profile.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-plus me-1"></i>Add New Address
                                </a>
                                <a href="profile.php#addresses" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>Manage Addresses
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div>
                                    <strong>No shipping address found!</strong><br>
                                    Please add a shipping address to continue with your order.
                                </div>
                            </div>
                            <div class="text-center">
                                <a href="profile.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add Shipping Address
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Payment Method -->
                    <div class="section-container">
                        <h2 class="section-title">Payment Method</h2>
                        <div class="row">
                            <?php
                            $paymentMethods = [
                                'cod' => [
                                    'enabled' => $paymentSettings['payment_cod_enabled'],
                                    'title' => 'Cash on Delivery',
                                    'desc' => 'Pay when you receive',
                                    'icon' => 'fas fa-money-bill-wave',
                                    'color' => '#28a745',
                                    'value' => 'cod'
                                ],
                                'bank_transfer' => [
                                    'enabled' => $paymentSettings['payment_bank_enabled'],
                                    'title' => 'Bank Transfer',
                                    'desc' => 'Transfer to bank account',
                                    'icon' => 'fas fa-university',
                                    'color' => '#007bff',
                                    'value' => 'bank_transfer'
                                ],
                                'card' => [
                                    'enabled' => $paymentSettings['payment_card_enabled'],
                                    'title' => 'Credit/Debit Card',
                                    'desc' => 'Visa, Mastercard, etc.',
                                    'icon' => 'fas fa-credit-card',
                                    'color' => '#6f42c1',
                                    'value' => 'card'
                                ],
                                'gcash' => [
                                    'enabled' => $paymentSettings['payment_gcash_enabled'],
                                    'title' => 'GCash',
                                    'desc' => 'Mobile wallet payment',
                                    'icon' => 'fas fa-mobile-alt',
                                    'color' => '#007bff',
                                    'value' => 'gcash'
                                ]
                            ];

                            $firstEnabled = true;
                            foreach ($paymentMethods as $key => $method):
                                if ($method['enabled']):
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="payment-option" onclick="selectPayment('<?php echo $method['value']; ?>')">
                                    <input type="radio" name="payment_method" value="<?php echo $method['value']; ?>"
                                           <?php echo $firstEnabled ? 'checked' : ''; ?> class="form-check-input">
                                    <div class="payment-content">
                                        <i class="<?php echo $method['icon']; ?>" style="font-size: 2rem; color: <?php echo $method['color']; ?>; margin-bottom: 0.5rem;"></i>
                                        <div class="payment-title"><?php echo $method['title']; ?></div>
                                        <div class="payment-desc"><?php echo $method['desc']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php
                                $firstEnabled = false;
                                endif;
                            endforeach;
                            ?>
                        </div>

                        <?php if (count(array_filter($paymentMethods, function($m) { return $m['enabled']; })) === 0): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>No Payment Methods Available</strong><br>
                            Please contact support for assistance with your order.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="order-summary">
                        <h2 class="section-title">Order Summary</h2>

                        <div class="summary-row">
                            <span>Subtotal (<?php echo count($cart_items); ?> items)</span>
                            <span id="checkout-subtotal">₱<?php echo number_format($subtotal, 2); ?></span>
                        </div>

                        <!-- Voucher discount row (hidden by default) -->
                        <div class="summary-row" id="checkout-discount-row" style="display: none;">
                            <span>Discount (<span id="checkout-voucher-code"></span>)</span>
                            <span id="checkout-discount" class="text-success">-₱0.00</span>
                        </div>

                        <div class="summary-row">
                            <span>Shipping Fee</span>
                            <span id="checkout-shipping">₱<?php echo number_format($shipping_fee, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Tax (12% VAT)</span>
                            <span id="checkout-tax">₱<?php echo number_format($tax, 2); ?></span>
                        </div>

                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span id="checkout-total">₱<?php echo number_format($total, 2); ?></span>
                        </div>

                        <button type="button" class="btn-checkout mt-3"
                                onclick="processCheckout()"
                                <?php echo (!empty($items_without_size) || empty($addresses)) ? 'disabled' : ''; ?>>
                            Place Order
                        </button>

                        <div style="text-align: center; margin-top: 1rem; color: #666666; font-size: 0.9rem;">
                            <i class="fas fa-shield-alt me-1"></i>
                            Secure checkout
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Global Cart and Favorites JS -->
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>

    <script>
        function selectPayment(method) {
            // Check if payment method is disabled
            const paymentOption = event.currentTarget;
            if (paymentOption.classList.contains('disabled')) {
                return; // Don't allow selection of disabled payment methods
            }

            // Update radio button
            const radioButton = document.querySelector(`input[value="${method}"]`);
            if (radioButton && !radioButton.disabled) {
                radioButton.checked = true;

                // Update visual selection
                document.querySelectorAll('.payment-option').forEach(el => {
                    el.classList.remove('selected');
                });
                paymentOption.classList.add('selected');
            }
        }

        function selectAddress(addressId) {
            // Update radio button
            const radioButton = document.querySelector(`input[name="shipping_address"][value="${addressId}"]`);
            if (radioButton) {
                radioButton.checked = true;

                // Update visual selection
                document.querySelectorAll('.address-option').forEach(el => {
                    el.classList.remove('selected');
                });
                event.currentTarget.classList.add('selected');
            }
        }

        function processCheckout() {
            const addressId = document.querySelector('input[name="shipping_address"]:checked')?.value;
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;

            if (!addressId) {
                alert('Please select a shipping address');
                return;
            }

            if (!paymentMethod) {
                alert('Please select a payment method');
                return;
            }

            // Store order data in session for payment processing
            const formData = new FormData();
            // Use place_order for COD, prepare_order for others
            const action = (paymentMethod === 'cod') ? 'place_order' : 'prepare_order';
            formData.append('action', action);
            formData.append('shipping_address_id', addressId);
            formData.append('payment_method', paymentMethod);

            // Add voucher information if applied
            const appliedVoucher = sessionStorage.getItem('appliedVoucher');
            if (appliedVoucher) {
                const voucher = JSON.parse(appliedVoucher);
                formData.append('voucher_code', voucher.code);
                formData.append('discount_amount', voucher.discount_amount);
            }

            // Add selected cart IDs if any
            const urlParams = new URLSearchParams(window.location.search);
            const selectedItems = urlParams.get('selected');
            if (selectedItems) {
                formData.append('selected_cart_ids', selectedItems);
            }

            fetch('process_checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect based on payment method
                    switch(paymentMethod) {
                        case 'bank_transfer':
                            window.location.href = `bank_transfer.php?order_id=${data.order_id}`;
                            break;
                        case 'card':
                            window.location.href = `creditcard.php?order_id=${data.order_id}`;
                            break;
                        case 'gcash':
                            window.location.href = `gcash.php?order_id=${data.order_id}`;
                            break;
                        case 'cod':
                        default:
                            window.location.href = `order_success.php?order_id=${data.order_id}`;
                            break;
                    }
                } else {
                    alert(data.message || 'An error occurred while processing your order');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your order');
            });
        }

        // Initialize payment selection
        document.addEventListener('DOMContentLoaded', function() {
            // Handle selected cart items from sessionStorage
            const selectedItems = sessionStorage.getItem('selectedCartItems');
            if (selectedItems) {
                const cartIds = JSON.parse(selectedItems);
                // Update URL to include selected items
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('selected', cartIds.join(','));

                // Only reload if we don't already have the selected parameter
                if (!window.location.search.includes('selected=')) {
                    window.location.href = currentUrl.toString();
                    return;
                }

                // Clear the sessionStorage after using it
                sessionStorage.removeItem('selectedCartItems');
            }

            // Handle applied voucher from sessionStorage
            const appliedVoucher = sessionStorage.getItem('appliedVoucher');
            if (appliedVoucher) {
                const voucher = JSON.parse(appliedVoucher);
                applyVoucherToCheckout(voucher);
            }

            // Set default payment selection
            const defaultPayment = document.querySelector('input[name="payment_method"]:checked');
            if (defaultPayment) {
                defaultPayment.closest('.payment-option').classList.add('selected');
            }

            // Set default address selection
            const defaultAddress = document.querySelector('input[name="shipping_address"]:checked');
            if (defaultAddress) {
                defaultAddress.closest('.address-option').classList.add('selected');
            }

            // Update cart and favorites counts
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
            if (typeof updateFavoritesCount === 'function') {
                updateFavoritesCount();
            }
        });

        function applyVoucherToCheckout(voucher) {
            const originalSubtotal = <?php echo $subtotal; ?>;
            const discountAmount = voucher.discount_amount; // Use the actual discount amount from voucher
            const newSubtotal = originalSubtotal - discountAmount;
            const shippingFee = 150; // Fixed shipping fee for checkout
            const tax = newSubtotal * 0.12; // 12% VAT for checkout
            const newTotal = newSubtotal + shippingFee + tax;

            // Update display
            document.getElementById('checkout-voucher-code').textContent = voucher.code;
            document.getElementById('checkout-discount').textContent = '-₱' + discountAmount.toFixed(2);
            document.getElementById('checkout-discount-row').style.display = 'flex';
            document.getElementById('checkout-tax').textContent = '₱' + tax.toFixed(2);
            document.getElementById('checkout-total').textContent = '₱' + newTotal.toFixed(2);
        }
    </script>
</body>
</html>
