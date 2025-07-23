<?php
// Process profile update form if submitted
if (isset($_POST['update_profile'])) {
    try {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $phone_number = $_POST['phone_number'];
        
        // Update user information
        $query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone_number, $user_id);
        $stmt->execute();
        
        // Update session data
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        
        $_SESSION['message'] = "Profile updated successfully!";
        header("Location: account.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
    }
}

// Process password update form if submitted
if (isset($_POST['update_password'])) {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify passwords match
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New passwords do not match.";
            header("Location: account.php");
            exit();
        }
        
        // Verify current password
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (!password_verify($current_password, $user['password'])) {
                $_SESSION['error'] = "Current password is incorrect.";
                header("Location: account.php");
                exit();
            }
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
        
        $_SESSION['message'] = "Password updated successfully!";
        header("Location: account.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating password: " . $e->getMessage();
    }
}
?>