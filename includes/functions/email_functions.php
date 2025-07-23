    <?php
    /**
     * Email Functions
     * 
     * This file contains functions for sending various types of emails
     * through the Brevo API.
     */

    // Include the Brevo configuration file with correct path
    $brevo_config_paths = [
        __DIR__ . '/brevo_config.php',
        __DIR__ . '/config/brevo_config.php',
        __DIR__ . '/includes/config/brevo_config.php',
        dirname(__DIR__) . '/includes/config/brevo_config.php',
        dirname(__DIR__) . '/config/brevo_config.php'
    ];

    $brevo_loaded = false;
    foreach ($brevo_config_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $brevo_loaded = true;
            break;
        }
    }

    // Define a fallback function if configuration can't be found
    if (!$brevo_loaded) {
        // Log that the config file wasn't found
        error_log("Brevo configuration file not found after trying multiple paths: " . implode(', ', $brevo_config_paths));
        
        if (!function_exists('send_brevo_email')) {
            function send_brevo_email($to_email, $to_name, $subject, $html_content, $text_content = '') {
                error_log("Brevo configuration file not found. Email not sent to: $to_email");
                return [
                    'success' => false,
                    'message' => 'Email configuration not found'
                ];
            }
        }
    }

    /**
     * Validates email data before sending
     * 
     * @param string $email User's email address
     * @param string $name User's name
     * @return array Success status and message
     */
    function validateEmailData($email, $name) {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email address: $email");
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        if (empty($name)) {
            error_log("Missing recipient name for email: $email");
            return ['success' => false, 'message' => 'Missing recipient name'];
        }
        
        // Check if Brevo is properly configured
        if (!defined('BREVO_API_KEY') || empty(BREVO_API_KEY) || 
            !defined('BREVO_SENDER_EMAIL') || empty(BREVO_SENDER_EMAIL)) {
            error_log("Brevo not configured properly. Missing API key or sender email.");
            return ['success' => false, 'message' => 'Email service not configured properly'];
        }
        
        return ['success' => true, 'message' => 'Validation passed'];
    }

    /**
     * Send account verification email with code
     * 
     * @param string $email User's email address
     * @param string $name User's full name
     * @param string $verification_code 6-digit verification code
     * @return array Result containing success status and message
     */
    function sendVerificationEmail($email, $name, $verification_code) {
        // Log the attempt
        error_log("Attempting to send verification email with code to: " . $email);
        
        // Validate input data
        $validation = validateEmailData($email, $name);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Validate verification code
        if (empty($verification_code)) {
            error_log("Missing verification code for email: $email");
            return ['success' => false, 'message' => 'Missing verification code'];
        }
        
        // Create HTML email content
        $htmlContent = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { text-align: center; padding: 10px; background-color: #FFF0F5; color: #FF69B4; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; }
                .code-box { display: inline-block; padding: 15px 25px; background-color: #f8f9fa; font-family: monospace; font-size: 32px; letter-spacing: 8px; margin: 20px 0; text-align: center; border-radius: 8px; border: 2px solid #FF69B4; color: #FF69B4; font-weight: bold; }
                .footer { text-align: center; padding: 10px; font-size: 12px; color: #777; }
                .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🍦 Sweet Treats</h1>
                </div>
                <div class="content">
                    <h2>Welcome, ' . htmlspecialchars($name) . '!</h2>
                    <p>Thank you for registering at Sweet Treats. To activate your account, please use the verification code below:</p>
                    
                    <div style="text-align: center;">
                        <div class="code-box">' . htmlspecialchars($verification_code) . '</div>
                    </div>
                    
                    <p>Enter this code on the verification page to complete your registration.</p>
                    
                    <div class="warning">
                        <strong>Important:</strong> This verification code will expire in 30 minutes for security reasons.
                    </div>
                    
                    <p>If you didn\'t create this account, you can safely ignore this email.</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' Sweet Treats. All rights reserved.</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Create plain text version
        $textContent = "Welcome to Sweet Treats, " . $name . "!\n\n" .
                    "Thank you for registering. To activate your account, please use this verification code:\n\n" .
                    "VERIFICATION CODE: " . $verification_code . "\n\n" .
                    "Enter this code on the verification page to complete your registration.\n\n" .
                    "IMPORTANT: This verification code will expire in 30 minutes for security reasons.\n\n" .
                    "If you didn't create this account, you can safely ignore this email.\n\n" .
                    "© " . date('Y') . " Sweet Treats. All rights reserved.\n" .
                    "This is an automated message, please do not reply to this email.";
        
        // Try to send the email using the Brevo API
        try {
            if (!function_exists('send_brevo_email')) {
                throw new Exception("Brevo email sending function not available");
            }
            
            $result = send_brevo_email(
                $email,
                $name,
                "Your Sweet Treats Verification Code",
                $htmlContent,
                $textContent
            );
            
            if ($result['success']) {
                error_log("Verification email with code sent successfully to: $email");
            } else {
                error_log("Verification email failed: " . ($result['message'] ?? 'Unknown error'));
            }
            
            return $result;
        } catch (Exception $e) {
            $error_message = "Exception while sending verification email: " . $e->getMessage();
            error_log($error_message);
            return ['success' => false, 'message' => $error_message];
        }
    }

    /**
     * Generate a 6-digit verification code
     * 
     * @return string 6-digit verification code
     */
    function generateVerificationCode() {
        return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send password reset email with a code
     * 
     * @param string $email User's email address
     * @param string $name User's full name
     * @param string $token Reset code to send to the user
     * @param string $reset_url Reset URL (ignored, kept for compatibility)
     * @return array Result containing success status and message
     */
    function sendPasswordResetEmail($email, $name, $token, $reset_url = '') {
        // Log the attempt
        error_log("Attempting to send password reset email with code to: " . $email);
        
        // Validate input data
        $validation = validateEmailData($email, $name);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Validate reset token (used as code)
        if (empty($token)) {
            error_log("Missing reset code for email: $email");
            return ['success' => false, 'message' => 'Missing reset code'];
        }
        
        // Create HTML email content with the code
        $htmlContent = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { text-align: center; padding: 10px; background-color: #FFF0F5; color: #FF69B4; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; }
                .code-box { display: inline-block; padding: 10px 20px; background-color: #f2f2f2; font-family: monospace; font-size: 24px; letter-spacing: 5px; margin: 20px 0; text-align: center; border-radius: 5px; border: 1px dashed #ccc; }
                .footer { text-align: center; padding: 10px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Sweet Treats</h1>
                </div>
                <div class="content">
                    <h2>Password Reset Code</h2>
                    <p>Hello, ' . htmlspecialchars($name) . '!</p>
                    <p>We received a request to reset your password. Please use the code below to reset your password:</p>
                    <p style="text-align: center;">
                        <span class="code-box">' . htmlspecialchars($token) . '</span>
                    </p>
                    <p>Enter this code on the password reset page to create a new password.</p>
                    <p>This code will expire in 30 minutes.</p>
                    <p>If you didn\'t request a password reset, you can safely ignore this email.</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' Sweet Treats. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Create plain text version
        $textContent = "Password Reset Code\n\n" .
                    "Hello, " . $name . "!\n\n" .
                    "We received a request to reset your password. Please use the code below to reset your password:\n\n" .
                    $token . "\n\n" .
                    "Enter this code on the password reset page to create a new password.\n\n" .
                    "This code will expire in 30 minutes.\n\n" .
                    "If you didn't request a password reset, you can safely ignore this email.\n\n" .
                    "© " . date('Y') . " Sweet Treats. All rights reserved.";
        
        // Try to send the email using the Brevo API
        try {
            if (!function_exists('send_brevo_email')) {
                throw new Exception("Brevo email sending function not available");
            }
            
            $result = send_brevo_email(
                $email,
                $name,
                "Reset Your Sweet Treats Password",
                $htmlContent,
                $textContent
            );
            
            if ($result['success']) {
                error_log("Password reset email sent successfully to: $email");
            } else {
                error_log("Password reset email failed: " . ($result['message'] ?? 'Unknown error'));
            }
            
            return $result;
        } catch (Exception $e) {
            $error_message = "Exception while sending password reset email: " . $e->getMessage();
            error_log($error_message);
            return ['success' => false, 'message' => $error_message];
        }
    }
                
    /**
     * Send order confirmation email
     * 
     * @param string $email User's email address
     * @param string $name User's full name
     * @param array $order_details Order details including order number, items, and total
     * @return array Result containing success status and message
     */
    function sendOrderConfirmationEmail($email, $name, $order_details) {
        // Log the attempt
        error_log("Attempting to send order confirmation email to: " . $email);
        
        // Validate input data
        $validation = validateEmailData($email, $name);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Validate order details
        if (empty($order_details) || 
            !isset($order_details['order_number']) || 
            !isset($order_details['order_date']) || 
            !isset($order_details['items']) || 
            !is_array($order_details['items']) ||
            !isset($order_details['total'])) {
            error_log("Invalid order details for email: $email");
            return ['success' => false, 'message' => 'Invalid order details'];
        }
        
        // Format order items for email
        $itemsHtml = '';
        $itemsText = '';
        $subtotal = 0;
        
        foreach ($order_details['items'] as $item) {
            if (!isset($item['name']) || !isset($item['quantity']) || !isset($item['price'])) {
                continue;
            }
            
            $itemTotal = $item['price'] * $item['quantity'];
            $subtotal += $itemTotal;
            
            $itemsHtml .= '<tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($item['name']) . '</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">' . $item['quantity'] . '</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">$' . number_format($item['price'], 2) . '</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">$' . number_format($itemTotal, 2) . '</td>
            </tr>';
            
            $itemsText .= $item['name'] . " x" . $item['quantity'] . " - $" . 
                        number_format($item['price'], 2) . " each = $" . 
                        number_format($itemTotal, 2) . "\n";
        }
        
        // Create HTML email content
        $htmlContent = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { text-align: center; padding: 10px; background-color: #FFF0F5; color: #FF69B4; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; }
                .order-details { margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; }
                th { background-color: #f2f2f2; text-align: left; padding: 8px; }
                .footer { text-align: center; padding: 10px; font-size: 12px; color: #777; }
                .total-row { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Sweet Treats</h1>
                </div>
                <div class="content">
                    <h2>Order Confirmation</h2>
                    <p>Hello, ' . htmlspecialchars($name) . '!</p>
                    <p>Thank you for your order. We\'re preparing your sweet treats with care!</p>
                    
                    <div class="order-details">
                        <h3>Order #' . htmlspecialchars($order_details['order_number']) . '</h3>
                        <p><strong>Order Date:</strong> ' . htmlspecialchars($order_details['order_date']) . '</p>
                        
                        <table>
                            <tr>
                                <th style="padding: 8px; text-align: left;">Item</th>
                                <th style="padding: 8px; text-align: center;">Qty</th>
                                <th style="padding: 8px; text-align: right;">Price</th>
                                <th style="padding: 8px; text-align: right;">Subtotal</th>
                            </tr>
                            ' . $itemsHtml . '
                            <tr class="total-row">
                                <td colspan="3" style="padding: 8px; text-align: right; border-top: 2px solid #ddd;"><strong>Total:</strong></td>
                                <td style="padding: 8px; text-align: right; border-top: 2px solid #ddd;"><strong>$' . number_format($order_details['total'], 2) . '</strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    <p>If you have any questions about your order, please contact our customer support team.</p>
                    <p>Thank you for choosing Sweet Treats!</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' Sweet Treats. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Create plain text version
        $textContent = "Order Confirmation\n\n" .
                    "Hello, " . $name . "!\n\n" . 
                    "Thank you for your order. We're preparing your sweet treats with care!\n\n" .
                    "Order #" . $order_details['order_number'] . "\n" .
                    "Order Date: " . $order_details['order_date'] . "\n\n" .
                    "Items:\n" .
                    "-------------------------\n" .
                    $itemsText .
                    "-------------------------\n" .
                    "Total: $" . number_format($order_details['total'], 2) . "\n\n" .
                    "If you have any questions about your order, please contact our customer support team.\n\n" .
                    "Thank you for choosing Sweet Treats!\n\n" .
                    "© " . date('Y') . " Sweet Treats. All rights reserved.";
        
        // Try to send the email using the Brevo API
        try {
            if (!function_exists('send_brevo_email')) {
                throw new Exception("Brevo email sending function not available");
            }
            
            $result = send_brevo_email(
                $email,
                $name,
                "Your Sweet Treats Order #" . $order_details['order_number'],
                $htmlContent,
                $textContent
            );
            
            if ($result['success']) {
                error_log("Order confirmation email sent successfully to: $email");
            } else {
                error_log("Order confirmation email failed: " . ($result['message'] ?? 'Unknown error'));
            }
            
            return $result;
        } catch (Exception $e) {
            $error_message = "Exception while sending order confirmation email: " . $e->getMessage();
            error_log($error_message);
            return ['success' => false, 'message' => $error_message];
        }
    }
    ?>