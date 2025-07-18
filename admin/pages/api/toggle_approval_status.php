<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try different paths for the includes
$db_paths = [
    '../../../includes/db.php',     // If db.php is in root/includes/
    '../../includes/db.php',        // If db.php is in admin/includes/
    '../../../config/db.php',       // Alternative location
    '../../../includes/database.php', // Alternative name
];

$db_found = false;
foreach ($db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_found = true;
        break;
    }
}

if (!$db_found) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Database configuration file not found. Checked paths: ' . implode(', ', $db_paths)
    ]);
    exit;
}

// Try different paths for admin check
$admin_check_paths = [
    '../includes/check_admin.php',   // If in admin/includes/
    '../../includes/check_admin.php', // If in root/includes/
    '../check_admin.php',            // If in admin/pages/
];

$admin_check_found = false;
foreach ($admin_check_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $admin_check_found = true;
        break;
    }
}

if (!$admin_check_found) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Admin check file not found. Checked paths: ' . implode(', ', $admin_check_paths)
    ]);
    exit;
}

// Set header to return JSON
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if JSON decode was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, 'Invalid JSON data: ' . json_last_error_msg());
}

// Validate input
if (!isset($data['review_id']) || !isset($data['status'])) {
    sendResponse(false, 'Missing required parameters');
}

$reviewId = intval($data['review_id']);
$status = trim($data['status']);

// Validate review ID
if ($reviewId <= 0) {
    sendResponse(false, 'Invalid review ID');
}

// Validate status
if ($status !== 'approved' && $status !== 'hidden') {
    sendResponse(false, 'Invalid status value. Must be "approved" or "hidden"');
}

try {
    // Check if database connection exists
    if (!isset($conn) || $conn->connect_error) {
        sendResponse(false, 'Database connection failed');
    }

    // First, check if the review exists
    $checkStmt = $conn->prepare("SELECT id, approval_status FROM reviews WHERE id = ?");
    if (!$checkStmt) {
        sendResponse(false, 'Failed to prepare check statement: ' . $conn->error);
    }
    
    $checkStmt->bind_param("i", $reviewId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        $checkStmt->close();
        sendResponse(false, 'Review not found');
    }
    
    $review = $result->fetch_assoc();
    $checkStmt->close();
    
    // Check if the status is actually changing
    if ($review['approval_status'] === $status) {
        sendResponse(true, 'Review status is already ' . $status);
    }

    // Update the review status (removed updated_at column)
    $updateStmt = $conn->prepare("UPDATE reviews SET approval_status = ? WHERE id = ?");
    if (!$updateStmt) {
        sendResponse(false, 'Failed to prepare update statement: ' . $conn->error);
    }
    
    $updateStmt->bind_param("si", $status, $reviewId);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            $updateStmt->close();
            sendResponse(true, 'Review status updated successfully to ' . $status);
        } else {
            $updateStmt->close();
            sendResponse(false, 'No rows were updated. Review may not exist or status unchanged.');
        }
    } else {
        $error = $updateStmt->error;
        $updateStmt->close();
        sendResponse(false, 'Database update error: ' . $error);
    }

} catch (Exception $e) {
    sendResponse(false, 'System error: ' . $e->getMessage());
} finally {
    // Close database connection if it exists
    if (isset($conn)) {
        $conn->close();
    }
}
?>