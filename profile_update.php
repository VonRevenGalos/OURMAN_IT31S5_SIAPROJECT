<?php
session_start();
require_once 'db.php';
require_once 'includes/session.php';

// Require login
requireLogin();

$user = getCurrentUser();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'update_profile':
            updateProfile($pdo, $user['id']);
            break;
        case 'save_address':
            saveAddress($pdo, $user['id']);
            break;
        case 'delete_address':
            deleteAddress($pdo, $user['id']);
            break;
        default:
            $_SESSION['profile_error'] = "Invalid action.";
            break;
    }
} catch (Exception $e) {
    $_SESSION['profile_error'] = "An error occurred: " . $e->getMessage();
}

header("Location: profile.php");
exit();

function updateProfile($pdo, $userId) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($username)) {
        $_SESSION['profile_error'] = "First name, last name, and username are required.";
        return;
    }
    
    // Validate phone number format if provided (11 digits)
    if (!empty($phone) && !preg_match('/^\d{11}$/', $phone)) {
        $_SESSION['profile_error'] = "Please enter a valid 11-digit phone number.";
        return;
    }
    
    // Check if username is already taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $userId]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['profile_error'] = "Username is already taken.";
        return;
    }
    
    // Update user profile
    $stmt = $pdo->prepare("UPDATE users SET 
                          first_name = ?, 
                          last_name = ?, 
                          username = ?, 
                          gender = ?, 
                          date_of_birth = ?, 
                          phone = ? 
                          WHERE id = ?");
    
    $stmt->execute([
        $first_name, 
        $last_name, 
        $username, 
        $gender ?: null, 
        $date_of_birth ?: null, 
        $phone ?: null, 
        $userId
    ]);
    
    $_SESSION['profile_success'] = "Profile updated successfully!";
}

function saveAddress($pdo, $userId) {
    $address_id = $_POST['address_id'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // Validate required fields
    if (empty($full_name) || empty($address_line1) || empty($city) || 
        empty($state) || empty($postal_code) || empty($country)) {
        $_SESSION['profile_error'] = "Please fill in all required address fields.";
        return;
    }
    
    // Validate phone number format if provided (11 digits)
    if (!empty($phone) && !preg_match('/^\d{11}$/', $phone)) {
        $_SESSION['profile_error'] = "Please enter a valid 11-digit phone number.";
        return;
    }
    
    // Validate postal code (should be numeric for Philippines)
    if (!preg_match('/^\d{4}$/', $postal_code)) {
        $_SESSION['profile_error'] = "Please enter a valid 4-digit postal code.";
        return;
    }
    
    // If this is set as default, unset other default addresses
    if ($is_default) {
        $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    
    if ($address_id) {
        // Update existing address
        $stmt = $pdo->prepare("UPDATE user_addresses SET 
                              full_name = ?, 
                              phone = ?, 
                              address_line1 = ?, 
                              address_line2 = ?, 
                              city = ?, 
                              state = ?, 
                              postal_code = ?, 
                              country = ?, 
                              is_default = ?, 
                              updated_at = NOW() 
                              WHERE id = ? AND user_id = ?");
        
        $stmt->execute([
            $full_name, $phone, $address_line1, $address_line2, 
            $city, $state, $postal_code, $country, $is_default, 
            $address_id, $userId
        ]);
        
        $_SESSION['profile_success'] = "Address updated successfully!";
    } else {
        // Insert new address
        $stmt = $pdo->prepare("INSERT INTO user_addresses 
                              (user_id, type, full_name, phone, address_line1, address_line2, 
                               city, state, postal_code, country, is_default, created_at, updated_at) 
                              VALUES (?, 'shipping', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $stmt->execute([
            $userId, $full_name, $phone, $address_line1, $address_line2, 
            $city, $state, $postal_code, $country, $is_default
        ]);
        
        $_SESSION['profile_success'] = "Address added successfully!";
    }
}

function deleteAddress($pdo, $userId) {
    $address_id = $_POST['address_id'] ?? '';
    
    if (empty($address_id)) {
        $_SESSION['profile_error'] = "Invalid address ID.";
        return;
    }
    
    // Check if address belongs to user
    $stmt = $pdo->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$address_id, $userId]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['profile_error'] = "Address not found.";
        return;
    }
    
    // Delete address
    $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$address_id, $userId]);
    
    $_SESSION['profile_success'] = "Address deleted successfully!";
}
?>
