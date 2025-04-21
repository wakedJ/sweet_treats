<?php
// Delete product AJAX handler
require_once "../../includes/db.php";

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if product ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$product_id = (int)$_POST['id'];

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // First delete from product_categories (if that table is in use)
    $delete_categories = "DELETE FROM product_categories WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $delete_categories);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    
    // Then delete the product
    $delete_product = "DELETE FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_product);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    
    // Commit the transaction
    mysqli_commit($conn);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>