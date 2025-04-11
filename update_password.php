<?php
session_start();
include "includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['update_password'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['currentPassword'];
    $new_password = $_POST['newPassword'];
    $confirm_password = $_POST['confirmPassword'];
    
    // Validate passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match.";
        header("Location: account.php");
        exit();
    }
    
    try {
        // Use the existing connection from db.php
        
        // First verify current password
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            if (password_verify($current_password, $row['password'])) {
                // Current password is correct, update to new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_query = "UPDATE users SET password = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                $_SESSION['message'] = "Password updated successfully!";
            } else {
                $_SESSION['error'] = "Current password is incorrect.";
            }
        }
        
        $stmt->close();
        
        header("Location: account.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating password: " . $e->getMessage();
        header("Location: account.php");
        exit();
    }
}

// If not a POST request, redirect to account page
header("Location: account.php");
exit();
?>