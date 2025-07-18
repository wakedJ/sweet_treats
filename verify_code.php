<?php
session_start();

// Include required files
include "includes/db.php";
include "includes/cart_functions.php";
if (file_exists("includes/email_functions.php")) {
    include "includes/email_functions.php";
}

// Debugging: Log session and POST data
error_log("Session at verify_code start: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));

// Check if generateVerificationCode function exists, if not create it
if (!function_exists('generateVerificationCode')) {
    function generateVerificationCode() {
        return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}

$error_message = "";
$success_message = "";

// Check if user came from registration process
if (!isset($_SESSION['verification_email'])) {
    error_log("No verification_email in session, redirecting to register.php");
    header("Location: register.php");
    exit();
}

$email = $_SESSION['verification_email'];

// Function to get table structure
function getTableColumns($conn, $table_name) {
    $result = $conn->query("DESCRIBE $table_name");
    $columns = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

// Handle verification code submission
if (isset($_POST['verify_code'])) {
    $entered_code = trim($_POST['verification_code']);
    
    error_log("Verification attempt for email: $email, code entered: $entered_code");
    
    if (empty($entered_code)) {
        $error_message = "Please enter the verification code.";
        error_log("Empty verification code entered");
    } elseif (strlen($entered_code) !== 6 || !ctype_digit($entered_code)) {
        $error_message = "Please enter a valid 6-digit verification code.";
        error_log("Invalid code format entered: $entered_code");
    } else {
        try {
            // Check if connection exists
            if (!$conn) {
                throw new Exception("Database connection failed");
            }
            
            // Get table structures to determine correct column names
            $users_columns = getTableColumns($conn, 'users');
            $temp_users_columns = getTableColumns($conn, 'temp_users');
            
            error_log("Users table columns: " . implode(", ", $users_columns));
            error_log("Temp users table columns: " . implode(", ", $temp_users_columns));
            
            // Determine the correct primary key column name for users table
            $user_id_column = 'user_id'; // default
            if (in_array('ID', $users_columns)) {
                $user_id_column = 'ID';
            } elseif (in_array('id', $users_columns)) {
                $user_id_column = 'id';
            }
            
            // Determine the correct primary key column name for temp_users table
            $temp_user_id_column = 'temp_user_id'; // default
            if (in_array('ID', $temp_users_columns)) {
                $temp_user_id_column = 'ID';
            } elseif (in_array('id', $temp_users_columns)) {
                $temp_user_id_column = 'id';
            }
            
            error_log("Using user_id column: $user_id_column, temp_user_id column: $temp_user_id_column");
            
            // Check if connection supports transactions
            if (!method_exists($conn, 'begin_transaction')) {
                throw new Exception("Database connection doesn't support transactions");
            }
            
            // Start transaction for data consistency
            $conn->begin_transaction();
            
            // Check if the verification code matches and hasn't expired
            $verify_query = "SELECT $temp_user_id_column, first_name, last_name, password, phone_number, expiry_datetime, verification_token 
                           FROM temp_users 
                           WHERE email = ?";
            $verify_stmt = $conn->prepare($verify_query);
            
            if (!$verify_stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            $verify_stmt->bind_param("s", $email);
            
            if (!$verify_stmt->execute()) {
                throw new Exception("Database execute error: " . $verify_stmt->error);
            }
            
            $result = $verify_stmt->get_result();
            
            if ($result->num_rows === 1) {
                $temp_user = $result->fetch_assoc();
                
                // Debug: Log stored vs entered code
                error_log("Stored code: " . $temp_user['verification_token'] . ", Entered code: $entered_code");
                error_log("Expiry time: " . $temp_user['expiry_datetime'] . ", Current time: " . date('Y-m-d H:i:s'));
                
                // Check if code is expired
                if (strtotime($temp_user['expiry_datetime']) < time()) {
                    $error_message = "Verification code has expired. Please request a new code.";
                    error_log("Code expired for email: $email");
                    $conn->rollback();
                } 
                // Check if the entered code matches the stored code
                elseif ($temp_user['verification_token'] === $entered_code) {
                    
                    error_log("Verification code matches! Processing user creation...");
                    
                    // Check if email already exists in users table (prevent duplicates)
                    $check_existing_query = "SELECT $user_id_column FROM users WHERE email = ?";
                    $check_existing_stmt = $conn->prepare($check_existing_query);
                    
                    if (!$check_existing_stmt) {
                        throw new Exception("Database prepare error for existing user check: " . $conn->error);
                    }
                    
                    $check_existing_stmt->bind_param("s", $email);
                    
                    if (!$check_existing_stmt->execute()) {
                        throw new Exception("Database execute error for existing user check: " . $check_existing_stmt->error);
                    }
                    
                    $existing_result = $check_existing_stmt->get_result();
                    
                    if ($existing_result->num_rows > 0) {
                        error_log("User already exists in users table");
                        $conn->rollback();
                        throw new Exception("An account with this email already exists.");
                    }
                    $check_existing_stmt->close();
                    
                    // Prepare the insert query based on available columns
                    $insert_columns = ['email', 'first_name', 'last_name', 'password'];
                    $insert_values = ['?', '?', '?', '?'];
                    $bind_params = [$email, $temp_user['first_name'], $temp_user['last_name'], $temp_user['password']];
                    $bind_types = 'ssss';
                    
                    // Add phone_number if column exists
                    if (in_array('phone_number', $users_columns) || in_array('phone', $users_columns)) {
                        $phone_column = in_array('phone_number', $users_columns) ? 'phone_number' : 'phone';
                        $insert_columns[] = $phone_column;
                        $insert_values[] = '?';
                        $bind_params[] = $temp_user['phone_number'];
                        $bind_types .= 's';
                    }
                    
                    // Add is_verified if column exists
                    if (in_array('is_verified', $users_columns)) {
                        $insert_columns[] = 'is_verified';
                        $insert_values[] = '1';
                    } elseif (in_array('verified', $users_columns)) {
                        $insert_columns[] = 'verified';
                        $insert_values[] = '1';
                    }
                    
                    // Add created_at if column exists
                    if (in_array('created_at', $users_columns)) {
                        $insert_columns[] = 'created_at';
                        $insert_values[] = 'NOW()';
                    } elseif (in_array('date_created', $users_columns)) {
                        $insert_columns[] = 'date_created';
                        $insert_values[] = 'NOW()';
                    }
                    
                    $insert_user_query = "INSERT INTO users (" . implode(', ', $insert_columns) . ") 
                                        VALUES (" . implode(', ', $insert_values) . ")";
                    
                    error_log("Insert query: $insert_user_query");
                    
                    $insert_user_stmt = $conn->prepare($insert_user_query);
                    
                    if (!$insert_user_stmt) {
                        error_log("Failed to prepare insert statement: " . $conn->error);
                        throw new Exception("Database prepare error for user insert: " . $conn->error);
                    }
                    
                    $insert_user_stmt->bind_param($bind_types, ...$bind_params);
                    
                    if ($insert_user_stmt->execute()) {
                        $new_user_id = $insert_user_stmt->insert_id;
                        error_log("User inserted successfully with ID: $new_user_id");
                        
                        // Transfer cart items from temp_user to permanent user (if cart table exists)
                        $cart_check_query = "SHOW TABLES LIKE 'cart'";
                        $cart_check_result = $conn->query($cart_check_query);
                        
                        if ($cart_check_result && $cart_check_result->num_rows > 0) {
                            // Check cart table structure
                            $cart_columns = getTableColumns($conn, 'cart');
                            $cart_user_column = 'user_id';
                            if (in_array('ID', $cart_columns)) {
                                $cart_user_column = 'ID';
                            } elseif (in_array('id', $cart_columns)) {
                                $cart_user_column = 'id';
                            }
                            
                            $transfer_cart_query = "UPDATE cart SET $cart_user_column = ?, user_type = 'user' 
                                                  WHERE $cart_user_column = ? AND user_type = 'temp_user'";
                            $transfer_cart_stmt = $conn->prepare($transfer_cart_query);
                            
                            if ($transfer_cart_stmt) {
                                $transfer_cart_stmt->bind_param("ii", $new_user_id, $temp_user[$temp_user_id_column]);
                                $cart_transfer_result = $transfer_cart_stmt->execute();
                                error_log("Cart transfer result: " . ($cart_transfer_result ? "success" : "failed"));
                                if (!$cart_transfer_result) {
                                    error_log("Cart transfer error: " . $transfer_cart_stmt->error);
                                }
                                $transfer_cart_stmt->close();
                            }
                        } else {
                            error_log("Cart table doesn't exist, skipping cart transfer");
                        }
                        
                        // Delete the temporary user record
                        $delete_temp_query = "DELETE FROM temp_users WHERE $temp_user_id_column = ?";
                        $delete_temp_stmt = $conn->prepare($delete_temp_query);
                        
                        if (!$delete_temp_stmt) {
                            throw new Exception("Database prepare error for temp user deletion: " . $conn->error);
                        }
                        
                        $delete_temp_stmt->bind_param("i", $temp_user[$temp_user_id_column]);
                        
                        if (!$delete_temp_stmt->execute()) {
                            error_log("Failed to delete temp user: " . $delete_temp_stmt->error);
                            // Don't throw exception here as user creation succeeded
                        } else {
                            error_log("Temp user deletion result: success");
                        }
                        $delete_temp_stmt->close();
                        
                        // Commit transaction
                        $conn->commit();
                        error_log("Transaction committed successfully");
                        
                        // Set up user session
                        $_SESSION['user_id'] = $new_user_id;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_name'] = trim($temp_user['first_name'] . ' ' . $temp_user['last_name']);
                        $_SESSION['is_verified'] = true;
                        $_SESSION['logged_in'] = true;
                        
                        // Clean up verification session variables
                        unset($_SESSION['verification_email']);
                        unset($_SESSION['verification_code_sent']);
                        
                        $success_message = "Email verified successfully! Your account has been activated.";
                        
                        // Log successful verification
                        error_log("User successfully verified and created - ID: $new_user_id, Email: $email");
                        error_log("Session after verification: " . print_r($_SESSION, true));
                        
                    } else {
                        $conn->rollback();
                        error_log("Failed to insert user: " . $insert_user_stmt->error);
                        throw new Exception("Failed to create your account: " . $insert_user_stmt->error);
                    }
                    
                    $insert_user_stmt->close();
                    
                } else {
                    // Code doesn't match
                    $conn->rollback();
                    error_log("Code mismatch - Stored: " . $temp_user['verification_token'] . ", Entered: $entered_code");
                    $error_message = "Invalid verification code. Please check the code in your email and try again.";
                }
                
            } else {
                // No user found or expired
                $conn->rollback();
                $error_message = "Verification code has expired or is invalid. Please request a new code.";
                error_log("No temp user found for email: $email");
            }
            
            $verify_stmt->close();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($conn && method_exists($conn, 'rollback')) {
                $conn->rollback();
            }
            
            // Log the actual error for debugging
            error_log("Verification error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            
            // Show more specific error message for debugging
            $error_message = "System error occurred: " . $e->getMessage();
            
            // In production, you might want to use a generic message:
            // $error_message = "System error occurred. Please try again.";
        }
    }
}

// Handle resend code request
if (isset($_POST['resend_code'])) {
    try {
        error_log("Resend code requested for email: $email");
        
        // Get temp_users table structure
        $temp_users_columns = getTableColumns($conn, 'temp_users');
        $temp_user_id_column = 'temp_user_id';
        if (in_array('ID', $temp_users_columns)) {
            $temp_user_id_column = 'ID';
        } elseif (in_array('id', $temp_users_columns)) {
            $temp_user_id_column = 'id';
        }
        
        // Check if temp user still exists
        $check_query = "SELECT $temp_user_id_column, first_name, last_name FROM temp_users WHERE email = ?";
        $check_stmt = $conn->prepare($check_query);
        
        if (!$check_stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $check_stmt->bind_param("s", $email);
        
        if (!$check_stmt->execute()) {
            throw new Exception("Database execute error: " . $check_stmt->error);
        }
        
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 1) {
            $temp_user = $result->fetch_assoc();
            
            // Generate new verification code
            $new_verification_code = generateVerificationCode();
            $new_expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            
            // Update the temp user with new code
            $update_query = "UPDATE temp_users SET verification_token = ?, expiry_datetime = ? WHERE email = ?";
            $update_stmt = $conn->prepare($update_query);
            
            if (!$update_stmt) {
                throw new Exception("Database prepare error for update: " . $conn->error);
            }
            
            $update_stmt->bind_param("sss", $new_verification_code, $new_expiry, $email);
            
            if ($update_stmt->execute()) {
                // Send new verification email
                if (function_exists('sendVerificationEmail')) {
                    $full_name = trim($temp_user['first_name'] . ' ' . $temp_user['last_name']);
                    $emailSent = sendVerificationEmail($email, $full_name, $new_verification_code);
                    
                    if ($emailSent && isset($emailSent['success']) && $emailSent['success']) {
                        $success_message = "A new verification code has been sent to your email address.";
                        error_log("New verification code sent successfully to: $email");
                    } else {
                        $error_message = "Failed to send new verification code. Your new code is: <strong>" . $new_verification_code . "</strong>";
                        error_log("Failed to send email, showing code directly: $new_verification_code");
                    }
                } else {
                    $error_message = "Email service unavailable. Your new code is: <strong>" . $new_verification_code . "</strong>";
                    error_log("Email function not available, showing code directly: $new_verification_code");
                }
            } else {
                throw new Exception("Failed to update verification code: " . $update_stmt->error);
            }
            
            $update_stmt->close();
        } else {
            $error_message = "Verification session expired. Please register again.";
            unset($_SESSION['verification_email']);
            unset($_SESSION['verification_code_sent']);
            error_log("Temp user not found for resend request: $email");
        }
        
        $check_stmt->close();
        
    } catch (Exception $e) {
        error_log("Resend code error: " . $e->getMessage());
        error_log("Resend error trace: " . $e->getTraceAsString());
        $error_message = "System error occurred during resend: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Sweet Treats</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/register.css">
    <style>
        .verification-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .code-input {
            font-size: 24px;
            letter-spacing: 8px;
            text-align: center;
            padding: 15px;
            margin: 20px 0;
            border: 2px solid #ddd;
            border-radius: 8px;
            width: 100%;
            font-family: monospace;
        }
        
        .code-input:focus {
            border-color: #FF69B4;
            outline: none;
        }
        
        .instructions {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #495057;
        }
        
        .resend-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .resend-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .resend-btn:hover {
            background: #5a6268;
        }
        
        .success-redirect {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .debug-info {
            background: #f0f0f0;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="logo">
            SWEET TREATS 
            <i class="fas fa-ice-cream"></i>
        </div>
        
        <h2 style="text-align: center; color: #333; margin-bottom: 20px;">Verify Your Email</h2>
        
        <div class="instructions">
            <i class="fas fa-envelope" style="color: #FF69B4;"></i>
            <strong>Check your email!</strong><br>
            We've sent a 6-digit verification code to:<br>
            <strong><?php echo htmlspecialchars($email); ?></strong>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
                <?php if (strpos($success_message, 'verified successfully') !== false): ?>
                    <div class="success-redirect">
                        <i class="fas fa-check-circle"></i>
                        Redirecting you to the homepage in 3 seconds...
                    </div>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'index.php';
                        }, 3000);
                    </script>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($success_message) || strpos($success_message, 'verified successfully') === false): ?>
            <form method="post" action="">
                <label for="verification_code" style="display: block; margin-bottom: 10px; font-weight: bold;">
                    Enter Verification Code:
                </label>
                <input 
                    type="text" 
                    id="verification_code"
                    name="verification_code" 
                    class="code-input"
                    placeholder="000000"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required
                    autocomplete="off"
                    autofocus
                >
                
                <input type="submit" name="verify_code" value="Verify Email" style="width: 100%; padding: 12px; background: #FF69B4; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;" onclick="console.log('Verify button clicked');">
            </form>
            
            <!-- Debug form submission -->
            <script>
                document.querySelector('form').addEventListener('submit', function(e) {
                    console.log('Form is being submitted');
                    console.log('Code entered:', document.getElementById('verification_code').value);
                });
            </script>
            
            <div class="resend-section">
                <p style="color: #666; font-size: 14px; margin-bottom: 10px;">
                    Didn't receive the code?
                </p>
                <form method="post" action="" style="display: inline;">
                    <button type="submit" name="resend_code" class="resend-btn">
                        <i class="fas fa-redo"></i> Resend Code
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="home-link" style="text-align: center; margin-top: 30px;">
            <a href="register.php">← Back to Registration</a>
        </div>
        
        <?php if (isset($_GET['debug'])): ?>
            <div class="debug-info">
                <h3>Debug Information</h3>
                <p>Session Email: <?php echo isset($_SESSION['verification_email']) ? $_SESSION['verification_email'] : 'Not set'; ?></p>
                <p>Current Time: <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-focus on the code input
        document.getElementById('verification_code')?.focus();
        
        // Auto-submit when 6 digits are entered (disabled for debugging)
        document.getElementById('verification_code')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            e.target.value = value;
            
            // Commented out auto-submit for now - let user click submit button
            // if (value.length === 6) {
            //     setTimeout(function() {
            //         e.target.form.submit();
            //     }, 500);
            // }
        });
        
        // Only allow numbers
        document.getElementById('verification_code')?.addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>