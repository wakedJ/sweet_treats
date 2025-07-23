<?php
/**
 * Rating Handler for Sweet Treats
 * This file handles AJAX requests for product ratings
 */

// Prevent any output before JSON response
ob_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1);

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection - adjust path based on this file being in partials folder
require_once '../includes/db.php'; // Path adjusted for partials folder

// Function to send JSON response and exit
function sendResponse($success, $message = '', $data = []) {
    $response = array_merge(['success' => $success], $data);
    if ($message) {
        $response['message'] = $message;
    }
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check if we have a valid action
if (!isset($_POST['action']) || $_POST['action'] !== 'save_rating') {
    sendResponse(false, 'Invalid action');
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendResponse(false, 'User not logged in');
}

// Validate required parameters
if (!isset($_POST['product_id']) || !isset($_POST['rating'])) {
    sendResponse(false, 'Missing required parameters');
}

// Sanitize and validate inputs
$user_id = (int)$_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];
$rating = (int)$_POST['rating'];

// Validate inputs
if ($user_id <= 0) {
    sendResponse(false, 'Invalid user ID');
}

if ($product_id <= 0) {
    sendResponse(false, 'Invalid product ID');
}

if ($rating < 1 || $rating > 5) {
    sendResponse(false, 'Rating must be between 1 and 5 stars');
}

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    sendResponse(false, 'Database connection failed: ' . $conn->connect_error);
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // First, verify that the user has actually purchased this product and it's delivered
    $verify_purchase_sql = "
        SELECT COUNT(*) as count 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE oi.product_id = ? 
        AND o.user_id = ?
        AND LOWER(o.status) IN ('delivered', 'completed')

    ";
    
    $verify_stmt = $conn->prepare($verify_purchase_sql);
    if (!$verify_stmt) {
        throw new Exception('Failed to prepare verification query: ' . $conn->error);
    }
    
    $verify_stmt->bind_param("ii", $product_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $purchase_data = $verify_result->fetch_assoc();
    $verify_stmt->close();
    
    if ($purchase_data['count'] == 0) {
        $conn->rollback();
        sendResponse(false, 'You can only rate products you have purchased and received');
    }
    
    // Check if user has already rated this product
    $check_sql = "SELECT id, rating FROM ratings WHERE user_id = ? AND product_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception('Failed to prepare check query: ' . $conn->error);
    }
    
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    $is_update = false;
    if ($result->num_rows > 0) {
        // Update existing rating
        $existing_rating = $result->fetch_assoc();
        $rating_id = $existing_rating['id'];
        
        $update_sql = "UPDATE ratings SET rating = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception('Failed to prepare update query: ' . $conn->error);
        }
        
        $update_stmt->bind_param("ii", $rating, $rating_id);
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update rating: ' . $update_stmt->error);
        }
        $update_stmt->close();
        $is_update = true;
    } else {
        // Insert new rating
        $insert_sql = "INSERT INTO ratings (user_id, product_id, rating, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            throw new Exception('Failed to prepare insert query: ' . $conn->error);
        }
        
        $insert_stmt->bind_param("iii", $user_id, $product_id, $rating);
        if (!$insert_stmt->execute()) {
            throw new Exception('Failed to insert rating: ' . $insert_stmt->error);
        }
        $insert_stmt->close();
    }
    
    $check_stmt->close();
    
    // Calculate new average rating
    $avg_sql = "
        SELECT 
            AVG(rating) as avg_rating,
            COUNT(*) as total_ratings
        FROM ratings 
        WHERE product_id = ?
    ";
    $avg_stmt = $conn->prepare($avg_sql);
    if (!$avg_stmt) {
        throw new Exception('Failed to prepare average query: ' . $conn->error);
    }
    
    $avg_stmt->bind_param("i", $product_id);
    $avg_stmt->execute();
    $avg_result = $avg_stmt->get_result();
    $avg_data = $avg_result->fetch_assoc();
    $avg_stmt->close();
    
    $avg_rating = $avg_data['avg_rating'];
    $total_ratings = $avg_data['total_ratings'];
    
    // Update product table with new average rating
    $update_product_sql = "
        UPDATE products 
        SET average_rating = ?, 
            total_ratings = ?,
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ";
    $update_product_stmt = $conn->prepare($update_product_sql);
    if (!$update_product_stmt) {
        throw new Exception('Failed to prepare product update query: ' . $conn->error);
    }
    
    $update_product_stmt->bind_param("dii", $avg_rating, $total_ratings, $product_id);
    if (!$update_product_stmt->execute()) {
        throw new Exception('Failed to update product: ' . $update_product_stmt->error);
    }
    $update_product_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Send success response
    sendResponse(true, $is_update ? 'Rating updated successfully' : 'Rating saved successfully', [
        'rating' => $rating,
        'average_rating' => round($avg_rating, 2),
        'total_ratings' => $total_ratings,
        'is_update' => $is_update
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Log the error
    error_log('Rating save error: ' . $e->getMessage());
    
    // Send error response with more details for debugging
    sendResponse(false, 'Error: ' . $e->getMessage());
}
?>