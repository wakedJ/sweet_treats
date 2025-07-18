<?php
// Include database connection
require_once '../../includes/db.php'; 

// Set proper content type for JSON response
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly, but capture them

try {
    // Check if data is provided
    if (!isset($_POST['message_id']) || !isset($_POST['status'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required parameters'
        ]);
        exit;
    }
    
    $message_id = (int)$_POST['message_id'];
    $status = (int)$_POST['status'];
    
    // Validate status is either 0 or 1
    if ($status !== 0 && $status !== 1) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid status value'
        ]);
        exit;
    }
    
    // Log the values for debugging
    error_log("Updating message ID: {$message_id} to status: {$status}");
    
    // Check if the message exists
    $check_query = "SELECT message_id FROM messages WHERE message_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $message_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Message not found'
        ]);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
    
    // Update message read status
    $query = "UPDATE messages SET is_read = ? WHERE message_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $status, $message_id);
    
    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Message status updated',
                'affected_rows' => $stmt->affected_rows
            ]);
        } else {
            // No rows were updated (perhaps the status was already set)
            echo json_encode([
                'status' => 'success',
                'message' => 'No changes needed, status already set',
                'affected_rows' => 0
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'SQL error: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'System error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>