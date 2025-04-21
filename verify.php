<?php
include "includes/db.php";

$error_message = "";
$success_message = "";

// Check if token is provided in the URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Check if the token exists and is not expired
        $current_time = date('Y-m-d H:i:s');
        $check_query = "SELECT * FROM temp_users WHERE verification_token = ? AND expiry_datetime > ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $token, $current_time);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Token is valid and not expired
            $temp_user = $result->fetch_assoc();
            
            // Begin transaction to ensure data consistency
            $conn->begin_transaction();
            
            try {
                // Insert into users table
                $insert_query = "INSERT INTO users (full_name, email, password, phone_number, verification_token, verification_token_expires, is_verified) 
                                VALUES (?, ?, ?, ?, NULL, NULL, 1)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("ssss", $temp_user['full_name'], $temp_user['email'], $temp_user['password'], $temp_user['phone_number']);
                $insert_stmt->execute();
                
                // Delete from temp_users table
                $delete_query = "DELETE FROM temp_users WHERE verification_token = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("s", $token);
                $delete_stmt->execute();
                
                // Commit the transaction
                $conn->commit();
                
                $success_message = "Your account has been successfully verified! You can now <a href='login.php'>login</a>.";
                
            } catch (Exception $e) {
                // An error occurred, rollback the transaction
                $conn->rollback();
                $error_message = "An error occurred during verification. Please try again.";
                error_log("Verification Error: " . $e->getMessage());
            }
            
        } else {
            // Token is invalid or expired
            $error_message = "Invalid or expired verification link. Please register again.";
        }
        
    } catch (Exception $e) {
        $error_message = "System error: " . $e->getMessage();
    }
    
} else {
    $error_message = "Invalid verification link.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification - Sweet Treats</title>
    <!-- Font Awesome CDN for ice cream icon -->
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
            text-align: center;
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
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        a {
            color: #FF69B4;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .button {
            display: inline-block;
            background-color: #FF69B4;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            SWEET TREATS 
            <i class="fas fa-ice-cream"></i>
        </div>
        
        <h2>Account Verification</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
            <p>If you need to register again, <a href="register.php">click here</a>.</p>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
            <a href="login.php" class="button">Go to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>