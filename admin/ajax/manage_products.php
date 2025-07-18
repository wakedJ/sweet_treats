<?php
// Fixed manage_products.php - includes error handling and debugging improvements
// Place this file in: C:\xampp\htdocs\sweet_treats\admin\ajax\manage_products.php

// Start output buffering to prevent any accidental output before headers
ob_start();

// Set content type for JSON response
header('Content-Type: application/json');

// Initialize response variable
$response = ['success' => false, 'message' => 'Unknown error occurred'];

try {
    // Turn on error logging but prevent display in output
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
    
    // Verify database file exists
    $db_path = '../../includes/db.php';
    if (!file_exists($db_path)) {
        throw new Exception("Database connection file not found at: $db_path");
    }
    
    // Include database connection
    require_once $db_path;
    
    // Verify database connection
    if (!isset($conn)) {
        throw new Exception("Database connection variable not set. Check db.php");
    }
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Initialize session if needed
    session_start();
    
    // Log the request for debugging
    error_log('Request received: ' . $_SERVER['REQUEST_METHOD'] . ', Action: ' . ($_REQUEST['action'] ?? 'none'));
    
    // Handle different actions
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
    
    switch ($action) {
        case 'get_single':
            // Get a single product by ID
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                throw new Exception("Invalid product ID");
            }
            
            // Log the query for debugging
            error_log("Fetching product with ID: $id");
            
            $sql = "SELECT p.*, c.name as category_name 
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   WHERE p.id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("SQL Prepare Error: " . $conn->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("SQL Execute Error: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Product not found");
            }
            
            $product = $result->fetch_assoc();
            
            $response = [
                'success' => true,
                'message' => 'Product retrieved successfully',
                'product' => $product
            ];
            break;
            
        case 'update':
            // Update a product
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method. POST required.");
            }
            
            $product_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            
            if ($product_id <= 0) {
                throw new Exception("Invalid product ID");
            }
            
            // Check if the product exists
            $check_sql = "SELECT id, image FROM products WHERE id = ?";
            $check_stmt = $conn->prepare($check_sql);
            
            if (!$check_stmt) {
                throw new Exception("SQL Prepare Error: " . $conn->error);
            }
            
            $check_stmt->bind_param("i", $product_id);
            
            if (!$check_stmt->execute()) {
                throw new Exception("SQL Execute Error: " . $check_stmt->error);
            }
            
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                throw new Exception("Product not found");
            }
            
            $product = $check_result->fetch_assoc();
            $old_image = $product['image'];
            
            // Validate input
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            if (empty($name)) {
                throw new Exception("Product name is required");
            }
            
            $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
            if ($category_id <= 0) {
                throw new Exception("Please select a valid category");
            }
            
            $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
            if ($price <= 0) {
                throw new Exception("Please enter a valid price");
            }
            
            $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
            if ($stock < 0) {
                throw new Exception("Stock cannot be negative");
            }
            
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $tag = isset($_POST['tag']) ? trim($_POST['tag']) : 'none';
            $sale_price = null;
            
            if ($tag === 'on sale') {
                $sale_price = isset($_POST['sale_price']) ? (float)$_POST['sale_price'] : 0;
                if ($sale_price <= 0 || $sale_price >= $price) {
                    throw new Exception("Sale price must be greater than 0 and less than regular price");
                }
            }
            
            // Handle image upload
            $image_name = $old_image;
            
            if (!empty($_FILES['image']['name'])) {
                $upload_dir = '../../uploads/products/'; // Adjusted path
                
                // Ensure upload directory exists
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        throw new Exception("Failed to create upload directory: $upload_dir");
                    }
                }
                
                // Check if directory is writable
                if (!is_writable($upload_dir)) {
                    throw new Exception("Upload directory is not writable: $upload_dir");
                }
                
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                
                // Check file extension
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($file_ext, $allowed_extensions)) {
                    throw new Exception("Only JPG, JPEG, PNG, and GIF files are allowed");
                }
                
                // Generate unique filename
                $image_name = 'product_' . $product_id . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $image_name;
                
                // Check for upload errors
                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    $upload_errors = [
                        UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
                        UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form",
                        UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
                        UPLOAD_ERR_NO_FILE => "No file was uploaded",
                        UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
                        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
                        UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
                    ];
                    $error_message = isset($upload_errors[$_FILES['image']['error']]) ? 
                                    $upload_errors[$_FILES['image']['error']] : 
                                    "Unknown upload error";
                    throw new Exception("Image upload error: " . $error_message);
                }
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    throw new Exception("Failed to upload image. Error: " . (error_get_last()['message'] ?? 'Unknown error'));
                }
                
                // Delete old image if it exists
                if (!empty($old_image)) {
                    $old_image_path = $upload_dir . $old_image;
                    if (file_exists($old_image_path)) {
                        @unlink($old_image_path);
                    }
                }
            }
            
            // Update the product - Fixed parameter binding issue
            $sql = "UPDATE products SET 
                   name = ?, 
                   category_id = ?, 
                   price = ?, 
                   stock = ?, 
                   description = ?, 
                   image = ?,
                   tag = ?,
                   sale_price = ?,
                   updated_at = NOW()
                   WHERE id = ?";
                   
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("SQL Prepare Error: " . $conn->error);
            }
            
            // Fixed binding parameters - using correct types for all parameters
            $stmt->bind_param("sidissddi", $name, $category_id, $price, $stock, $description, $image_name, $tag, $sale_price, $product_id);
            
            if (!$stmt->execute()) {
                throw new Exception("SQL Execute Error: " . $stmt->error);
            }
            
            $response = [
                'success' => true,
                'message' => 'Product updated successfully',
                'product_id' => $product_id
            ];
            break;
            
        default:
            $response = [
                'success' => false,
                'message' => 'Invalid action: ' . $action
            ];
    }
    
} catch (Exception $e) {
    // Log the error
    error_log('Error in manage_products.php: ' . $e->getMessage());
    
    // Set error response
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

// Clean any buffered output to ensure only JSON is returned
ob_end_clean();

// Return JSON response
echo json_encode($response);
exit;