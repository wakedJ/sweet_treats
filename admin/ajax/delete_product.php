<?php
// File: admin/ajax/delete-product.php
// Purpose: Delete a product and its related information

// Always return JSON
header('Content-Type: application/json');

// Enable error logging to a file instead of outputting to response
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Include database connection
require_once "../../includes/db.php"; // Fixed path to db.php

// Initialize response
$response = ['success' => false, 'message' => 'Invalid request'];

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

// Check if product ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $response['message'] = 'Product ID is required';
    echo json_encode($response);
    exit;
}

$product_id = (int)$_POST['id'];

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Get product image information before deletion
    $image_query = "SELECT image FROM products WHERE id = ?";
    $image_stmt = $conn->prepare($image_query);
    
    if (!$image_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $image_stmt->bind_param("i", $product_id);
    
    if (!$image_stmt->execute()) {
        throw new Exception("Execute failed: " . $image_stmt->error);
    }
    
    $image_result = $image_stmt->get_result();
    
    if ($image_result->num_rows === 0) {
        throw new Exception("Product not found with ID: " . $product_id);
    }
    
    $product = $image_result->fetch_assoc();
    $image_name = $product['image'];
    
    // Delete the product
    $delete_product = "DELETE FROM products WHERE id = ?";
    $prod_stmt = $conn->prepare($delete_product);
    
    if (!$prod_stmt) {
        throw new Exception("Prepare delete failed: " . $conn->error);
    }
    
    $prod_stmt->bind_param("i", $product_id);
    
    if (!$prod_stmt->execute()) {
        throw new Exception("Failed to delete product: " . $prod_stmt->error);
    }
    
    if ($prod_stmt->affected_rows === 0) {
        throw new Exception("No rows were deleted. Product ID might not exist.");
    }
    
    // Commit the transaction
    $conn->commit();
    
    // Delete the product image if it exists
    if (!empty($image_name)) {
        $upload_dir = '../../uploads/products/'; // Fixed path to upload directory
        $image_path = $upload_dir . $image_name;
        if (file_exists($image_path)) {
            if (!unlink($image_path)) {
                // Just log this error, don't fail the whole operation
                error_log("Could not delete image file: $image_path");
            }
        }
    }
    
    $response = [
        'success' => true,
        'message' => 'Product deleted successfully',
        'product_id' => $product_id
    ];
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    
    error_log("Product deletion error: " . $e->getMessage());
    
    $response = [
        'success' => false, 
        'message' => 'Error deleting product: ' . $e->getMessage()
    ];
}

// Return JSON response
echo json_encode($response);
exit;