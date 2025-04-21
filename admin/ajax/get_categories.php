<?php
// Pure AJAX endpoint that only returns JSON
header('Content-Type: application/json');

// Turn off display errors to prevent HTML error messages in JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Initialize response variable
$response = ['success' => false, 'message' => 'An error occurred', 'categories' => []];

try {
    // Include your database connection file
    require_once '../../includes/db.php';
    
    // Query to get all active parent categories
    $sql = "SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if ($result) {
        $categories = [];
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        
        $response = [
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'categories' => $categories
        ];
    } else {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'categories' => []
    ];
}

// Return JSON response
echo json_encode($response);
exit;