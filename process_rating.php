<?php
// Prevent direct access
if (!defined('SITE_ROOT')) {
    exit('Direct script access denied.');
}

// Include database connection
require_once 'includes/db_connection.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

// Validate input
if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID or rating']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Check if the user has already rated this product
    $check_stmt = $conn->prepare("SELECT id FROM ratings WHERE user_id = ? AND product_id = ?");
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing rating
        $row = $result->fetch_assoc();
        $rating_id = $row['id'];
        
        $update_stmt = $conn->prepare("UPDATE ratings SET rating = ?, created_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("ii", $rating, $rating_id);
        $success = $update_stmt->execute();
        
        if (!$success) {
            throw new Exception("Failed to update rating");
        }
    } else {
        // Insert new rating
        $insert_stmt = $conn->prepare("INSERT INTO ratings (user_id, product_id, rating, created_at) VALUES (?, ?, ?, NOW())");
        $insert_stmt->bind_param("iii", $user_id, $product_id, $rating);
        $success = $insert_stmt->execute();
        
        if (!$success) {
            throw new Exception("Failed to insert rating");
        }
    }
    
    // Update product average rating in products table (if you have such a field)
    update_product_average_rating($product_id);
    
    // Return success response
    echo json_encode(['success' => true, 'message' => 'Rating saved successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}

/**
 * Helper function to update the product's average rating
 * 
 * @param int $product_id The ID of the product
 * @return void
 */
function update_product_average_rating($product_id) {
    global $conn;
    
    // Calculate the average rating for this product
    $avg_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM ratings WHERE product_id = ?");
    $avg_stmt->bind_param("i", $product_id);
    $avg_stmt->execute();
    $avg_result = $avg_stmt->get_result();
    $avg_data = $avg_result->fetch_assoc();
    $avg_rating = round($avg_data['avg_rating'], 1);
    
    // Update the product table if you have a rating field there
    $update_stmt = $conn->prepare("UPDATE products SET average_rating = ? WHERE id = ?");
    $update_stmt->bind_param("di", $avg_rating, $product_id);
    $update_stmt->execute();
}