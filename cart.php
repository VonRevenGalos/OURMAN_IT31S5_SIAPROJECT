<?php
require_once 'includes/session.php';
require_once 'db.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = getUserId();

// Fetch user's cart items
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.title, p.price, p.stock, p.image, p.brand, p.color 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? 
        ORDER BY c.date_added DESC
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $subtotal = 0;
    $total_items = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
        $total_items += $item['quantity'];
    }
    
    $shipping = 150; // Fixed shipping fee to match checkout
    $tax = $subtotal * 0.12; // 12% VAT to match checkout
    $total = $subtotal + $shipping + $tax;
    
} catch (PDOException $e) {
    error_log("Cart fetch error: " . $e->getMessage());
    $cart_items = [];
    $subtotal = $total_items = $shipping = $tax = $total = 0;
}

// Get current user
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ShoeARizz</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/cart.css">

    <style>
        /* Voucher Section Styles */
        .voucher-section {
            margin: 1rem 0;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .voucher-input-group {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .voucher-input-group input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        .voucher-input-group button {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .voucher-message {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            padding: 0.25rem 0;
        }

        .voucher-message.success {
            color: #28a745;
        }

        .voucher-message.error {
            color: #dc3545;
        }

        .applied-voucher {
            margin-top: 0.5rem;
        }

        .voucher-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 0.5rem;
            font-size: 0.9rem;
            color: #155724;
        }

        .voucher-code-display {
            font-weight: 600;
        }

        .btn-remove-voucher {
            background: none;
            border: none;
            color: #155724;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-remove-voucher:hover {
            color: #0c4128;
        }

        #discount-row {
            color: #28a745;
            font-weight: 500;
        }

        /* Animation for size selection warning */
        @keyframes pulse-warning {
            0% { background-color: transparent; }
            50% { background-color: #ffc107; }
            100% { background-color: transparent; }
        }

        /* Selected cart item styling */
        .cart-item.selected {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid cart-container">
        <div class="row">
            <div class="col-12">
                <!-- Page Header -->
                <div class="cart-header">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h1 class="page-title">
                                    <i class="fas fa-shopping-cart me-3"></i>Shopping Cart
                                </h1>
                                <p class="page-subtitle">
                                    <span id="cart-count"><?php echo $total_items; ?></span> item<?php echo $total_items !== 1 ? 's' : ''; ?> in your cart
                                </p>
                            </div>
                            <div class="col-md-6 text-end">
                                <?php if (!empty($cart_items)): ?>
                                <div class="header-actions">
                                    <button class="btn btn-outline-dark me-2" id="select-all-btn" onclick="toggleSelectAll()">
                                        <i class="fas fa-check-square me-1"></i>Select All
                                    </button>
                                    <button class="btn btn-dark me-2" id="remove-selected-btn" disabled>
                                        <i class="fas fa-trash me-1"></i>Remove Selected
                                    </button>
                                    <button class="btn btn-dark" onclick="clearCart()">
                                        <i class="fas fa-trash-alt me-1"></i>Clear Cart
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart Content -->
                <div class="cart-content">
                    <div class="container">
                        <?php if (empty($cart_items)): ?>
                            <!-- Empty State -->
                            <div class="empty-cart">
                                <div class="empty-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h3>Your cart is empty</h3>
                                <p>Start adding products to your cart to see them here.</p>
                                <a href="men.php" class="btn btn-dark btn-lg">
                                    <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <!-- Cart Items -->
                                <div class="col-lg-8">
                                    <div class="cart-items" id="cart-items">
                                        <?php foreach ($cart_items as $item): ?>
                                            <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>">
                                                <div class="item-checkbox">
                                                    <input type="checkbox" class="form-check-input cart-select" value="<?php echo $item['cart_id']; ?>" onchange="updateSelectedCount()">
                                                </div>
                                                <div class="item-image">
                                                    <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                                        <img src="<?php echo htmlspecialchars(trim($item['image'])); ?>"
                                                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                                                             class="img-fluid"
                                                             loading="lazy"
                                                             onerror="this.style.display='none'; this.parentElement.style.background='#f8f9fa'; this.onerror=null;">
                                                    </a>
                                                </div>
                                                <div class="item-details">
                                                    <div class="item-brand"><?php echo htmlspecialchars($item['brand'] ?? 'Generic'); ?></div>
                                                    <h4 class="item-title">
                                                        <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                                            <?php echo htmlspecialchars($item['title']); ?>
                                                        </a>
                                                    </h4>
                                                    <div class="item-info">
                                                        <span class="item-color">Color: <?php echo htmlspecialchars($item['color'] ?? 'N/A'); ?></span>
                                                        <span class="item-size">
                                                            Size:
                                                            <button class="size-btn <?php echo empty($item['size']) ? 'size-required' : ''; ?>"
                                                                    onclick="editSize(<?php echo $item['cart_id']; ?>, '<?php echo htmlspecialchars($item['size'] ?? ''); ?>')">
                                                                <?php
                                                                if (empty($item['size'])) {
                                                                    echo '<span class="text-danger">Select Size</span>';
                                                                } else {
                                                                    echo htmlspecialchars($item['size']);
                                                                }
                                                                ?>
                                                                <i class="fas fa-edit ms-1"></i>
                                                            </button>
                                                        </span>
                                                    </div>
                                                    <div class="item-stock">
                                                        <?php if ($item['stock'] > 0): ?>
                                                            <span class="text-success">
                                                                <i class="fas fa-check-circle me-1"></i>In Stock (<?php echo $item['stock']; ?> available)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-danger">
                                                                <i class="fas fa-times-circle me-1"></i>Out of Stock
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="item-quantity">
                                                    <label>Quantity:</label>
                                                    <div class="quantity-controls">
                                                        <button class="qty-btn qty-minus" data-cart-id="<?php echo $item['cart_id']; ?>"
                                                                <?php echo ($item['quantity'] <= 1) ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>"
                                                               min="1" max="<?php echo $item['stock']; ?>"
                                                               data-cart-id="<?php echo $item['cart_id']; ?>">
                                                        <button class="qty-btn qty-plus" data-cart-id="<?php echo $item['cart_id']; ?>"
                                                                <?php echo ($item['quantity'] >= $item['stock']) ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="item-price">
                                                    <div class="price-per-item">₱<?php echo number_format($item['price'], 2); ?> each</div>
                                                    <div class="price-total" data-cart-id="<?php echo $item['cart_id']; ?>">
                                                        ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                    </div>
                                                </div>
                                                <div class="item-actions">
                                                    <button class="btn-remove" onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Cart Summary -->
                                <div class="col-lg-4">
                                    <div class="cart-summary">
                                        <h3>Order Summary</h3>
                                        <div class="summary-row">
                                            <span>Subtotal (<span id="summary-items"><?php echo $total_items; ?></span> items):</span>
                                            <span id="summary-subtotal">₱<?php echo number_format($subtotal, 2); ?></span>
                                        </div>

                                        <!-- Voucher Section -->
                                        <div class="voucher-section">
                                            <div class="voucher-input-group">
                                                <input type="text" id="voucher-code" class="form-control" placeholder="Enter voucher code" maxlength="50">
                                                <button type="button" id="apply-voucher-btn" class="btn btn-outline-dark">Apply</button>
                                            </div>
                                            <div id="voucher-message" class="voucher-message"></div>
                                            <div id="applied-voucher" class="applied-voucher" style="display: none;">
                                                <div class="voucher-info">
                                                    <span class="voucher-code-display"></span>
                                                    <button type="button" id="remove-voucher-btn" class="btn-remove-voucher">×</button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Discount Row (hidden by default) -->
                                        <div class="summary-row" id="discount-row" style="display: none;">
                                            <span>Discount:</span>
                                            <span id="summary-discount" class="text-success">-₱0.00</span>
                                        </div>

                                        <div class="summary-row">
                                            <span>Shipping:</span>
                                            <span id="summary-shipping">₱<?php echo number_format($shipping, 2); ?></span>
                                        </div>
                                        <div class="summary-row">
                                            <span>Tax:</span>
                                            <span id="summary-tax">₱<?php echo number_format($tax, 2); ?></span>
                                        </div>
                                        <hr>
                                        <div class="summary-total">
                                            <span>Total:</span>
                                            <span id="summary-total">₱<?php echo number_format($total, 2); ?></span>
                                        </div>
                                        
                                        <div class="checkout-actions">
                                            <button class="btn btn-dark btn-lg w-100 mb-3" onclick="proceedToCheckout()">
                                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                            </button>
                                            <a href="men.php" class="btn btn-outline-dark w-100">
                                                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                            </a>
                                        </div>
                                        
                                        <div class="shipping-info">
                                            <p><i class="fas fa-truck me-2"></i>₱150 shipping fee</p>
                                            <p><i class="fas fa-shield-alt me-2"></i>Secure checkout guaranteed</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Size Edit Modal -->
    <div class="modal fade" id="sizeModal" tabindex="-1" aria-labelledby="sizeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sizeModalLabel">Edit Size</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="size-options">
                        <label class="form-label">Select Size:</label>
                        <div class="size-grid">
                            <button class="size-option" data-size="6">6</button>
                            <button class="size-option" data-size="6.5">6.5</button>
                            <button class="size-option" data-size="7">7</button>
                            <button class="size-option" data-size="7.5">7.5</button>
                            <button class="size-option" data-size="8">8</button>
                            <button class="size-option" data-size="8.5">8.5</button>
                            <button class="size-option" data-size="9">9</button>
                            <button class="size-option" data-size="9.5">9.5</button>
                            <button class="size-option" data-size="10">10</button>
                            <button class="size-option" data-size="10.5">10.5</button>
                            <button class="size-option" data-size="11">11</button>
                            <button class="size-option" data-size="12">12</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark" onclick="saveSize()">Save Size</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>
    <script src="assets/js/cart.js"></script>

    <script>
        // Voucher functionality
        let appliedVoucher = null;
        let originalSubtotal = <?php echo $subtotal; ?>;
        let currentSubtotal = originalSubtotal; // Track current subtotal based on selection

        document.addEventListener('DOMContentLoaded', function() {
            const applyVoucherBtn = document.getElementById('apply-voucher-btn');
            const removeVoucherBtn = document.getElementById('remove-voucher-btn');
            const voucherCodeInput = document.getElementById('voucher-code');

            if (applyVoucherBtn) {
                applyVoucherBtn.addEventListener('click', applyVoucher);
            }

            if (removeVoucherBtn) {
                removeVoucherBtn.addEventListener('click', removeVoucher);
            }

            if (voucherCodeInput) {
                voucherCodeInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        applyVoucher();
                    }
                });
            }

            // Ensure remove selected button works properly
            const removeSelectedBtn = document.getElementById('remove-selected-btn');
            if (removeSelectedBtn) {
                // Remove any existing onclick handlers and add proper event listener
                removeSelectedBtn.removeAttribute('onclick');
                removeSelectedBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!this.disabled && typeof removeSelected === 'function') {
                        removeSelected();
                    }
                });
            }

            // Update totals when selection changes
            updateSelectedTotals();
        });

        function updateSelectedTotals() {
            const selectedCheckboxes = document.querySelectorAll('.cart-select:checked');
            let selectedSubtotal = 0;
            let selectedItemCount = 0;

            if (selectedCheckboxes.length > 0) {
                // Calculate totals for selected items only
                selectedCheckboxes.forEach(checkbox => {
                    const cartItem = checkbox.closest('.cart-item');
                    const priceTotal = cartItem.querySelector('.price-total');
                    const qtyInput = cartItem.querySelector('.qty-input');

                    if (priceTotal && qtyInput) {
                        // Extract price from the price-total element (remove ₱ and commas)
                        const priceText = priceTotal.textContent.replace(/[₱,]/g, '');
                        const itemTotal = parseFloat(priceText) || 0;
                        const quantity = parseInt(qtyInput.value) || 0;

                        selectedSubtotal += itemTotal;
                        selectedItemCount += quantity;
                    }
                });
                currentSubtotal = selectedSubtotal;
            } else {
                // Use all items if none selected
                currentSubtotal = originalSubtotal;
                selectedItemCount = <?php echo $total_items; ?>;
            }

            // Update the summary display
            document.getElementById('summary-items').textContent = selectedItemCount;

            // If voucher is applied, recalculate with new subtotal
            if (appliedVoucher) {
                applyVoucherToCurrentSelection();
            } else {
                updateSummaryDisplay(currentSubtotal);
            }
        }

        function updateSummaryDisplay(subtotal) {
            const shipping = 150;
            const tax = subtotal * 0.12;
            const total = subtotal + shipping + tax;

            document.getElementById('summary-subtotal').textContent = '₱' + subtotal.toFixed(2);
            document.getElementById('summary-shipping').textContent = '₱' + shipping.toFixed(2);
            document.getElementById('summary-tax').textContent = '₱' + tax.toFixed(2);
            document.getElementById('summary-total').textContent = '₱' + total.toFixed(2);
        }

        function applyVoucherToCurrentSelection() {
            if (!appliedVoucher) return;

            const discountAmount = (currentSubtotal * appliedVoucher.discount_percentage) / 100;
            const discountedSubtotal = currentSubtotal - discountAmount;
            const shipping = 150;
            const tax = discountedSubtotal * 0.12;
            const total = discountedSubtotal + shipping + tax;

            document.getElementById('summary-subtotal').textContent = '₱' + currentSubtotal.toFixed(2);
            document.getElementById('summary-discount').textContent = '-₱' + discountAmount.toFixed(2);
            document.getElementById('summary-shipping').textContent = '₱' + shipping.toFixed(2);
            document.getElementById('summary-tax').textContent = '₱' + tax.toFixed(2);
            document.getElementById('summary-total').textContent = '₱' + total.toFixed(2);

            // Show discount row
            document.getElementById('discount-row').style.display = 'flex';

            // Update voucher object with current discount amount
            appliedVoucher.discount_amount = discountAmount;
        }

        function applyVoucher() {
            const voucherCode = document.getElementById('voucher-code').value.trim();
            const messageDiv = document.getElementById('voucher-message');
            const applyBtn = document.getElementById('apply-voucher-btn');

            if (!voucherCode) {
                showVoucherMessage('Please enter a voucher code', 'error');
                return;
            }

            // Disable button and show loading
            applyBtn.disabled = true;
            applyBtn.textContent = 'Applying...';

            const formData = new FormData();
            formData.append('voucher_code', voucherCode);
            formData.append('subtotal', currentSubtotal);

            fetch('validate_voucher.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    appliedVoucher = data.voucher;
                    updateCartTotals(data.totals);
                    showAppliedVoucher(data.voucher);
                    showVoucherMessage(data.message, 'success');
                    document.getElementById('voucher-code').value = '';
                } else {
                    showVoucherMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showVoucherMessage('An error occurred while applying the voucher', 'error');
            })
            .finally(() => {
                applyBtn.disabled = false;
                applyBtn.textContent = 'Apply';
            });
        }

        function removeVoucher() {
            appliedVoucher = null;

            // Reset totals to current selection values
            updateSummaryDisplay(currentSubtotal);

            // Hide discount row and applied voucher
            document.getElementById('discount-row').style.display = 'none';
            document.getElementById('applied-voucher').style.display = 'none';
            document.getElementById('voucher-input-section').style.display = 'block';

            showVoucherMessage('Voucher removed', 'success');
        }

        function updateCartTotals(totals) {
            document.getElementById('summary-subtotal').textContent = '₱' + totals.original_subtotal.toFixed(2);
            document.getElementById('summary-discount').textContent = '-₱' + totals.discount_amount.toFixed(2);
            document.getElementById('summary-shipping').textContent = totals.shipping_fee > 0 ? '₱' + totals.shipping_fee.toFixed(2) : 'FREE';
            document.getElementById('summary-tax').textContent = '₱' + totals.tax.toFixed(2);
            document.getElementById('summary-total').textContent = '₱' + totals.total.toFixed(2);

            // Show discount row
            document.getElementById('discount-row').style.display = 'flex';
        }

        function showAppliedVoucher(voucher) {
            const appliedVoucherDiv = document.getElementById('applied-voucher');
            const codeDisplay = appliedVoucherDiv.querySelector('.voucher-code-display');

            codeDisplay.textContent = voucher.code + ' (' + voucher.discount_percentage + '% off)';
            appliedVoucherDiv.style.display = 'block';
        }

        function showVoucherMessage(message, type) {
            const messageDiv = document.getElementById('voucher-message');
            messageDiv.textContent = message;
            messageDiv.className = 'voucher-message ' + type;

            // Clear message after 5 seconds
            setTimeout(() => {
                messageDiv.textContent = '';
                messageDiv.className = 'voucher-message';
            }, 5000);
        }

        // Update proceedToCheckout function to include voucher data and size validation
        function proceedToCheckout() {
            // Check if cart has items
            const cartItems = document.querySelectorAll('.cart-item');
            if (cartItems.length === 0) {
                showNotification('Your cart is empty!', 'error');
                return;
            }

            // Get selected items (if any are selected, only checkout those)
            const selectedCheckboxes = document.querySelectorAll('.cart-select:checked');
            const itemsToCheck = selectedCheckboxes.length > 0 ?
                Array.from(selectedCheckboxes).map(cb => cb.closest('.cart-item')) :
                Array.from(cartItems);

            // Check if selected/all items have sizes selected
            const itemsWithoutSize = [];
            itemsToCheck.forEach(item => {
                const sizeBtn = item.querySelector('.size-btn');
                if (sizeBtn) {
                    // Check if button has the 'size-required' class or contains "Select Size" text
                    const hasRequiredClass = sizeBtn.classList.contains('size-required');
                    const containsSelectSize = sizeBtn.textContent.includes('Select Size');

                    if (hasRequiredClass || containsSelectSize) {
                        const titleElement = item.querySelector('.item-title a');
                        if (titleElement) {
                            itemsWithoutSize.push(titleElement.textContent.trim());
                        }
                    }
                }
            });

            if (itemsWithoutSize.length > 0) {
                const itemList = itemsWithoutSize.length > 3
                    ? itemsWithoutSize.slice(0, 3).join(', ') + ` and ${itemsWithoutSize.length - 3} more`
                    : itemsWithoutSize.join(', ');
                showNotification(`Please select sizes for: ${itemList}`, 'warning');

                // Highlight items without sizes
                itemsWithoutSize.forEach(itemTitle => {
                    const cartItems = document.querySelectorAll('.cart-item');
                    cartItems.forEach(item => {
                        const titleElement = item.querySelector('.item-title a');
                        if (titleElement && titleElement.textContent.trim() === itemTitle) {
                            const sizeBtn = item.querySelector('.size-btn');
                            if (sizeBtn) {
                                sizeBtn.style.animation = 'pulse-warning 1s ease-in-out 3';
                            }
                        }
                    });
                });

                return;
            }

            // If items are selected, store them for checkout
            if (selectedCheckboxes.length > 0) {
                const selectedCartIds = Array.from(selectedCheckboxes).map(cb => cb.value);
                // Store selected items in sessionStorage for checkout
                sessionStorage.setItem('selectedCartItems', JSON.stringify(selectedCartIds));
            } else {
                // Clear any previously selected items if checking out all
                sessionStorage.removeItem('selectedCartItems');
            }

            // Store voucher data in sessionStorage if applied
            if (appliedVoucher) {
                sessionStorage.setItem('appliedVoucher', JSON.stringify(appliedVoucher));
            } else {
                sessionStorage.removeItem('appliedVoucher');
            }

            // Redirect to checkout page
            window.location.href = 'checkout.php';
        }

        // Show notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'} notification-toast`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
            `;

            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;

            document.body.appendChild(notification);

            // Show notification
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);

            // Hide notification
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }

        // Enhanced updateSelectedCount function that works with cart.js
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.cart-select:checked');
            const removeBtn = document.getElementById('remove-selected-btn');
            const selectAllBtn = document.getElementById('select-all-btn');
            const allCheckboxes = document.querySelectorAll('.cart-select');

            // Update global selectedItems array for cart.js compatibility
            // Ensure selectedItems is available globally for cart.js
            if (typeof selectedItems !== 'undefined') {
                selectedItems = Array.from(checkboxes).map(cb => cb.value);
            } else {
                window.selectedItems = Array.from(checkboxes).map(cb => cb.value);
            }

            // Update cart item visual states
            allCheckboxes.forEach(checkbox => {
                const cartItem = checkbox.closest('.cart-item');
                if (cartItem) {
                    if (checkbox.checked) {
                        cartItem.classList.add('selected');
                    } else {
                        cartItem.classList.remove('selected');
                    }
                }
            });

            // Update remove button state and enable/disable properly
            if (checkboxes.length > 0) {
                removeBtn.disabled = false;
                removeBtn.innerHTML = `<i class="fas fa-trash me-1"></i>Remove Selected (${checkboxes.length})`;
                removeBtn.classList.add('btn-danger');
                removeBtn.classList.remove('btn-dark');
                // Ensure the button is clickable
                removeBtn.style.pointerEvents = 'auto';
                removeBtn.style.opacity = '1';
            } else {
                removeBtn.disabled = true;
                removeBtn.innerHTML = '<i class="fas fa-trash me-1"></i>Remove Selected';
                removeBtn.classList.remove('btn-danger');
                removeBtn.classList.add('btn-dark');
                removeBtn.style.opacity = '0.6';
            }

            // Update select all button
            const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
            if (allChecked && allCheckboxes.length > 0) {
                selectAllBtn.innerHTML = '<i class="fas fa-square me-1"></i>Deselect All';
            } else {
                selectAllBtn.innerHTML = '<i class="fas fa-check-square me-1"></i>Select All';
            }

            // Update totals based on selection
            updateSelectedTotals();
        }

        // Make sure we use the cart.js removeSelected function
        // No need to override it, just ensure selectedItems is properly set
    </script>
</body>
</html>
