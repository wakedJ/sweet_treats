<?php
include "includes/db.php";

// Initialize error message variable
$error_message = "";

// Check if form was submitted
if (isset($_POST['register'])) {
    // Get form data
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    if (empty($full_name) || empty($phone_number) || empty($email) || empty($password)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if email already exists
            $check_query = "SELECT id FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Email address already registered.";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user data
                $insert_query = "INSERT INTO users (full_name, email, password, phone_number) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("ssss", $full_name, $email, $hashed_password, $phone_number);
                
                if ($insert_stmt->execute()) {
                    // Redirect to homepage with success message
                    header("Location: index.php?success=registered");
                    exit();
                } else {
                    $error_message = "Registration failed. Please try again.";
                }
                
                $insert_stmt->close();
            }
            
            $check_stmt->close();
            
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
    <title>Sweet Treats</title>
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
        .sign-in-link {
            text-align: center;
            margin-top: 15px;
            color: #333;
        }
        .sign-in-link a {
            color: #FF69B4;
            text-decoration: none;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Registration Form -->
    <div class="container">
        <div class="logo">
            SWEET TREATS 
            <i class="fas fa-ice-cream"></i>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div style="color: red; margin-bottom: 15px; text-align: center;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <input type="text" name="full_name" placeholder="full name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            <input type="tel" name="phone_number" placeholder="phone number" required value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
            <input type="email" name="email" placeholder="email address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input type="password" name="password" placeholder="password" required>
            <input type="password" name="confirm_password" placeholder="confirm password" required>
            <input type="submit" name="register" value="Register" style="background-color: #FF69B4; color: white; border: none; cursor: pointer;">
            
            <div class="sign-in-link">
                Already a customer? <a href="login.php">Sign in here</a>
            </div>
        </form>
    </div>
</body>
</html>