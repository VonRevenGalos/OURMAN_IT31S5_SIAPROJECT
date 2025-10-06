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
    <title>GCash Payment - ShoeARizz</title>
    
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
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            min-height: 100vh;
            color: #000000;
        }
        
        .gcash-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .gcash-header {
            background: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .gcash-logo {
            width: 120px;
            height: 60px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .gcash-content {
            background: white;
            padding: 2rem;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .mobile-mockup {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border-radius: 25px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .mobile-mockup::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .mobile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .balance-display {
            text-align: center;
            margin: 2rem 0;
        }
        
        .balance-amount {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .balance-label {
            font-size: 1rem;
            opacity: 0.9;
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
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .phone-input {
            position: relative;
        }
        
        .phone-prefix {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666666;
            font-weight: 500;
            z-index: 2;
        }
        
        .phone-input input {
            padding-left: 3rem;
        }
        
        .btn-gcash {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-gcash:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
        
        .btn-gcash:disabled {
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
            border-top: 2px solid #007bff;
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
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }
        
        .feature-list li i {
            color: #007bff;
            margin-right: 0.5rem;
            width: 20px;
        }
    </style>
</head>
<body>
    <div class="gcash-container">
        <div class="gcash-header">
            <div class="gcash-logo">GCash</div>
            <h1>GCash Payment</h1>
            <p class="mb-0">Fast, secure, and convenient</p>
        </div>
        
        <div class="gcash-content">
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
            
            <!-- Mobile Mockup -->
            <div class="mobile-mockup">
                <div class="mobile-header">
                    <div style="font-size: 1.2rem; font-weight: 600;">GCash</div>
                    <div style="display: flex; gap: 0.5rem;">
                        <i class="fas fa-signal"></i>
                        <i class="fas fa-wifi"></i>
                        <i class="fas fa-battery-full"></i>
                    </div>
                </div>
                
                <div class="balance-display">
                    <div class="balance-amount">₱*******</div>
                    <div class="balance-label">Available Balance</div>
                </div>
                
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Instant transfer</li>
                    <li><i class="fas fa-shield-alt"></i> Bank-level security</li>
                    <li><i class="fas fa-mobile-alt"></i> Mobile convenience</li>
                </ul>
            </div>
            
            <!-- Payment Form -->
            <form id="gcashForm">
                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <div class="phone-input">
                        <span class="phone-prefix">+63</span>
                        <input type="tel" class="form-control" id="mobileNumber" placeholder="XXX XXX XXXX" maxlength="12" required>
                    </div>
                    <small class="text-muted">Enter your 10-digit mobile number (without +63)</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" placeholder="Enter your email for OTP verification" required>
                </div>
                
                <button type="submit" class="btn-gcash">
                    <i class="fas fa-mobile-alt me-2"></i>Pay with GCash
                </button>
                
                <div class="security-info">
                    <i class="fas fa-shield-alt me-2"></i>
                    Your transaction is protected by GCash security
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Format mobile number input
        document.getElementById('mobileNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            // Ensure it starts with 9
            if (value.length > 0 && value[0] !== '9') {
                value = '9' + value.slice(1);
            }
            
            // Limit to 10 digits
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            
            // Format as XXX XXX XXXX
            if (value.length > 6) {
                value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6);
            } else if (value.length > 3) {
                value = value.slice(0, 3) + ' ' + value.slice(3);
            }
            
            e.target.value = value;
        });
        
        document.getElementById('gcashForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const mobileNumber = document.getElementById('mobileNumber').value.replace(/\s/g, '');
            const email = document.getElementById('email').value;
            
            // Validate mobile number
            if (!mobileNumber || mobileNumber.length !== 10 || !mobileNumber.startsWith('9')) {
                alert('Please enter a valid 10-digit mobile number starting with 9');
                return;
            }
            
            if (!email) {
                alert('Please enter your email address');
                return;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return;
            }
            
            // Show loading state
            const button = document.querySelector('.btn-gcash');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            button.disabled = true;
            
            // Send OTP
            const formData = new FormData();
            formData.append('action', 'send_gcash_otp');
            formData.append('email', email);
            formData.append('order_id', '<?php echo $order_id; ?>');
            formData.append('mobile_number', '+63' + mobileNumber);
            
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
            formData.append('action', 'verify_gcash_otp');
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
