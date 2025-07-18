    <?php
    include "includes/db.php";
    // Add PHPMailer classes
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require 'vendor/autoload.php';  // Path to autoload.php from Composer

    $message = "";
    $status = "error";

    // Check if form was submitted
    if (isset($_POST['resend'])) {
        $email = trim($_POST['email']);
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
        } else {
            try {
                // Check if the email exists in the database
                $stmt = $conn->prepare("SELECT id, full_name, is_verified FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    $message = "Email address not found in our system.";
                } else {
                    $user = $result->fetch_assoc();
                    
                    if ($user['is_verified'] == 1) {
                        $message = "Your email is already verified. You can login to your account.";
                        $status = "success";
                    } else {
                        // Generate a new verification token
                        $verification_token = bin2hex(random_bytes(32));
                        $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
                        
                        // Update the token in the database
                        $update_stmt = $conn->prepare("UPDATE users SET verification_token = ?, token_expiry = ? WHERE id = ?");
                        $update_stmt->bind_param("ssi", $verification_token, $token_expiry, $user['id']);
                        
                        if ($update_stmt->execute()) {
                            // Send the verification email
                            if (sendVerificationEmail($email, $user['full_name'], $verification_token)) {
                                $message = "A new verification email has been sent to your address.";
                                $status = "success";
                            } else {
                                $message = "Failed to send verification email. Please try again later.";
                            }
                        } else {
                            $message = "System error. Please try again later.";
                        }
                        
                        $update_stmt->close();
                    }
                }
                
                $stmt->close();
            } catch (Exception $e) {
                $message = "An error occurred: " . $e->getMessage();
            }
        }
    }

    // Function to send verification email (same as in register.php)
    function sendVerificationEmail($email, $name, $token) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';  // You can use Gmail or other SMTP providers
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your_email@gmail.com';  // SMTP username (your email)
            $mail->Password   = 'your_app_password';     // SMTP password (use app password for Gmail)
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            
            // Recipients
            $mail->setFrom('noreply@sweettreats.com', 'Sweet Treats');
            $mail->addAddress($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Sweet Treats Account';
            
            // Generate the verification URL
            $verification_url = 'https://yourdomain.com/verify.php?token=' . $token;
            
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
                        color: white;
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
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Resend Verification Email - Sweet Treats</title>
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
            .message {
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 15px;
                text-align: center;
            }
            .error {
                background-color: #f8d7da;
                color: #721c24;
            }
            .success {
                background-color: #d4edda;
                color: #155724;
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
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo">
                SWEET TREATS 
                <i class="fas fa-ice-cream"></i>
            </div>
            
            <h2 style="text-align: center;">Resend Verification Email</h2>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $status; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <input type="email" name="email" placeholder="Enter your email address" required>
                <input type="submit" name="resend" value="Resend Verification Email" style="background-color: #FF69B4; color: white; border: none; cursor: pointer;">
                
                <div class="login-link">
                    Already verified? <a href="login.php">Sign in here</a>
                </div>
            </form>
        </div>
    </body>
    </html>