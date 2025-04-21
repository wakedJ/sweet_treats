<?php
// Include your database connection
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    // Query to get all active categories
    $sql = "SELECT id, name FROM categories WHERE status = 1 ORDER BY name";
    $result = $conn->query($sql);
    
    $categories = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Close connection
$conn->close();
?>