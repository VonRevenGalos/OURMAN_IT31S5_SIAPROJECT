<?php
require_once 'includes/session.php';

// Require login
requireLogin();

$currentUser = getCurrentUser();
$success = $_SESSION['profile_success'] ?? '';
$error = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);

// Generate CSRF token for delete account
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get complete user data including gender, date_of_birth, phone
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$currentUser['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['profile_error'] = "User not found.";
        header("Location: user.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['profile_error'] = "Failed to load user data.";
    header("Location: user.php");
    exit();
}

// Get user addresses
try {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$user['id']]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $addresses = [];
    $error = "Failed to load addresses.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ShoeARizz</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/profile.css">

    <style>
        .profile-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .profile-header-text {
            flex: 1;
        }

        .profile-header-actions {
            flex-shrink: 0;
        }

        .btn-delete-account {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-delete-account:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
            color: #fff;
        }

        .btn-delete-account:active {
            transform: translateY(0);
        }

        .btn-delete-account:focus {
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.5);
        }

        .btn-delete-account::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-delete-account:hover::before {
            left: 100%;
        }

        @media (max-width: 768px) {
            .profile-header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile-header-actions {
                width: 100%;
            }

            .btn-delete-account {
                width: 100%;
                justify-content: center;
            }
        }

        /* Modal styling */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header.bg-danger {
            border-radius: 12px 12px 0 0;
        }

        .modal-body ul {
            padding-left: 1.5rem;
        }

        .modal-body ul li {
            margin-bottom: 0.5rem;
        }

        #deleteConfirmation {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        #deleteConfirmation:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        #deleteConfirmation.valid {
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="profile-container">
        <div class="container">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="profile-header">
                        <div class="profile-header-content">
                            <div class="profile-header-text">
                                <h1 class="profile-title">
                                    <i class="fas fa-user-edit me-3"></i>My Profile
                                </h1>
                                <p class="profile-subtitle">Manage your personal information and shipping addresses</p>
                            </div>
                            <div class="profile-header-actions">
                                <button type="button" class="btn btn-danger btn-delete-account" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                    <i class="fas fa-trash-alt me-2"></i>Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Personal Information -->
                <div class="col-lg-6 mb-4">
                    <div class="profile-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user me-2"></i>Personal Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <form action="profile_update.php" method="POST" id="profileForm">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                                    <div class="form-text">Email cannot be changed</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo (isset($user['gender']) && $user['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo (isset($user['gender']) && $user['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo (isset($user['gender']) && $user['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                           value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                           placeholder="09123456789" maxlength="11">
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Addresses -->
                <div class="col-lg-6 mb-4">
                    <div class="profile-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">
                                <i class="fas fa-map-marker-alt me-2"></i>Shipping Addresses
                            </h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addressModal">
                                <i class="fas fa-plus me-1"></i>Add Address
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($addresses)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No shipping addresses added yet</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal">
                                        <i class="fas fa-plus me-2"></i>Add Your First Address
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="address-list">
                                    <?php foreach ($addresses as $address): ?>
                                        <div class="address-item">
                                            <div class="address-header">
                                                <h6 class="address-name">
                                                    <?php echo htmlspecialchars($address['full_name']); ?>
                                                    <?php if ($address['is_default']): ?>
                                                        <span class="badge bg-primary ms-2">Default</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <div class="address-actions">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editAddress(<?php echo htmlspecialchars(json_encode($address)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteAddress(<?php echo $address['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="address-details">
                                                <p class="mb-1"><?php echo htmlspecialchars($address['address_line1']); ?></p>
                                                <?php if ($address['address_line2']): ?>
                                                    <p class="mb-1"><?php echo htmlspecialchars($address['address_line2']); ?></p>
                                                <?php endif; ?>
                                                <p class="mb-1">
                                                    <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?>
                                                </p>
                                                <p class="mb-1"><?php echo htmlspecialchars($address['country']); ?></p>
                                                <?php if ($address['phone']): ?>
                                                    <p class="mb-0 text-muted">
                                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($address['phone']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Address Modal -->
    <div class="modal fade" id="addressModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <span id="modalTitle">Add New Address</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="profile_update.php" method="POST" id="addressForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_address">
                        <input type="hidden" name="address_id" id="address_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       placeholder="09123456789" maxlength="11">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address_line1" class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" id="address_line1" name="address_line1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address_line2" class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" id="address_line2" name="address_line2" 
                                   placeholder="Apartment, suite, unit, etc. (optional)">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" name="state" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="postal_code" class="form-label">ZIP/Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                       placeholder="1234" maxlength="4" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="country" class="form-label">Country</label>
                            <select class="form-select" id="country" name="country" required>
                                <option value="">Select Country</option>
                                <option value="Philippines" selected>Philippines</option>
                                <option value="United States">United States</option>
                                <option value="Canada">Canada</option>
                                <option value="Australia">Australia</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Japan">Japan</option>
                                <option value="South Korea">South Korea</option>
                                <option value="Singapore">Singapore</option>
                                <option value="Malaysia">Malaysia</option>
                                <option value="Thailand">Thailand</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                            <label class="form-check-label" for="is_default">
                                Set as default shipping address
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Account Confirmation Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAccountModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Account
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    <p class="mb-3">Are you sure you want to permanently delete your account? This will:</p>
                    <ul class="text-danger">
                        <li>Delete all your personal information</li>
                        <li>Remove all your saved addresses</li>
                        <li>Clear your order history</li>
                        <li>Remove all items from your cart and favorites</li>
                        <li>Delete all your notifications</li>
                        <li>Permanently log you out</li>
                    </ul>
                    <div class="mt-4 p-3 bg-light rounded">
                        <p class="mb-2 fw-bold text-dark">Type "DELETE" to confirm:</p>
                        <input type="text" class="form-control" id="deleteConfirmation" placeholder="Type DELETE to confirm" autocomplete="off">
                        <small class="text-muted mt-1 d-block">This confirmation is case-sensitive</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                        <i class="fas fa-trash-alt me-2"></i>Delete My Account
                    </button>
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
    <script src="assets/js/profile.js"></script>

    <!-- Delete Account JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteConfirmation = document.getElementById('deleteConfirmation');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            // Enable/disable delete button based on confirmation text
            deleteConfirmation.addEventListener('input', function() {
                const isValid = this.value.trim().toUpperCase() === 'DELETE';
                confirmDeleteBtn.disabled = !isValid;

                if (isValid) {
                    this.classList.add('valid');
                } else {
                    this.classList.remove('valid');
                }
            });

            // Handle delete account confirmation
            confirmDeleteBtn.addEventListener('click', function() {
                if (deleteConfirmation.value.trim().toUpperCase() === 'DELETE') {
                    // Show loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting Account...';
                    this.disabled = true;

                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'delete_account.php';
                    form.style.display = 'none';

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = 'csrf_token';
                    csrfToken.value = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';

                    const confirmInput = document.createElement('input');
                    confirmInput.type = 'hidden';
                    confirmInput.name = 'confirm_delete';
                    confirmInput.value = 'DELETE';

                    form.appendChild(csrfToken);
                    form.appendChild(confirmInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });

            // Reset modal when closed
            document.getElementById('deleteAccountModal').addEventListener('hidden.bs.modal', function() {
                deleteConfirmation.value = '';
                deleteConfirmation.classList.remove('valid');
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = '<i class="fas fa-trash-alt me-2"></i>Delete My Account';
            });
        });
    </script>
</body>
</html>
