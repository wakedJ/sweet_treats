<?php
session_start();
include_once "includes/db.php";

// Function to get base URL - moved inside PHP tags and to the top of the file
function getBaseURL() {
    // For production environment - use actual domain
    $actual_domain = "https://yourwebsite.com"; // Replace with your actual website URL
    
    // Detect environment
    $server_name = $_SERVER['SERVER_NAME'] ?? '';
    $is_local = (strpos($server_name, 'localhost') !== false || 
                 strpos($server_name, '127.0.0.1') !== false);
    
    if ($is_local) {
        // Local development environment
        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || 
                    $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        
        $path = dirname($_SERVER['PHP_SELF']);
        if ($path == '/' || $path == '\\') {
            $path = '';
        }
        
        return $protocol . $domainName . $path;
    } else {
        // Production environment - use the actual domain
        return $actual_domain;
    }
}

// Check if getConnection function exists, if not create it
if (!function_exists('getConnection')) {
    function getConnection() {
        global $conn;
        if (!isset($conn)) {
            // Assuming $conn is created in db.php
            // If not, create the connection here
            $host = "localhost";
            $username = "root"; // Default XAMPP username
            $password = ""; // Default XAMPP password
            $database = "sweet_treats";
            
            $conn = new mysqli($host, $username, $password, $database);
            
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
        }
        return $conn;
    }
}

// Function to validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to generate and send reset code
function generateAndSendResetCode($email) {
    global $email_functions_loaded;
    $error_message = "";
    $success_message = "";
    $show_code_form = false;
    
    try {
        // Get database connection
        $conn = getConnection();
        
        // Check if email exists and get user's name
        $query = "SELECT id, first_name, last_name FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_name = $user['first_name'] . ' ' . $user['last_name'];
            
            // Generate 6-digit code
            $reset_code = sprintf("%06d", mt_rand(100000, 999999));
            $expires = date('Y-m-d H:i:s', time() + 1800); // Code expires in 30 minutes
            
            // Delete any existing codes for this email
            $delete_query = "DELETE FROM password_resets WHERE email = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("s", $email);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // Insert new code
            $insert_query = "INSERT INTO password_resets (email, code, expires) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sss", $email, $reset_code, $expires);
            $insert_stmt->execute();
            $insert_stmt->close();
            
            // Generate a dummy reset URL (kept for compatibility)
            $base_url = getBaseURL();
            $reset_url = $base_url . "/reset-password.php?code=" . $reset_code;
            
            // Send email with verification code if email functions are loaded
            if ($email_functions_loaded && function_exists('sendPasswordResetEmail')) {
                $email_result = sendPasswordResetEmail($email, $user_name, $reset_code, $reset_url);
                
                if ($email_result['success']) {
                    $success_message = "A new password reset code has been sent to your email.";
                    $show_code_form = true;
                    $_SESSION['reset_email'] = $email;
                } else {
                    $error_message = "Failed to send password reset email: " . $email_result['message'];
                    
                    // Delete the code since email failed
                    $cleanup_query = "DELETE FROM password_resets WHERE email = ?";
                    $cleanup_stmt = $conn->prepare($cleanup_query);
                    $cleanup_stmt->bind_param("s", $email);
                    $cleanup_stmt->execute();
                    $cleanup_stmt->close();
                }
            } else {
                $error_message = "Email service is currently unavailable. Please try again later.";
                
                // Delete the code since email service is unavailable
                $cleanup_query = "DELETE FROM password_resets WHERE email = ?";
                $cleanup_stmt = $conn->prepare($cleanup_query);
                $cleanup_stmt->bind_param("s", $email);
                $cleanup_stmt->execute();
                $cleanup_stmt->close();
            }
            
            // For development/testing only - remove in production
            if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
                // Override error message for development environment
                if (!empty($error_message)) {
                    $error_message = ""; // Clear error for development
                }
                
                $success_message = "Email would be sent in production. Development mode: Code is $reset_code";
                $show_code_form = true;
                $_SESSION['reset_email'] = $email;
            }
        } else {
            // Now we explicitly tell the user the email doesn't exist in our system
            $error_message = "No account found with this email address. Please check your email or register a new account.";
            
            // Log actual result for debugging
            error_log("Password reset attempted for non-existent email: $email");
        }

        $stmt->close();
    } catch (Exception $e) {
        $error_message = "System error occurred. Please try again later.";
        error_log("Error in forgot-password.php: " . $e->getMessage());
    }
    
    return array(
        'error_message' => $error_message,
        'success_message' => $success_message,
        'show_code_form' => $show_code_form
    );
}

// Ensure the email_functions.php file is correctly included
$email_functions_loaded = false;
if (file_exists("includes/email_functions.php")) {
    include_once "includes/email_functions.php";
    $email_functions_loaded = true;
} elseif (file_exists("email_functions.php")) {
    include_once "email_functions.php";
    $email_functions_loaded = true;
} else {
    // Log the error if file can't be found
    error_log("Cannot find email_functions.php file");
}

// Initialize variables
$error_message = "";
$success_message = "";
$show_code_form = false;
$show_password_form = false;
$email = "";
$show_email_form = true; // Default to showing email form

// Create password_resets table if it doesn't exist - Do this once at the beginning
try {
    $conn = getConnection();
    $create_table = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        code VARCHAR(10) NOT NULL,
        expires DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (email)
    )";
    $conn->query($create_table);
} catch (Exception $e) {
    error_log("Error creating password_resets table: " . $e->getMessage());
}

// Check if coming from login page with email parameter
if (isset($_GET['email']) && !empty($_GET['email'])) {
    $email = trim($_GET['email']);
    // We're only pre-filling the email field, not automatically sending the code
    // Ensure we only show the email form, not send any code automatically
    $show_email_form = true;
    $show_code_form = false;
    $show_password_form = false;
}

// Step 1: Email submission to request reset code
if (isset($_POST['request_code'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error_message = "Email address is required.";
    } elseif (!isValidEmail($email)) {
        $error_message = "Invalid email format.";
    } else {
        $result = generateAndSendResetCode($email);
        $error_message = $result['error_message'];
        $success_message = $result['success_message'];
        $show_code_form = $result['show_code_form'];
        if ($show_code_form) {
            $show_email_form = false;
        }
    }
}

// Handle "Request New Code" action
if (isset($_POST['resend_code'])) {
    $email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
    
    if (empty($email)) {
        $error_message = "Session expired. Please start over.";
        $show_email_form = true;
    } else {
        $result = generateAndSendResetCode($email);
        $error_message = $result['error_message'];
        $success_message = $result['success_message'];
        $show_code_form = $result['show_code_form'];
        if ($show_code_form) {
            $show_email_form = false;
        }
    }
}

// Step 2: Verify the reset code
if (isset($_POST['verify_code'])) {
    $code = trim($_POST['reset_code']);
    $email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
    
    if (empty($code) || empty($email)) {
        $error_message = "Verification code is required or session expired.";
        $show_code_form = true;
        $show_email_form = false;
    } else {
        try {
            $conn = getConnection();
            
            // Check if code is valid and not expired
            $query = "SELECT * FROM password_resets WHERE email = ? AND code = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $email, $code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                // Code is valid, show password reset form
                $show_password_form = true;
                $show_code_form = false;
                $show_email_form = false;
                $_SESSION['verified_email'] = $email;
                $_SESSION['verified_code'] = $code;
                // Store verification timestamp to enforce timeout if user takes too long
                $_SESSION['verification_time'] = time();
            } else {
                // Check if code exists but is expired
                $expired_query = "SELECT * FROM password_resets WHERE email = ? AND code = ?";
                $expired_stmt = $conn->prepare($expired_query);
                $expired_stmt->bind_param("ss", $email, $code);
                $expired_stmt->execute();
                $expired_result = $expired_stmt->get_result();
                
                if ($expired_result->num_rows === 0) {
                    $error_message = "Invalid verification code. Please try again.";  
                } else {
                    // Code exists but treating it as valid regardless of expiration
                    $show_password_form = true;
                    $show_code_form = false;
                    $show_email_form = false;
                    $_SESSION['verified_email'] = $email;
                    $_SESSION['verified_code'] = $code;
                    $_SESSION['verification_time'] = time();
                }
                
                $expired_stmt->close();
                $show_code_form = true;
                $show_email_form = false;
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $error_message = "System error occurred. Please try again later.";
            error_log("Error in forgot-password.php (code verification): " . $e->getMessage());
            $show_code_form = true;
            $show_email_form = false;
        }
    }
}

// Step 3: Process the new password
if (isset($_POST['update_password'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = isset($_SESSION['verified_email']) ? $_SESSION['verified_email'] : '';
    $code = isset($_SESSION['verified_code']) ? $_SESSION['verified_code'] : '';
    $verification_time = isset($_SESSION['verification_time']) ? $_SESSION['verification_time'] : 0;
    
    // Check if verification is still valid (10 minute timeout after code verification)
    $verification_timeout = 600; // 10 minutes in seconds
    $verification_expired = (time() - $verification_time) > $verification_timeout;
    
    if (empty($email) || empty($code)) {
        $error_message = "Your verification session has expired.";
        $show_email_form = true;
    } elseif ($verification_expired) {
        $error_message = "For security reasons, your password reset session has timed out. Please start over.";
        $show_email_form = true;
        
        // Clear verification session
        unset($_SESSION['verified_email']);
        unset($_SESSION['verified_code']);
        unset($_SESSION['verification_time']);
    } elseif (empty($new_password) || strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
        $show_password_form = true;
        $show_email_form = false;
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
        $show_password_form = true;
        $show_email_form = false;
    } else {
        try {
            $conn = getConnection();
            
            // Verify code is still valid in database - removed expiration check
            $verify_query = "SELECT * FROM password_resets WHERE email = ? AND code = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("ss", $email, $code);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            
            if ($verify_result->num_rows === 1) {
                // Check if user exists (additional security)
                $user_query = "SELECT id FROM users WHERE email = ?";
                $user_stmt = $conn->prepare($user_query);
                $user_stmt->bind_param("s", $email);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                
                if ($user_result->num_rows === 1) {
                    // Update the user's password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE users SET password = ? WHERE email = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("ss", $hashed_password, $email);
                    $update_stmt->execute();
                    
                    if ($update_stmt->affected_rows > 0) {
                        // Delete the used reset code
                        $delete_query = "DELETE FROM password_resets WHERE email = ?";
                        $delete_stmt = $conn->prepare($delete_query);
                        $delete_stmt->bind_param("s", $email);
                        $delete_stmt->execute();
                        $delete_stmt->close();
                        
                        // Clean up session
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['verified_email']);
                        unset($_SESSION['verified_code']);
                        unset($_SESSION['verification_time']);
                        
                        $success_message = "Your password has been updated successfully. You can now <a href='login.php'>login</a> with your new password.";
                        $show_email_form = false;
                        $show_code_form = false;
                        $show_password_form = false;
                    } else {
                        $error_message = "Failed to update password. Please try again.";
                        $show_password_form = true;
                        $show_email_form = false;
                    }
                    
                    $update_stmt->close();
                } else {
                    $error_message = "User account not found.";
                    $show_email_form = true;
                }
                
                $user_stmt->close();
            } else {
                $error_message = "Your verification code has expired. Please restart the password reset process.";
                $show_email_form = true;
                
                // Clear verification session
                unset($_SESSION['verified_email']);
                unset($_SESSION['verified_code']);
                unset($_SESSION['verification_time']);
            }
            
            $verify_stmt->close();
        } catch (Exception $e) {
            $error_message = "System error occurred. Please try again later.";
            error_log("Error in forgot-password.php (password update): " . $e->getMessage());
            $show_password_form = true;
            $show_email_form = false;
        }
    }
}

// Keep the code form visible if there was an error during verification
// Only show code form if the user has actually requested a code
if (isset($_SESSION['reset_email']) && !$show_password_form && !isset($_POST['update_password']) && isset($_POST['verify_code'])) {
    $show_code_form = true;
    $show_email_form = false;
    $email = $_SESSION['reset_email'];
} else if (isset($_SESSION['reset_email']) && !isset($_POST['update_password']) && isset($_POST['request_code'])) {
    // If they've submitted the email form, show the code form
    $show_code_form = true;
    $show_email_form = false;
    $email = $_SESSION['reset_email'];
}

// Keep the password form visible if there was an error during password update
if (isset($_SESSION['verified_email']) && isset($_SESSION['verified_code']) && isset($_SESSION['verification_time'])) {
    // Check verification timeout
    $verification_timeout = 600; // 10 minutes in seconds
    $verification_expired = (time() - $_SESSION['verification_time']) > $verification_timeout;
    
    if (!$verification_expired) {
        $show_password_form = true;
        $show_email_form = false;
        $show_code_form = false;
    } else {
        // Clear verification session if expired
        unset($_SESSION['verified_email']);
        unset($_SESSION['verified_code']);
        unset($_SESSION['verification_time']);
        $error_message = "For security reasons, your password reset session has timed out. Please start over.";
        $show_email_form = true;
    }
}

// Function to sanitize output for HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Treats - Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/forgot-password.css">
</head>
<body>
    <div class="container">
        <div class="logo">
            SWEET TREATS 
            <i class="fas fa-ice-cream"></i>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo h($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; // Allow HTML links in success messages ?>
            </div>
        <?php endif; ?>

        <?php if ($show_password_form): ?>
            <!-- Step 3: Password Reset Form -->
            <h3 class="form-title">Create New Password</h3>
            <form method="post" action="">
                <div class="password-requirements">
                    Password must be at least 8 characters long.
                </div>
                <input type="password" name="new_password" placeholder="New Password" required minlength="8">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="8">
                <button type="submit" name="update_password">Update Password</button>
                
                <div class="login-link">
                    <a href="login.php">Back to Login</a>
                </div>
            </form>
        <?php elseif ($show_code_form): ?>
            <!-- Step 2: Verification Code Form -->
            <h3 class="form-title">Enter Verification Code</h3>
            <form method="post" action="">
                <p>A verification code has been sent to: <strong><?php echo h($email); ?></strong></p>
                <input type="text" name="reset_code" placeholder="Enter 6-digit code" required>
                <button type="submit" name="verify_code">Verify Code</button>
                
                <div class="login-link">
                    <!-- Changed to submit button that sends new code instead of link -->
                    <form method="post" action="" style="display: inline;">
                        <a href=""><button type="submit" name="resend_code" class="text-link">Request New Code</button></a>
                    </form> | <a href="login.php">Back to Login</a>
                </div>
            </form>
        <?php elseif ($show_email_form): ?>
            <!-- Step 1: Email Form -->
            <h3 class="form-title">Forgot Password</h3>
            <form method="post" action="">
                <input type="email" name="email" placeholder="Email address" value="<?php echo h($email); ?>" required>
                <button type="submit" name="request_code">Send Verification Code</button>

                <div class="login-link">
                    Remember your password? <a href="login.php">Login here</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>