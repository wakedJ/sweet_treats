<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection and admin check
require_once '../includes/db.php';
require_once './includes/check_admin.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only GET requests are allowed.'
    ]);
    exit;
}

// Check if review ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Review ID is required.'
    ]);
    exit;
}

$reviewId = intval($_GET['id']);

// Validate review ID
if ($reviewId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid review ID.'
    ]);
    exit;
}

try {
    // Prepare and execute query to get review details with user information
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.user_id,
            r.subject,
            r.message,
            r.submitted_at,
            r.is_read,
            r.approval_status,
            u.first_name,
            u.last_name,
            u.email,
            u.phone
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $reviewId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Review not found.'
        ]);
        exit;
    }
    
    $review = $result->fetch_assoc();
    
    // Format the data for response
    $reviewDetails = [
        'id' => (int)$review['id'],
        'user_id' => $review['user_id'] ? (int)$review['user_id'] : null,
        'subject' => $review['subject'],
        'message' => $review['message'],
        'submitted_at' => $review['submitted_at'],
        'submitted_at_formatted' => date('F j, Y \a\t g:i A', strtotime($review['submitted_at'])),
        'is_read' => (bool)$review['is_read'],
        'approval_status' => $review['approval_status'],
        'customer' => [
            'name' => trim(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? '')),
            'first_name' => $review['first_name'],
            'last_name' => $review['last_name'],
            'email' => $review['email'],
            'phone' => $review['phone']
        ]
    ];
    
    // Mark review as read if it wasn't already
    if (!$review['is_read']) {
        $updateStmt = $conn->prepare("UPDATE reviews SET is_read = 1 WHERE id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param("i", $reviewId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Update the response to reflect the change
            $reviewDetails['is_read'] = true;
        }
    }
    
    $stmt->close();
    
    // Return success response with review details
    echo json_encode([
        'success' => true,
        'message' => 'Review details retrieved successfully.',
        'data' => $reviewDetails
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_review_details.php: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving review details.',
        'error' => $e->getMessage() // Remove this in production
    ]);
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?> 