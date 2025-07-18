<?php
// Include database connection
require_once '../../includes/db.php';  // Updated to match your project structure

header('Content-Type: application/json');

try {
    // Check if the message ID is provided
    if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid message ID'
        ]);
        exit;
    }
    
    $message_id = (int)$_GET['id'];
    
    // Use prepared statement to prevent SQL injection
    $query = "SELECT * FROM messages WHERE message_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $message = $result->fetch_assoc();
        
        // Format date
        $date = new DateTime($message['submission_date']);
        $formatted_date = $date->format('F j, Y \a\t g:i a');
        
        // Basic success response
        echo json_encode([
            'status' => 'success',
            'name' => $message['name'],
            'email' => $message['email'],
            'subject' => $message['subject'],
            'message' => nl2br($message['message']),
            'formatted_date' => $formatted_date,
            'is_read' => (bool)$message['is_read']
        ]);
        
        // Update read status
        if (!$message['is_read']) {
            $update_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE message_id = ?");
            $update_stmt->bind_param("i", $message_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Message not found'
        ]);
    }
    
    // Close statement
    if (isset($stmt)) {
        $stmt->close();
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'System error: ' . $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>