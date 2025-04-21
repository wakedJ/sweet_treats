if ($insert_stmt->execute()) {
    // Email sending code remains the same
    
    // But ALSO show the verification link on the page for development
    $success_message = "Registration successful! An email has been sent to your inbox.";
    
    // For development only - show the verification link
    if (true) { // Set to false in production
        $success_message .= "<br><br><strong>Development Mode:</strong> 
                            <a href='verify.php?token=" . $verification_token . "' 
                            style='color:#FF69B4;'>Click here to verify your account</a>";
    }
}