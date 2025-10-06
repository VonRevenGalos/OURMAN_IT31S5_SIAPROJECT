<?php
// Set JSON header and error reporting
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output

require_once 'includes/session.php';
require_once 'db.php';
require 'vendor/PHPMailer/src/Exception.php';
require 'vendor/PHPMailer/src/PHPMailer.php';
require 'vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = getUserId();
$action = $_POST['action'] ?? '';

// Function to send OTP email (from signup_process.php)
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@shoearizz.store';
        $mail->Password   = 'Astron_202';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('noreply@shoearizz.store', 'ShoeARizz');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Payment Verification Code - ShoeARizz';
        $mail->Body    = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f8f9fa; }
                .otp-code { font-size: 24px; font-weight: bold; color: #007bff; text-align: center; padding: 20px; background-color: white; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>ShoeARizz Payment Verification</h1>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>Your payment verification code is:</p>
                    <div class='otp-code'>$otp</div>
                    <p>This code will expire in 10 minutes. Please do not share this code with anyone.</p>
                    <p>If you did not request this code, please ignore this email.</p>
                    <p>Thank you for shopping with ShoeARizz!</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Generate 6-digit OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

switch ($action) {
    case 'send_bank_otp':
        $email = $_POST['email'] ?? '';
        $order_id = $_POST['order_id'] ?? '';
        $bank = $_POST['bank'] ?? '';
        $account_number = $_POST['account_number'] ?? '';
        $account_name = $_POST['account_name'] ?? '';
        
        if (!$email || !$order_id || !$bank || !$account_number || !$account_name) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        // Verify order belongs to user
        try {
            $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $user_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Invalid order']);
                exit();
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit();
        }
        
        $otp = generateOTP();
        
        // Store OTP in session
        $_SESSION['payment_otp'] = $otp;
        $_SESSION['payment_otp_time'] = time();
        $_SESSION['payment_order_id'] = $order_id;
        $_SESSION['payment_type'] = 'bank_transfer';
        $_SESSION['payment_data'] = [
            'bank' => $bank,
            'account_number' => $account_number,
            'account_name' => $account_name
        ];
        
        if (sendOTPEmail($email, $otp)) {
            echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
        }
        break;
        
    case 'send_card_otp':
        $email = $_POST['email'] ?? '';
        $order_id = $_POST['order_id'] ?? '';
        $provider = $_POST['provider'] ?? '';
        $card_number = $_POST['card_number'] ?? '';
        $card_holder = $_POST['card_holder'] ?? '';
        
        if (!$email || !$order_id || !$provider || !$card_number || !$card_holder) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        // Verify order belongs to user
        try {
            $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $user_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Invalid order']);
                exit();
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit();
        }
        
        $otp = generateOTP();
        
        // Store OTP in session
        $_SESSION['payment_otp'] = $otp;
        $_SESSION['payment_otp_time'] = time();
        $_SESSION['payment_order_id'] = $order_id;
        $_SESSION['payment_type'] = 'credit_card';
        $_SESSION['payment_data'] = [
            'provider' => $provider,
            'card_number' => substr($card_number, -4), // Store only last 4 digits
            'card_holder' => $card_holder
        ];
        
        if (sendOTPEmail($email, $otp)) {
            echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
        }
        break;
        
    case 'send_gcash_otp':
        $email = $_POST['email'] ?? '';
        $order_id = $_POST['order_id'] ?? '';
        $mobile_number = $_POST['mobile_number'] ?? '';
        
        if (!$email || !$order_id || !$mobile_number) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        // Verify order belongs to user
        try {
            $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $user_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Invalid order']);
                exit();
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit();
        }
        
        $otp = generateOTP();
        
        // Store OTP in session
        $_SESSION['payment_otp'] = $otp;
        $_SESSION['payment_otp_time'] = time();
        $_SESSION['payment_order_id'] = $order_id;
        $_SESSION['payment_type'] = 'gcash';
        $_SESSION['payment_data'] = [
            'mobile_number' => $mobile_number
        ];
        
        if (sendOTPEmail($email, $otp)) {
            echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
        }
        break;
        
    case 'verify_bank_otp':
    case 'verify_card_otp':
    case 'verify_gcash_otp':
        $otp = $_POST['otp'] ?? '';
        $order_id = $_POST['order_id'] ?? '';
        
        if (!$otp || !$order_id) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        // Check if OTP is valid
        if (!isset($_SESSION['payment_otp']) ||
            !isset($_SESSION['payment_otp_time']) ||
            !isset($_SESSION['payment_order_id']) ||
            $_SESSION['payment_order_id'] != $order_id) {

            $debug_info = [
                'has_otp' => isset($_SESSION['payment_otp']),
                'has_time' => isset($_SESSION['payment_otp_time']),
                'has_order_id' => isset($_SESSION['payment_order_id']),
                'session_order_id' => $_SESSION['payment_order_id'] ?? 'not set',
                'request_order_id' => $order_id
            ];
            error_log("Session validation failed: " . json_encode($debug_info));

            echo json_encode(['success' => false, 'message' => 'Invalid session', 'debug' => $debug_info]);
            exit();
        }
        
        // Check if OTP has expired (10 minutes)
        if (time() - $_SESSION['payment_otp_time'] > 600) {
            unset($_SESSION['payment_otp'], $_SESSION['payment_otp_time'], $_SESSION['payment_order_id']);
            echo json_encode(['success' => false, 'message' => 'OTP has expired']);
            exit();
        }
        
        // Verify OTP
        if ($_SESSION['payment_otp'] !== $otp) {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
            exit();
        }
        
        // OTP is valid, process the payment
        try {
            $pdo->beginTransaction();
            
            // Update order status to Pending
            $stmt = $pdo->prepare("UPDATE orders SET status = 'Pending' WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $user_id]);
            
            // Get order items to update product quantities
            $stmt = $pdo->prepare("
                SELECT oi.product_id, oi.size, oi.quantity 
                FROM order_items oi 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Update product quantities
            foreach ($order_items as $item) {
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Clear cart after successful payment (only selected items if any)
            if (isset($_SESSION['payment_selected_cart_ids']) && !empty($_SESSION['payment_selected_cart_ids'])) {
                // Clear only selected items
                $selected_cart_ids = $_SESSION['payment_selected_cart_ids'];
                $cart_ids = explode(',', $selected_cart_ids);
                $cart_ids = array_map('intval', $cart_ids);
                $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND cart_id IN ($placeholders)");
                $params = array_merge([$user_id], $cart_ids);
                $stmt->execute($params);
            } else {
                // Clear all cart items if no specific selection
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
            }

            $pdo->commit();
            
            // Clear session data
            unset($_SESSION['payment_otp'], $_SESSION['payment_otp_time'], $_SESSION['payment_order_id'], $_SESSION['payment_type'], $_SESSION['payment_data'], $_SESSION['payment_selected_cart_ids']);
            
            echo json_encode(['success' => true, 'message' => 'Payment verified successfully']);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Payment processing error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()]);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("General error in payment processing: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
