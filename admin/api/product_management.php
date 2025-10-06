<?php
/**
 * Product Management API
 * Handles Add, Edit, Delete operations for products
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Disable display_errors and error reporting for API responses
ini_set('display_errors', 0);
error_reporting(0);

// Include admin authentication
require_once __DIR__ . '/../includes/admin_auth.php';

// Require admin login
requireAdminLogin();

// Validate admin session
if (!validateAdminSession()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

// Clean any output buffer and set JSON response header
ob_clean();
header('Content-Type: application/json; charset=utf-8');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            if ($method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            addProduct();
            break;
            
        case 'edit':
            if ($method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            editProduct();
            break;
            
        case 'delete':
            if ($method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            deleteProduct();
            break;
            
        case 'get':
            if ($method !== 'GET') {
                throw new Exception('Invalid request method');
            }
            getProduct();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function addProduct() {
    global $pdo;
    
    // Validate required fields
    $required_fields = ['title', 'price', 'stock', 'gender', 'category', 'color', 'height', 'width'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Validate and process file uploads
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Main image is required');
    }
    
    // Process main image
    $mainImage = processImageUpload($_FILES['image'], $_POST['gender'], $_POST['category']);
    
    // Process thumbnails (optional)
    $thumbnail1 = null;
    $thumbnail2 = null;
    $thumbnail3 = null;
    
    if (isset($_FILES['thumbnail1']) && $_FILES['thumbnail1']['error'] === UPLOAD_ERR_OK) {
        $thumbnail1 = processImageUpload($_FILES['thumbnail1'], $_POST['gender'], $_POST['category'], '1');
    }
    
    if (isset($_FILES['thumbnail2']) && $_FILES['thumbnail2']['error'] === UPLOAD_ERR_OK) {
        $thumbnail2 = processImageUpload($_FILES['thumbnail2'], $_POST['gender'], $_POST['category'], '2');
    }
    
    if (isset($_FILES['thumbnail3']) && $_FILES['thumbnail3']['error'] === UPLOAD_ERR_OK) {
        $thumbnail3 = processImageUpload($_FILES['thumbnail3'], $_POST['gender'], $_POST['category'], '3');
    }
    
    // Insert into database
    $sql = "INSERT INTO products (title, price, stock, image, category, description, thumbnail1, thumbnail2, thumbnail3, color, height, width, brand, collection) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['title'],
        $_POST['price'],
        $_POST['stock'],
        $mainImage,
        $_POST['category'],
        $_POST['description'] ?? null,
        $thumbnail1,
        $thumbnail2,
        $thumbnail3,
        $_POST['color'],
        $_POST['height'],
        $_POST['width'],
        $_POST['brand'] ?? 'Generic',
        $_POST['collection'] ?? 'Standard'
    ]);
    
    if ($result) {
        $productId = $pdo->lastInsertId();
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Product added successfully',
            'product_id' => $productId,
            'images_uploaded' => [
                'main' => $mainImage,
                'thumbnail1' => $thumbnail1,
                'thumbnail2' => $thumbnail2,
                'thumbnail3' => $thumbnail3
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        ob_end_flush();
    } else {
        throw new Exception('Failed to add product to database');
    }
}

function editProduct() {
    global $pdo;
    
    // Validate product ID
    if (empty($_POST['product_id'])) {
        throw new Exception('Product ID is required');
    }
    
    $productId = $_POST['product_id'];
    
    // Get current product data
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentProduct) {
        throw new Exception('Product not found');
    }
    
    // Validate required fields
    $required_fields = ['title', 'price', 'stock', 'gender', 'category', 'color', 'height', 'width'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Process image uploads (optional for edit)
    $mainImage = $currentProduct['image'];
    $thumbnail1 = $currentProduct['thumbnail1'];
    $thumbnail2 = $currentProduct['thumbnail2'];
    $thumbnail3 = $currentProduct['thumbnail3'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $mainImage = processImageUpload($_FILES['image'], $_POST['gender'], $_POST['category']);
    }
    
    if (isset($_FILES['thumbnail1']) && $_FILES['thumbnail1']['error'] === UPLOAD_ERR_OK) {
        $thumbnail1 = processImageUpload($_FILES['thumbnail1'], $_POST['gender'], $_POST['category'], '1');
    }

    if (isset($_FILES['thumbnail2']) && $_FILES['thumbnail2']['error'] === UPLOAD_ERR_OK) {
        $thumbnail2 = processImageUpload($_FILES['thumbnail2'], $_POST['gender'], $_POST['category'], '2');
    }

    if (isset($_FILES['thumbnail3']) && $_FILES['thumbnail3']['error'] === UPLOAD_ERR_OK) {
        $thumbnail3 = processImageUpload($_FILES['thumbnail3'], $_POST['gender'], $_POST['category'], '3');
    }
    
    // Update database
    $sql = "UPDATE products SET title = ?, price = ?, stock = ?, image = ?, category = ?, description = ?, 
            thumbnail1 = ?, thumbnail2 = ?, thumbnail3 = ?, color = ?, height = ?, width = ?, brand = ?, collection = ? 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['title'],
        $_POST['price'],
        $_POST['stock'],
        $mainImage,
        $_POST['category'],
        $_POST['description'] ?? null,
        $thumbnail1,
        $thumbnail2,
        $thumbnail3,
        $_POST['color'],
        $_POST['height'],
        $_POST['width'],
        $_POST['brand'] ?? 'Generic',
        $_POST['collection'] ?? 'Standard',
        $productId
    ]);
    
    if ($result) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully',
            'product_id' => $productId,
            'images_updated' => [
                'main' => $mainImage,
                'thumbnail1' => $thumbnail1,
                'thumbnail2' => $thumbnail2,
                'thumbnail3' => $thumbnail3
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        ob_end_flush();
    } else {
        throw new Exception('Failed to update product in database');
    }
}

function deleteProduct() {
    global $pdo;
    
    // Validate product ID
    if (empty($_POST['product_id'])) {
        throw new Exception('Product ID is required');
    }
    
    $productId = $_POST['product_id'];
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT id, title FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $result = $stmt->execute([$productId]);
    
    if ($result) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully',
            'product_id' => $productId,
            'product_title' => $product['title']
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        ob_end_flush();
    } else {
        throw new Exception('Failed to delete product from database');
    }
}

function getProduct() {
    global $pdo;
    
    // Validate product ID
    if (empty($_GET['product_id'])) {
        throw new Exception('Product ID is required');
    }
    
    $productId = $_GET['product_id'];
    
    // Get product data
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Determine gender from category
    $gender = 'men'; // default
    if (strpos($product['category'], 'women') !== false) {
        $gender = 'women';
    } elseif (strpos($product['category'], 'kids') !== false) {
        $gender = 'kids';
    }
    
    $product['gender'] = $gender;

    ob_clean();
    echo json_encode(['success' => true, 'product' => $product], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function processImageUpload($file, $gender, $category, $suffix = '') {
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Only JPG and WEBP files are allowed');
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size must be less than 5MB');
    }

    // Map category to correct directory structure
    // Categories from the form already have correct prefixes:
    // Men: sneakers, running, athletics
    // Women: womensneakers, womenrunning, womenathletics
    // Kids: kidsneakers, kidsathletics, kidslipon
    $cleanCategory = $category;

    // Get the absolute path to the public_html directory
    $publicHtmlPath = dirname(dirname(__DIR__)); // Go up two levels from admin/api/
    $uploadDir = $publicHtmlPath . '/assets/img/' . $gender . '/' . $cleanCategory . '/';

    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory: ' . $uploadDir);
        }
    }

    // Generate filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = pathinfo($file['name'], PATHINFO_FILENAME);
    $filename = preg_replace('/[^a-zA-Z0-9]/', '', $filename);

    if ($suffix) {
        $filename .= $suffix;
    }

    $finalFilename = $filename . '.' . $extension;
    $uploadPath = $uploadDir . $finalFilename;

    // Debug: Log the upload path (only to error log, not output)
    error_log("Upload path: " . $uploadPath);
    error_log("Upload directory exists: " . (file_exists($uploadDir) ? 'Yes' : 'No'));
    error_log("Upload directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No'));

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $error = error_get_last();
        throw new Exception('Failed to upload file to: ' . $uploadPath . ' Error: ' . ($error['message'] ?? 'Unknown error'));
    }

    // Verify file was uploaded
    if (!file_exists($uploadPath)) {
        throw new Exception('File upload verification failed');
    }

    // Return relative path for database
    return 'assets/img/' . $gender . '/' . $cleanCategory . '/' . $finalFilename;
}
