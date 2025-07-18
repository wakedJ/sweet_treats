<?php
session_start(); // Start session

$developer_mode = isset($_GET['dev_mode']) && $_GET['dev_mode'] == '1';

// Include required files
include "includes/db.php";
include "includes/cart_functions.php"; // Include cart functions
// Fix the path to email_functions.php - ensure it's properly included
if (file_exists("includes/email_functions.php")) {
    include "includes/email_functions.php"; // Include our email functions file
} else {
    error_log("Email functions file not found at: includes/email_functions.php");
}

// Add email system test if in developer mode
if ($developer_mode && isset($_GET['test_email'])) {
    // Make sure email_functions are loaded before testing
    if (!function_exists('testEmailConnection')) {
        die("Error: Email functions are not properly loaded. Check file paths.");
    }
    
    $test_result = testEmailConnection();
    echo "<div style='background: #f8f9fa; padding: 20px; margin: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h2>Email System Test Results</h2>";
    echo "<p>Success: " . ($test_result['success'] ? 'Yes' : 'No') . "</p>";
    echo "<p>Message: " . htmlspecialchars($test_result['message']) . "</p>";
    echo "<p>API Info: " . htmlspecialchars($test_result['api_info']) . "</p>";
    echo "<p>Sender: " . htmlspecialchars($test_result['sender']) . "</p>";
    echo "</div>";
    
    if (!$test_result['success']) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h3>Troubleshooting Tips</h3>";
        echo "<ul>";
        echo "<li>Check if your API key is complete and correct</li>";
        echo "<li>Verify that your sender email is verified in Brevo</li>";
        echo "<li>Check server error logs for more details</li>";
        echo "<li>Make sure your server can make outbound HTTPS connections</li>";
        echo "</ul>";
        echo "</div>";
    }
}

if (!file_exists('vendor/autoload.php')) {
    die("Error: PHPMailer is not installed. Please run 'composer require phpmailer/phpmailer'");
}

// Clean up expired temporary users
$cleanup_query = "DELETE FROM temp_users WHERE expiry_datetime < NOW()";
$conn->query($cleanup_query);

// Initialize error message variable
$error_message = "";
$success_message = "";
$registration_successful = false;

// Check if form was submitted
if (isset($_POST['register'])) {
    // Get form data - Split full name into first and last name
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone_number = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($phone_number) || empty($email) || empty($password)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if database connection is valid
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }

            // Check if email already exists in users table
            $check_query = "SELECT id FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_query);
            if (!$check_stmt) {
                throw new Exception("Prepare failed for users check: " . $conn->error);
            }
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $user_exists = ($result->num_rows > 0);
            $check_stmt->close();
            
            // Check if email already exists in temp_users table
            $check_temp_query = "SELECT temp_user_id FROM temp_users WHERE email = ?";
            $check_temp_stmt = $conn->prepare($check_temp_query);
            if (!$check_temp_stmt) {
                throw new Exception("Prepare failed for temp_users check: " . $conn->error);
            }
            $check_temp_stmt->bind_param("s", $email);
            $check_temp_stmt->execute();
            $temp_result = $check_temp_stmt->get_result();
            $temp_user_exists = ($temp_result->num_rows > 0);
            $check_temp_stmt->close();
            
            if ($user_exists || $temp_user_exists) {
                // If the user already exists, provide more specific error message
                if ($user_exists) {
                    $error_message = "This email address is already registered as a full user.";
                } else {
                    // Delete the existing temporary user record to allow re-registration
                    $delete_temp_query = "DELETE FROM temp_users WHERE email = ?";
                    $delete_temp_stmt = $conn->prepare($delete_temp_query);
                    $delete_temp_stmt->bind_param("s", $email);
                    $delete_temp_stmt->execute();
                    $delete_temp_stmt->close();
                    
                    // Continue with new registration since we deleted the old temp record
                    $temp_user_exists = false;
                }
            }
            
            // Only proceed if the email doesn't exist in either table
            if (!$user_exists && !$temp_user_exists) {
                // Generate 6-digit verification code instead of token
                $verification_code = generateVerificationCode();
                
                // Set expiry to 30 minutes from now (for verification code)
                $expiry_datetime = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Store verification code in database instead of token
                $insert_query = "INSERT INTO temp_users (email, first_name, last_name, password, verification_token, expiry_datetime, phone_number, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_query);
                
                if (!$insert_stmt) {
                    throw new Exception("Prepare failed for insert: " . $conn->error);
                }
                
                // Use verification_code in place of verification_token
                $insert_stmt->bind_param("sssssss", $email, $first_name, $last_name, $hashed_password, $verification_code, $expiry_datetime, $phone_number);
                
                if ($insert_stmt->execute()) {
                    $registration_successful = true;
                    $user_id = $insert_stmt->insert_id;
                    
                    // Store email and code in session for verification process
                    $_SESSION['verification_email'] = $email;
                    $_SESSION['verification_code_sent'] = true;
                    
                    // Check if sendVerificationEmail function exists
                    if (!function_exists('sendVerificationEmail')) {
                        error_log("sendVerificationEmail function not found - check if email_functions.php is properly included");
                        $success_message = "Registration successful! However, email verification is not available. Your verification code is: <strong>" . $verification_code . "</strong>";
                    } else {
                        // Send verification email using our function - now with code instead of URL
                        try {
                            $full_name = $first_name . ' ' . $last_name; // Combine for email purposes
                            $emailSent = sendVerificationEmail($email, $full_name, $verification_code);
                            
                            if ($emailSent) {
                                $success_message = "Registration successful! We've sent a 6-digit verification code to your email address.<br><br>";
                                $success_message .= "<div style='background: #e8f4fd; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                                $success_message .= "<strong>Next Step:</strong> Check your email and <a href='verify_code.php' style='color: #0c5460; font-weight: bold;'>click here to enter your verification code</a>.";
                                $success_message .= "</div>";
                                $success_message .= "<small>The code will expire in 30 minutes. If you don't receive the email, check your spam folder.</small>";
                            } else {
                                $success_message = "Registration successful! However, there was an issue sending the verification email. Your verification code is: <strong>" . $verification_code . "</strong><br>";
                                $success_message .= "<a href='verify_code.php'>Click here to enter your verification code</a>";
                                error_log("Failed to send verification email to: " . $email);
                            }
                            
                            // Transfer any items in session cart to the new temp user
                            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                                $transfer_result = transfer_session_cart_to_db($conn, $user_id, 'temp_user');
                                
                                if (!$transfer_result) {
                                    // Log error but don't show to user
                                    error_log("Failed to transfer cart for new temp user ID: $user_id");
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Email Exception: " . $e->getMessage());
                            $success_message = "Registration successful! However, verification email could not be sent. Your verification code is: <strong>" . $verification_code . "</strong><br>";
                            $success_message .= "<a href='verify_code.php'>Click here to enter your verification code</a>";
                        }
                    }
                } else {
                    $error_message = "Registration failed: " . $insert_stmt->error;
                }
                
                $insert_stmt->close();
            }
        } catch (Exception $e) {
            $error_message = "System error: " . $e->getMessage();
        }
    }
}

// Function to check if Brevo is properly configured
function checkBrevoConfig() {
    if (!defined('BREVO_API_KEY') || !defined('BREVO_SENDER_EMAIL')) {
        return false;
    }
    
    // Check if API key looks valid (should start with "xkeysib-")
    if (!preg_match('/^xkeysib-[a-zA-Z0-9-]+$/', BREVO_API_KEY) || 
        BREVO_API_KEY == 'xkeysib-your-full-brevo-api-key-goes-here') {
        return false;
    }
    
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sweet Treats</title>
    <!-- Font Awesome CDN for ice cream icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <!-- Registration Form -->
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
        <?php else: ?>
            <form method="post" action="">
                <div class="name-fields">
                    <input type="text" name="first_name" placeholder="First name" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    <input type="text" name="last_name" placeholder="Last name" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                </div>
                <input type="tel" name="phone_number" placeholder="Phone number" required value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                <input type="email" name="email" placeholder="Email address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm password" required>
                <input type="submit" name="register" value="Register">
                
                <div class="sign-in-link">
                    Already a customer? <a href="login.php">Sign in here</a>
                </div>
            </form>
            
            <?php if ($developer_mode): ?>
                <div class="dev-info">
                    <p><strong>Developer Info:</strong></p>
                    <p>Email Functions Loaded: <?php echo function_exists('sendVerificationEmail') ? 'Yes' : 'No'; ?></p>
                    <p>Code Generation Function: <?php echo function_exists('generateVerificationCode') ? 'Yes' : 'No'; ?></p>
                    <p>Brevo Properly Configured: <?php echo checkBrevoConfig() ? 'Yes' : 'No'; ?></p>
                    <p><a href="?dev_mode=1&test_email=1">Run Email Test</a></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="home-link">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>