<?php
require_once 'includes/session.php';
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = getUserId();
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    header("Location: cart.php");
    exit();
}

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, ua.full_name, ua.address_line1, ua.city, ua.state, ua.postal_code
        FROM orders o
        LEFT JOIN user_addresses ua ON o.shipping_address_id = ua.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header("Location: cart.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching order: " . $e->getMessage());
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Card Payment - ShoeARizz</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #000000;
        }
        
        .card-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .payment-header {
            background: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .payment-content {
            background: white;
            padding: 2rem;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .card-providers {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .provider-option {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 120px;
        }
        
        .provider-option:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .provider-option.selected {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        
        .provider-logo {
            width: 60px;
            height: 40px;
            object-fit: contain;
            margin-bottom: 0.5rem;
        }
        
        .provider-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #000000;
        }
        
        .credit-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 15px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .credit-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .card-number {
            font-size: 1.5rem;
            letter-spacing: 0.2rem;
            margin: 1rem 0;
            font-weight: 500;
        }
        
        .card-details {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #000000;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-pay {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-pay:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .summary-total {
            font-weight: 600;
            font-size: 1.2rem;
            border-top: 2px solid #667eea;
            padding-top: 0.5rem;
            margin-top: 1rem;
        }
        
        .security-info {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="payment-header">
            <h1><i class="fas fa-credit-card me-2"></i>Credit Card Payment</h1>
            <p class="mb-0">Secure payment processing</p>
        </div>
        
        <div class="payment-content">
            <!-- Order Summary -->
            <div class="order-summary">
                <h4 class="mb-3">Order Summary</h4>
                <div class="summary-row">
                    <span>Order ID:</span>
                    <span>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total Amount:</span>
                    <span>₱<?php echo number_format($order['total_price'], 2); ?></span>
                </div>
            </div>
            
            <!-- Card Providers -->
            <div class="card-providers">
                <div class="provider-option" onclick="selectProvider('visa')">
                    <input type="radio" name="card_provider" value="visa" class="form-check-input d-none">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA2MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjMUE1NkU0Ii8+Cjx0ZXh0IHg9IjMwIiB5PSIyNSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0IiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPlZJU0E8L3RleHQ+Cjwvc3ZnPg==" alt="Visa" class="provider-logo">
                    <div class="provider-name">Visa</div>
                </div>
                
                <div class="provider-option" onclick="selectProvider('mastercard')">
                    <input type="radio" name="card_provider" value="mastercard" class="form-check-input d-none">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA2MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjRUQ2QzAyIi8+Cjx0ZXh0IHg9IjMwIiB5PSIxOCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEwIiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk1hc3RlcjwvdGV4dD4KPHRleHQgeD0iMzAiIHk9IjMwIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTAiIGZvbnQtd2VpZ2h0PSJib2xkIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+Q2FyZDwvdGV4dD4KPC9zdmc+" alt="Mastercard" class="provider-logo">
                    <div class="provider-name">Mastercard</div>
                </div>
                
                <div class="provider-option" onclick="selectProvider('paypal')">
                    <input type="radio" name="card_provider" value="paypal" class="form-check-input d-none">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA2MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjMDAzMDg3Ii8+Cjx0ZXh0IHg9IjMwIiB5PSIyNSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEyIiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPlBheVBhbDwvdGV4dD4KPC9zdmc+" alt="PayPal" class="provider-logo">
                    <div class="provider-name">PayPal</div>
                </div>
            </div>
            
            <!-- Credit Card Preview -->
            <div class="credit-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 1.2rem; font-weight: 600;">ShoeARizz Bank</div>
                    <i class="fas fa-wifi" style="font-size: 1.5rem;"></i>
                </div>
                <div class="card-number" id="cardNumberDisplay">**** **** **** ****</div>
                <div class="card-details">
                    <div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">CARD HOLDER</div>
                        <div style="font-weight: 600;" id="cardHolderDisplay">YOUR NAME</div>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">EXPIRES</div>
                        <div style="font-weight: 600;" id="cardExpiryDisplay">MM/YY</div>
                    </div>
                </div>
            </div>
            
            <!-- Card Form -->
            <form id="creditCardForm">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="form-label">Cardholder Name</label>
                            <input type="text" class="form-control" id="cardHolder" placeholder="John Doe" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cvv" placeholder="123" maxlength="4" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Expiry Month</label>
                            <select class="form-control" id="expiryMonth" required>
                                <option value="">Month</option>
                                <option value="01">01 - January</option>
                                <option value="02">02 - February</option>
                                <option value="03">03 - March</option>
                                <option value="04">04 - April</option>
                                <option value="05">05 - May</option>
                                <option value="06">06 - June</option>
                                <option value="07">07 - July</option>
                                <option value="08">08 - August</option>
                                <option value="09">09 - September</option>
                                <option value="10">10 - October</option>
                                <option value="11">11 - November</option>
                                <option value="12">12 - December</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Expiry Year</label>
                            <select class="form-control" id="expiryYear" required>
                                <option value="">Year</option>
                                <option value="24">2024</option>
                                <option value="25">2025</option>
                                <option value="26">2026</option>
                                <option value="27">2027</option>
                                <option value="28">2028</option>
                                <option value="29">2029</option>
                                <option value="30">2030</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" placeholder="Enter your email for OTP verification" required>
                </div>
                
                <button type="submit" class="btn-pay">
                    <i class="fas fa-lock me-2"></i>Pay ₱<?php echo number_format($order['total_price'], 2); ?>
                </button>
                
                <div class="security-info">
                    <i class="fas fa-shield-alt me-2"></i>
                    Your payment information is encrypted and secure
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let selectedProvider = null;

        function selectProvider(provider) {
            selectedProvider = provider;

            // Update radio button
            document.querySelector(`input[value="${provider}"]`).checked = true;

            // Update visual selection
            document.querySelectorAll('.provider-option').forEach(el => {
                el.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }

        // Format card number input
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            if (formattedValue.length > 19) formattedValue = formattedValue.substr(0, 19);
            e.target.value = formattedValue;

            // Update card preview
            document.getElementById('cardNumberDisplay').textContent = formattedValue || '**** **** **** ****';
        });

        // Update cardholder name preview
        document.getElementById('cardHolder').addEventListener('input', function(e) {
            document.getElementById('cardHolderDisplay').textContent = e.target.value.toUpperCase() || 'YOUR NAME';
        });

        // Update expiry preview
        function updateExpiryDisplay() {
            const month = document.getElementById('expiryMonth').value;
            const year = document.getElementById('expiryYear').value;
            document.getElementById('cardExpiryDisplay').textContent =
                (month && year) ? `${month}/${year}` : 'MM/YY';
        }

        document.getElementById('expiryMonth').addEventListener('change', updateExpiryDisplay);
        document.getElementById('expiryYear').addEventListener('change', updateExpiryDisplay);

        // CVV input validation
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });

        document.getElementById('creditCardForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (!selectedProvider) {
                alert('Please select a card provider');
                return;
            }

            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const cardHolder = document.getElementById('cardHolder').value;
            const cvv = document.getElementById('cvv').value;
            const expiryMonth = document.getElementById('expiryMonth').value;
            const expiryYear = document.getElementById('expiryYear').value;
            const email = document.getElementById('email').value;

            if (!cardNumber || cardNumber.length < 13 || !cardHolder || !cvv || !expiryMonth || !expiryYear || !email) {
                alert('Please fill in all required fields');
                return;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return;
            }

            // Show loading state
            const button = document.querySelector('.btn-pay');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            button.disabled = true;

            // Send OTP
            const formData = new FormData();
            formData.append('action', 'send_card_otp');
            formData.append('email', email);
            formData.append('order_id', '<?php echo $order_id; ?>');
            formData.append('provider', selectedProvider);
            formData.append('card_number', cardNumber);
            formData.append('card_holder', cardHolder);

            fetch('process_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showOTPModal(email);
                } else {
                    alert(data.message || 'Failed to send OTP');
                }
                button.innerHTML = originalText;
                button.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
                button.innerHTML = originalText;
                button.disabled = false;
            });
        });

        function showOTPModal(email) {
            // Create OTP modal
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div class="modal fade" id="otpModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">OTP Verification</h5>
                            </div>
                            <div class="modal-body text-center">
                                <p>We've sent a verification code to:</p>
                                <strong>${email}</strong>
                                <div class="mt-3">
                                    <input type="text" class="form-control text-center" id="otpCode" placeholder="Enter 6-digit OTP" maxlength="6" style="font-size: 1.5rem; letter-spacing: 0.5rem;">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="closeOTPModal()">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="verifyOTP()">Verify</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            const otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
            otpModal.show();
        }

        function verifyOTP() {
            const otpCode = document.getElementById('otpCode').value;

            if (!otpCode || otpCode.length !== 6) {
                alert('Please enter a valid 6-digit OTP');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'verify_card_otp');
            formData.append('otp', otpCode);
            formData.append('order_id', '<?php echo $order_id; ?>');

            fetch('process_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        closeOTPModal();
                        window.location.href = `order_success.php?order_id=<?php echo $order_id; ?>`;
                    } else {
                        alert(data.message || 'Invalid OTP');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', text);
                    alert('Server response error. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while verifying OTP');
            });
        }

        function closeOTPModal() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('otpModal'));
            modal.hide();
            document.getElementById('otpModal').remove();
        }
    </script>
</body>
</html>
