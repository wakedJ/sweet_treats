<?php
// ajax/send-reply.php
// Ensure proper error handling
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Set the content type to JSON
header('Content-Type: application/json');

try {
    // Include database connection with the correct path
    require_once '../includes/db.php';
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Check if all required fields are provided
    if (!isset($_POST['message_id']) || !isset($_POST['email']) || 
        !isset($_POST['subject']) || !isset($_POST['content'])) {
        throw new Exception('Missing required fields');
    }

    $message_id = (int)$_POST['message_id'];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject = trim($_POST['subject']);
    $content = trim($_POST['content']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Validate other fields
    if (empty($subject) || empty($content)) {
        throw new Exception('Subject and content cannot be empty');
    }

    // Set up email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: SweetTreats <no-reply@example.com>" . "\r\n";

    // Build email content
    $email_content = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8c5d6; padding: 15px; text-align: center; }
            .content { padding: 20px; border: 1px solid #ddd; }
            .footer { text-align: center; padding: 10px; font-size: 0.8em; color: #888; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>SweetTreats Response</h2>
            </div>
            <div class="content">
                <p>Dear Customer,</p>
                <p>' . nl2br(htmlspecialchars($content)) . '</p>
                <p>Best regards,<br>SweetTreats Customer Support</p>
            </div>
            <div class="footer">
                <p>This is an automated response. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>';

    // Attempt to send the email
    $mail_sent = mail($email, $subject, $email_content, $headers);
    
    if (!$mail_sent) {
        throw new Exception('Failed to send email');
    }
    
    // Mark message as read in database
    $query = "UPDATE messages SET is_read = 1 WHERE message_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $message_id);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('Database update failed: ' . $stmt->error);
    }

    // Clean any output that might have been generated
    ob_end_clean();
    
    // Return success
    echo json_encode([
        'status' => 'success',
        'message' => 'Reply sent successfully'
    ]);
    
} catch (Exception $e) {
    // Clean any output that might have been generated
    ob_end_clean();
    
    // Log error to server log
    error_log("Reply error: " . $e->getMessage());
    
    // Return error with details
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>