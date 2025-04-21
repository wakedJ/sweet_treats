<?php

if (!file_exists('vendor/autoload.php')) {
    die("Error: PHPMailer is not installed. Please run 'composer require phpmailer/phpmailer'");
}

include "includes/db.php";

// Clean up expired temporary users
$cleanup_query = "DELETE FROM temp_users WHERE expiry_datetime < NOW()";
$conn->query($cleanup_query);
// Add PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize error message variable
$error_message = "";
$success_message = "";

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
                // Generate verification token
                $verification_token = bin2hex(random_bytes(32));
                $expiry_datetime = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert into temp_users table
                $insert_query = "INSERT INTO temp_users (email, full_name, password, verification_token, expiry_datetime, phone_number) 
                VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                
                if (!$insert_stmt) {
                    throw new Exception("Prepare failed for insert: " . $conn->error);
                }
                
                $insert_stmt->bind_param("ssssss", $email, $full_name, $hashed_password, $verification_token, $expiry_datetime, $phone_number);
                
                if ($insert_stmt->execute()) {
                    // Generate verification URL - Create a more reliable URL
                    // Get the current URL path components
                    $base_url = '';
                    if (isset($_SERVER['HTTP_HOST'])) {
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                        $base_url = $protocol . '://' . $_SERVER['HTTP_HOST'];
                    } else {
                        $base_url = 'http://localhost'; // Fallback for CLI
                    }
                    
                    // Get the directory path
                    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
                    $directory = dirname($script_name);
                    // If it's the root directory, set to empty string
                    if ($directory == '/' || $directory == '\\') {
                        $directory = '';
                    }
                    
                    $verification_url = $base_url . $directory . '/verify.php?token=' . $verification_token;
                    
                    // Add a direct verification link that will always display for testing
                    $direct_verification_link = '<a href="verify.php?token=' . $verification_token . '">Direct Verification Link</a>';
                    
                    // Special handling for LIU email addresses
                    if (strpos($email, '@students.liu.edu.lb') !== false || strpos($email, 'liu.edu.lb') !== false) {
                        // For LIU students, show direct link without sending email
                        $success_message = "Registration successful! For LIU students, please use this direct link to verify your account: <a href='verify.php?token=" . $verification_token . "'>Verify Account</a>";
                        error_log("LIU domain detected. Provided direct verification link instead of sending email.");
                    } else {
                        // Regular email process for non-LIU addresses
                        try {
                            $emailSent = sendVerificationEmail($email, $full_name, $verification_token, $verification_url);
                            
                            if ($emailSent) {
                                $success_message = "Registration successful! Please check your email to verify your account.<br><br>If you don't receive the email, you can use this direct link: " . $direct_verification_link;
                            } else {
                                // Try a simplified test email
                                $testEmailSent = debugEmailSending($email, $full_name, $verification_token, $verification_url);
                                
                                if ($testEmailSent) {
                                    $success_message = "Registration successful! Email test worked but verification email may have issues. You can use this direct link: " . $direct_verification_link;
                                } else {
                                    // Email sending failed
                                    error_log("Email sending failed to: " . $email);
                                    $success_message = "Registration successful! However, there was an issue sending the verification email. You can use this direct link: " . $direct_verification_link;
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Email Exception: " . $e->getMessage());
                            $success_message = "Registration successful! But verification email could not be sent. You can use this direct link: " . $direct_verification_link;
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

// Function to send verification email with improved debugging
function sendVerificationEmail($email, $name, $token, $verification_url) {
    // Log the attempt
    error_log("Sending verification email to: " . $email);
    
    // Special handling for LIU domains - skip actual sending
    if (strpos($email, 'liu.edu.lb') !== false) {
        error_log("LIU domain detected in sendVerificationEmail - skipping actual email sending");
        return true; // Return true to indicate "success" without sending
    }
    
    require 'vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lanawaked237@gmail.com';
        $mail->Password   = 'nkuu slol muzq wmun'; // Check if this is still valid
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        
        // Add this for better compatibility with all domains
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Enhanced debug for specific domains
        if (strpos($email, '.lb') !== false) {
            error_log("Special handling for .lb domain: $email");
            $mail->SMTPDebug = 3; // More verbose debugging for .lb addresses 
        } else {
            $mail->SMTPDebug = 2;
        }
        
        // Increase timeout values for slower connections
        $mail->Timeout = 120; // seconds
        
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer: $str");
        };
        
        // Recipients
        $mail->setFrom('lanawaked237@gmail.com', 'Sweet Treats');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Sweet Treats Account';
        
        // Email body with HTML styling that matches your site's theme
        $mail->Body = '
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                .header {
                    text-align: center;
                    padding: 10px;
                    background-color: #FFF0F5;
                    color: #FF69B4;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    padding: 20px;
                }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #FF69B4;
                    color: white !important;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    padding: 10px;
                    font-size: 12px;
                    color: #777;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Sweet Treats</h1>
                </div>
                <div class="content">
                    <h2>Welcome, ' . htmlspecialchars($name) . '!</h2>
                    <p>Thank you for registering at Sweet Treats. To activate your account, please verify your email address by clicking the button below:</p>
                    <p style="text-align: center;">
                        <a href="' . $verification_url . '" class="button">Verify Email Address</a>
                    </p>
                    <p>If the button doesn\'t work, copy and paste this link into your browser:</p>
                    <p>' . $verification_url . '</p>
                    <p>This link will expire in 24 hours.</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' Sweet Treats. All rights reserved.</p>
                    <p>If you didn\'t create this account, you can safely ignore this email.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->AltBody = 'Welcome to Sweet Treats! Please verify your email by clicking this link: ' . $verification_url . "\n\nThis link will expire in 24 hours.";
        
        // Add priority settings to help with delivery
        $mail->Priority = 1; // Highest priority
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');
        
        $result = $mail->send();
        error_log("Email sent: " . ($result ? "Yes" : "No") . " to " . $email);
        return $result;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        error_log("Exception Details: " . $e->getMessage());
        return false;
    }
}

// Debug function to test email sending with minimal content
function debugEmailSending($email, $name, $token, $verification_url) {
    // Special handling for LIU domains - skip actual sending
    if (strpos($email, 'liu.edu.lb') !== false) {
        error_log("LIU domain detected in debugEmailSending - skipping actual email sending");
        return true; // Return true to indicate "success" without sending
    }
    
    // First, log that we're attempting to send an email
    error_log("Attempting to send test email to: " . $email);
    
    require 'vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Enable verbose debug output
        if (strpos($email, '.lb') !== false) {
            error_log("Test email for .lb domain: $email");
            $mail->SMTPDebug = 3; // More verbose debugging for .lb addresses
        } else {
            $mail->SMTPDebug = 2;
        }
        
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug: $str");
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lanawaked237@gmail.com';
        $mail->Password   = 'nkuu slol muzq wmun'; // Check if app password is still valid
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        
        // Add improved SSL options
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Set timeouts to give enough time for connection
        $mail->Timeout = 120; // increased from 60 seconds
        $mail->SMTPKeepAlive = true; // Keep the SMTP connection open
        
        // Recipients
        $mail->setFrom('lanawaked237@gmail.com', 'Sweet Treats');
        $mail->addAddress($email, $name);
        
        // Simple test content
        $mail->isHTML(true);
        $mail->Subject = 'Sweet Treats - Email Test';
        
        $mail->Body    = 'This is a test email to verify your email functionality is working. <br><br>Your verification link is: <a href="' . $verification_url . '">Click here to verify</a>';
        $mail->AltBody = 'This is a test email. Your verification link is: ' . $verification_url;
        
        // Add priority settings to help with .lb domains
        $mail->Priority = 1; // Highest priority
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');
        
        // Send the email
        $result = $mail->send();
        error_log("Email send result: " . ($result ? "SUCCESS" : "FAILURE") . " to " . $email);
        return $result;
    } catch (Exception $e) {
        error_log("TEST Mailer Error: " . $mail->ErrorInfo);
        error_log("TEST Exception Details: " . $e->getMessage());
        return false;
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
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
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