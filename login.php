<?php
session_start(); // Start session for login persistence
include "includes/db.php"; // Include database connection

$error_message = ""; // Initialize error message variable

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Both fields are required.";
    } else {
        try {
            // Remove the getConnection function from here
            
            // Check if email exists
            $query = "SELECT id, password, role, full_name FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $row['password'])) {
                    // Store user information in session
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_role'] = $row['role'];
                    $_SESSION['user_name'] = $row['full_name'];
                    
                    // Redirect based on role
                    if ($row['role'] === 'admin') {
                        header("Location: admin/dashboard.php");
                    } else {
                        header("Location: account.php"); // Change to account.php instead of index.php
                    }
                    exit();
                } else {
                    $error_message = "Incorrect email or password.";
                }
            } else {
                $error_message = "No account found with this email.";
            }

            $stmt->close();
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
    <title>Sweet Treats - Login</title>
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
        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 15px;
        }
        .forgot-password a {
            color: #FF69B4;
            text-decoration: none;
            font-size: 0.8em;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Login Form -->
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

        <form method="post" action="">
            <input type="email" name="email" placeholder="Email address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input type="password" name="password" placeholder="Password" required>

            <div class="forgot-password">
                <a href="forgot-password.php">Forgot password?</a>
            </div>

            <input type="submit" name="login" value="Login" style="background-color: #FF69B4; color: white; border: none; cursor: pointer; width: 100%; padding: 10px;">

            <div class="sign-in-link">
                New customer? <a href="register.php">Register here</a>
            </div>
        </form>
    </div>
</body>
</html>