<?php
session_start(); // Start session for login persistence

// Check if user is already logged in and redirect accordingly
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        // Admin is already logged in, redirect to admin panel
        $admin_url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/admin/index.php";
        header("Location: $admin_url");
        exit();
    } else {
        // Regular user is already logged in, redirect to account page
        $account_url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/account.php";
        header("Location: $account_url");
        exit();
    }
}

include "includes/db.php"; // Include database connection
require_once 'includes/auth_check.php';
include "includes/cart_functions.php"; // Include cart functions

// For debugging - you can remove this in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check for any session-based error messages (from redirects)
$error_message = "";
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Clear the error after displaying
}
$login_successful = false; // Initialize login status flag

// Save the current cart state before any processing
$guest_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
// Save the redirect URL if it exists
$redirect_to = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    // Get redirect from POST if it exists (from hidden field)
    $redirect_to = isset($_POST['redirect']) ? $_POST['redirect'] : $redirect_to;

    if (empty($email) || empty($password)) {
        $error_message = "Both fields are required.";
    } else {
        try {
            // Check if email exists
            // Updated query to use first_name and last_name instead of full_name
            $query = "SELECT id, password, role, first_name, last_name, status FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                
                // Check if account is active
                if ($row['status'] !== 'active') {
                    $error_message = "This account is not active. Please verify your email or contact support.";
                }
                // Verify password
                else if (password_verify($password, $row['password'])) {
                    // Login successful
                    $login_successful = true;
                    $user_id = $row['id'];
                    
                    // Store user information in session
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_role'] = $row['role'];
                    // Combine first and last name for display purposes
                    $_SESSION['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
                    
                    // Transfer session cart to database
                    $transfer_result = transfer_session_cart_to_db($conn, $user_id);
                    
                    if (!$transfer_result) {
                        // Log error but don't show to user
                        error_log("Failed to transfer cart for user ID: $user_id");
                    }
                    
                    // Make sure to save the user's cart count after transferring cart items
                    $cart_count_query = "SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?";
                    $cart_count_stmt = $conn->prepare($cart_count_query);
                    $cart_count_stmt->bind_param("i", $user_id);
                    $cart_count_stmt->execute();
                    $cart_count_result = $cart_count_stmt->get_result();
                    $cart_row = $cart_count_result->fetch_assoc();
                    $_SESSION['cart_count'] = $cart_row['total'] ?: 0;
                    
                    // Optional: Add debugging
                    // error_log("Login successful. Cart count: " . $_SESSION['cart_count']);
                    
                    // FIXED REDIRECT LOGIC - Use absolute paths
                    if (!empty($redirect_to)) {
                        if ($redirect_to == 'checkout') {
                            header("Location: " . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/checkout.php");
                        } else {
                            // Handle other redirects if needed - make them absolute
                            $redirect_url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/" . $redirect_to;
                            header("Location: $redirect_url");
                        }
                        exit();
                    } else if ($row['role'] === 'admin') {
                        // FIXED: Use absolute path for admin redirect
                        $admin_url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/admin/index.php";
                        header("Location: $admin_url");
                        exit();
                    } else {
                        // FIXED: Use absolute path for regular user redirect
                        $account_url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/account.php";
                        header("Location: $account_url");
                        exit();
                    }
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

// For debugging - you can remove in production
$session_debug = "";
if (isset($_SESSION['cart'])) {
    $session_debug .= "Cart has " . count($_SESSION['cart']) . " items. ";
}
if (isset($_SESSION['user_id'])) {
    $session_debug .= "User ID: " . $_SESSION['user_id'] . ". ";
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
    <link rel="stylesheet" href="css/login.css">
    <script>
    // Function to pass the email to forgot password link
    function updateForgotPasswordLink() {
        var emailInput = document.getElementById('email-input');
        var forgotPasswordLink = document.getElementById('forgot-password-link');
        
        if (emailInput.value.trim() !== '') {
            forgotPasswordLink.href = 'forgot-password.php?email=' + encodeURIComponent(emailInput.value.trim());
        } else {
            forgotPasswordLink.href = 'forgot-password.php';
        }
    }
    </script>
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
            <input 
                type="email" 
                id="email-input" 
                name="email" 
                placeholder="Email address" 
                required 
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                onchange="updateForgotPasswordLink()" 
                onkeyup="updateForgotPasswordLink()" 
            >
            <input type="password" name="password" placeholder="Password" required>
            
            <?php if (!empty($redirect_to)): ?>
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_to); ?>">
            <?php endif; ?>

            <div class="forgot-password">
                <a href="forgot-password.php" id="forgot-password-link">Forgot password?</a>
            </div>

            <input type="submit" name="login" value="Login" style="background-color: #FF69B4; color: white; border: none; cursor: pointer; width: 100%; padding: 10px;">

            <div class="sign-in-link">
                New customer? <a href="register.php">Register here</a>
            </div>
            
            <?php if (!empty($session_debug)): ?>
            <div class="debug-info">
                Debug: <?php echo $session_debug; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <script>
    // Initialize the forgot password link when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        updateForgotPasswordLink();
    });
    </script>
</body>
</html>