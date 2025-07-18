<?php
session_start();
include "includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php?redirect=" . urlencode($_SERVER['HTTP_REFERER']) . "#review-section");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if the user has made any purchases
$checkPurchase = $conn->prepare("SELECT COUNT(*) as count FROM orders 
                                WHERE user_id = ? AND status = 'completed'");
$checkPurchase->bind_param("i", $user_id);
$checkPurchase->execute();
$result = $checkPurchase->get_result();
$orderCount = $result->fetch_assoc()['count'];

if ($orderCount === 0) {
    // User hasn't made any purchases, redirect back with error message
    $_SESSION['review_error'] = "You need to make a purchase before leaving a review.";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    if (empty($_POST['subject']) || empty($_POST['message'])) {
        $_SESSION['review_error'] = "Both subject and message are required.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Check length constraints
    if (strlen($subject) > 50) {
        $_SESSION['review_error'] = "Subject must be 50 characters or less.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    if (strlen($message) > 500) {
        $_SESSION['review_error'] = "Message must be 500 characters or less.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    // Determine approval status (you can change this logic as needed)
    // For example, you might auto-approve reviews from users with more than 5 completed orders
    $approvalStatus = "hidden"; // Default to pending
    
    if ($orderCount > 5) {
        // Auto-approve for loyal customers
        $approvalStatus = "approved";
    }
    
    // Insert the review into the database
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, subject, message, approval_status, submitted_at) 
                          VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user_id, $subject, $message, $approvalStatus);
    
    if ($stmt->execute()) {
        // Success
        $_SESSION['review_success'] = ($approvalStatus === "approved") 
            ? "Thank you! Your review has been posted." 
            : "Thank you! Your review has been submitted and is awaiting approval.";
    } else {
        // Error
        $_SESSION['review_error'] = "Sorry, there was a problem submitting your review. Please try again.";
    }
    
    $stmt->close();
} else {
    // Form was not submitted properly
    $_SESSION['review_error'] = "Invalid form submission.";
}

// Redirect back to the previous page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>