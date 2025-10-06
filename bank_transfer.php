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
    <title>Bank Transfer - ShoeARizz</title>

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
            background-color: #f5f7fa;
            color: #000000;
        }

        .bank-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .bank-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }

        .bank-content {
            background: white;
            padding: 2rem;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .bank-selection {
            margin-bottom: 2rem;
        }

        .bank-option {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .bank-option:hover {
            border-color: #2a5298;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 82, 152, 0.2);
        }

        .bank-option.selected {
            border-color: #2a5298;
            background-color: #f8f9ff;
        }

        .bank-logo {
            width: 80px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
        }

        .bank-details {
            flex: 1;
        }

        .bank-name {
            font-weight: 600;
            font-size: 1.2rem;
            color: #000000;
            margin-bottom: 0.25rem;
        }

        .bank-desc {
            color: #666666;
            font-size: 0.9rem;
        }

        .account-form {
            display: none;
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            margin-top: 2rem;
        }

        .account-form.show {
            display: block;
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
            border-color: #2a5298;
            box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.25);
        }

        .btn-proceed {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-proceed:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 82, 152, 0.3);
        }

        .btn-proceed:disabled {
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
            border-top: 2px solid #2a5298;
            padding-top: 0.5rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="bank-container">
        <div class="bank-header">
            <h1><i class="fas fa-university me-2"></i>Bank Transfer Payment</h1>
            <p class="mb-0">Secure online banking transfer</p>
        </div>

        <div class="bank-content">
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

            <!-- Bank Selection -->
            <div class="bank-selection">
                <h4 class="mb-3">Select Your Bank</h4>

                <div class="bank-option" onclick="selectBank('bdo')">
                    <input type="radio" name="selected_bank" value="bdo" class="form-check-input">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjYwIiB2aWV3Qm94PSIwIDAgMTAwIDYwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjMDA0NkFEIi8+Cjx0ZXh0IHg9IjUwIiB5PSIzNSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkJETzwvdGV4dD4KPC9zdmc+" alt="BDO" class="bank-logo">
                    <div class="bank-details">
                        <div class="bank-name">Banco de Oro (BDO)</div>
                        <div class="bank-desc">Philippines' largest bank</div>
                    </div>
                </div>

                <div class="bank-option" onclick="selectBank('bpi')">
                    <input type="radio" name="selected_bank" value="bpi" class="form-check-input">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjYwIiB2aWV3Qm94PSIwIDAgMTAwIDYwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRDUyQjFFIi8+Cjx0ZXh0IHg9IjUwIiB5PSIzNSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkJQSTwvdGV4dD4KPC9zdmc+" alt="BPI" class="bank-logo">
                    <div class="bank-details">
                        <div class="bank-name">Bank of the Philippine Islands (BPI)</div>
                        <div class="bank-desc">Trusted banking since 1851</div>
                    </div>
                </div>

                <div class="bank-option" onclick="selectBank('metrobank')">
                    <input type="radio" name="selected_bank" value="metrobank" class="form-check-input">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjYwIiB2aWV3Qm94PSIwIDAgMTAwIDYwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRkY2QjAwIi8+Cjx0ZXh0IHg9IjUwIiB5PSIzMCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEyIiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk1ldHJvPC90ZXh0Pgo8dGV4dCB4PSI1MCIgeT0iNDUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxMiIgZm9udC13ZWlnaHQ9ImJvbGQiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5CYW5rPC90ZXh0Pgo8L3N2Zz4=" alt="Metrobank" class="bank-logo">
                    <div class="bank-details">
                        <div class="bank-name">Metropolitan Bank & Trust Co. (Metrobank)</div>
                        <div class="bank-desc">Your partner bank</div>
                    </div>
                </div>
            </div>

            <!-- Account Details Form -->
            <div class="account-form" id="accountForm">
                <h4 class="mb-3">Enter Your Account Details</h4>
                <form id="bankTransferForm">
                    <div class="form-group">
                        <label class="form-label">Account Number</label>
                        <input type="text" class="form-control" id="accountNumber" placeholder="Enter your account number" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Account Holder Name</label>
                        <input type="text" class="form-control" id="accountName" placeholder="Enter account holder name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" placeholder="Enter your email for OTP verification" required>
                    </div>

                    <button type="submit" class="btn-proceed">
                        <i class="fas fa-lock me-2"></i>Proceed with Transfer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let selectedBank = null;

        function selectBank(bank) {
            selectedBank = bank;

            // Update radio button
            document.querySelector(`input[value="${bank}"]`).checked = true;

            // Update visual selection
            document.querySelectorAll('.bank-option').forEach(el => {
                el.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            // Show account form
            document.getElementById('accountForm').classList.add('show');
        }

        document.getElementById('bankTransferForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (!selectedBank) {
                alert('Please select a bank first');
                return;
            }

            const accountNumber = document.getElementById('accountNumber').value;
            const accountName = document.getElementById('accountName').value;
            const email = document.getElementById('email').value;

            if (!accountNumber || !accountName || !email) {
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
            const button = document.querySelector('.btn-proceed');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            button.disabled = true;

            // Send OTP
            const formData = new FormData();
            formData.append('action', 'send_bank_otp');
            formData.append('email', email);
            formData.append('order_id', '<?php echo $order_id; ?>');
            formData.append('bank', selectedBank);
            formData.append('account_number', accountNumber);
            formData.append('account_name', accountName);

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
            formData.append('action', 'verify_bank_otp');
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
