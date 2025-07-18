<?php
// Pure AJAX endpoint that only returns JSON
header('Content-Type: application/json');

// Turn off display errors to prevent HTML error messages in JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Initialize response variable
$response = ['success' => false, 'message' => 'Invalid request method'];

// Process the form submission if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Include your database connection file
        require_once '../../includes/db.php';
        
        // Get and sanitize input values
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1; // Default to active (1)
        
        // Validate required fields
        if (empty($name)) {
            throw new Exception("Category name is required");
        }
        
        // Check if category name already exists
        $check_sql = "SELECT id FROM categories WHERE name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception("A category with this name already exists");
        }
        $check_stmt->close();
        
        // Insert new category
        $sql = "INSERT INTO categories (name, description, status) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $description, $status);
        
        if ($stmt->execute()) {
            $category_id = $conn->insert_id;
            $response = [
                'success' => true,
                'message' => 'Category added successfully!',
                'category_id' => $category_id
            ];
        } else {
            error_log("Database error in add-category.php: " . $stmt->error);
            throw new Exception("Database error: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Return JSON response
echo json_encode($response);
exit;