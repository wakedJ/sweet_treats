<?php
session_start();
include "includes/db.php";

$error_message = "";
$success_message = "";
$valid_token = false;
$token = $_GET['token'] ?? '';

try {
    // Get database connection
    $conn = getConnection();
    
    if (!empty($token)) {
        // Verify token
        $query = "SELECT email, expires FROM password_resets WHERE token = ? AND expires > NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $email = $row['email'];
            $valid_token = true;
        } else {
            $error_message = "Invalid or expired reset link.";
        }
        $stmt->close();
    } else {
        $error_message = "Invalid reset link.";
    }
    
    // Handle password reset form submission
    if ($valid_token && isset($_POST['update_password'])) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($password) || empty($confirm_password)) {
            $error_message = "Both fields are required.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            // Update password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE email = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ss", $hashed_password, $email);
            
            if ($update_stmt->execute()) {
                // Delete used token
                $delete_query = "DELETE FROM password_resets WHERE token = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("s", $token);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                $success_message = "Password has been reset successfully. You can now <a href='login.php'>login</a> with your new password.";
                $valid_token = false; // Hide the form
            } else {
                $error_message = "Failed to update password. Please try again.";
            }
            $update_stmt->close();
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    $error_message = "System error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Treats - Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #FFF0F5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 350px;
            padding: 30px;
        }
        .logo {
            color: #FF69B4;
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .logo i {
            color: #FF69B4;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
            color: #333;
        }
        .login-link a {
            color: #FF69B4;
            text-decoration: none;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
        .success-message {
            color: green;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            SWEET TREATS 
            <i class="fas fa-ice-cream"></i>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($valid_token): ?>
            <form method="post" action="">
                <input type="password" name="password" placeholder="New Password (min 6 characters)" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                <input type="submit" name="update_password" value="Update Password" style="background-color: #FF69B4; color: white; border: none; cursor: pointer; width: 100%; padding: 10px;">
            </form>
        <?php elseif (empty($success_message)): ?>
            <div class="login-link">
                <a href="login.php">Return to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>