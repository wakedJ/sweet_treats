<?php
session_start();
include "includes/db.php";

$error_message = "";
$success_message = "";

if (isset($_POST['reset'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error_message = "Email address is required.";
    } else {
        try {
            // Get database connection
            $conn = getConnection();

            // Check if email exists
            $query = "SELECT id FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                // Generate token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour
                
                // Create password_resets table if it doesn't exist
                $create_table = "CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $conn->query($create_table);
                
                // Delete any existing tokens for this email
                $delete_query = "DELETE FROM password_resets WHERE email = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("s", $email);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                // Insert new token
                $insert_query = "INSERT INTO password_resets (email, token, expires) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("sss", $email, $token, $expires);
                $insert_stmt->execute();
                $insert_stmt->close();
                
                // In a production environment, you would send an email with the reset link
                // For development, we'll just display the reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
                $success_message = "Password reset link has been sent to your email.<br>For testing: <a href='$reset_link'>Reset Password</a>";
            } else {
                $error_message = "No account found with this email address.";
            }

            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            $error_message = "System error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Treats - Forgot Password</title>
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

        <form method="post" action="">
            <input type="email" name="email" placeholder="Email address" required>
            <input type="submit" name="reset" value="Reset Password" style="background-color: #FF69B4; color: white; border: none; cursor: pointer; width: 100%; padding: 10px;">

            <div class="login-link">
                Remember your password? <a href="login.php">Login here</a>
            </div>
        </form>
    </div>
</body>
</html>