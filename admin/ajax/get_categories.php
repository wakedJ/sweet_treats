<?php
// This is a file to handle category fetching
// Place this in your ajax/ directory with the name get_categories.php

// Pure AJAX endpoint that only returns JSON
header('Content-Type: application/json');

// Turn off display errors to prevent HTML error messages in JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

try {
    // FIXED: Adjusted path to your DB connection file
    // Based on your file location: C:\xampp\htdocs\sweet_treats\admin\ajax\get_categories.php
    require_once '../../includes/db.php'; // Going up two levels from admin/ajax to the root
    
    // Alternative paths to try if the above doesn't work:
    // require_once '../includes/db.php'; // If includes is directly under admin
    // require_once 'C:/xampp/htdocs/sweet_treats/includes/db.php'; // Absolute path
    
    // Initialize session if needed
    session_start();
    
    // Verify connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? "Connection variable not defined"));
    }
    
    // Get active categories
    $sql = "SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => $row['id'],
            'name' => htmlspecialchars($row['name'])
        ];
    }
    
    $response = [
        'success' => true,
        'message' => 'Categories retrieved successfully',
        'categories' => $categories
    ];
    
} catch (Exception $e) {
    error_log('Error in get_categories: ' . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Error retrieving categories: ' . $e->getMessage()
    ];
}

// Return JSON response
echo json_encode($response);
exit;
?>  