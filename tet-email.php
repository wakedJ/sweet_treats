<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "Testing PHPMailer installation...<br>";

// Check if classes exist
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    die("PHPMailer class not found. Please check your installation.");
}

echo "PHPMailer class found!<br>";

// Create a simple test message
$mail = new PHPMailer(true);
try {
    // Enable verbose debug output
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'lanawaked237@gmail.com';
    $mail->Password   = 'nkuu slol muzq wmun'; 
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    
    // Test recipient - use your email
    $mail->setFrom('lanawaked237@gmail.com', 'Test Sender');
    $mail->addAddress('your-email@example.com', 'Test Recipient');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test';
    $mail->Body    = 'This is a test email to verify PHPMailer is working properly.';
    
    $mail->send();
    echo "Test email has been sent!";
} catch (Exception $e) {
    echo "Test failed: " . $mail->ErrorInfo;
}