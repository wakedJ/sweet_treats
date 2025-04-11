<?php
session_start();
include "includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['update_preferences'])) {
    $user_id = $_SESSION['user_id'];
    $email_promo = isset($_POST['emailPromo']) ? 1 : 0;
    $special_offers = isset($_POST['specialOffers']) ? 1 : 0;
    
    try {
        // Use the existing connection from db.php
        
        // Update preferences
        $query = "UPDATE users SET email_promo = ?, special_offers = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $email_promo, $special_offers, $user_id);
        $stmt->execute();
        
        $stmt->close();
        
        $_SESSION['message'] = "Communication preferences updated successfully!";
        header("Location: account.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating preferences: " . $e->getMessage();
        header("Location: account.php");
        exit();
    }
}

// If not a POST request, redirect to account page
header("Location: account.php");
exit();
?>