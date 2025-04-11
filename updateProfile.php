<?php
session_start();
include "includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['update_profile'])) {
    $user_id = $_SESSION['user_id'];
    $full_name = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $birthdate = $_POST['birthdate'];
    
    try {
        // Use the existing connection from db.php
        
        // Update user profile
        $query = "UPDATE users SET full_name = ?, email = ?, phone = ?, birthdate = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $full_name, $email, $phone, $birthdate, $user_id);
        $stmt->execute();
        
        // Update session name if changed
        if ($stmt->affected_rows > 0) {
            $_SESSION['user_name'] = $full_name;
        }
        
        $stmt->close();
        
        // Redirect back with success message
        $_SESSION['message'] = "Profile updated successfully!";
        header("Location: account.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
        header("Location: account.php");
        exit();
    }
}

// If not a POST request, redirect to account page
header("Location: account.php");
exit();
?>