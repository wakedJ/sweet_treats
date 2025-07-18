<?php
// Include database connection and admin check
require_once './includes/check_admin.php';
require_once '../includes/db.php';

// Function to get current delivery rules from database
function getDeliveryRules($conn) {
    $query = "SELECT * FROM delivery_rules ORDER BY id DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        return defaultDeliveryRules();
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Failed to execute statement: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return defaultDeliveryRules();
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    } else {
        // If no rules exist, insert default values and return them
        $insertQuery = "INSERT INTO delivery_rules 
                      (min_order_for_free_delivery, standard_delivery_fee) 
                      VALUES (?, ?)";
        
        $stmt = mysqli_prepare($conn, $insertQuery);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . mysqli_error($conn));
            return defaultDeliveryRules();
        }
        
        // Default values
        $defaultMin = 50.00;
        $defaultFee = 5.00;
        
        mysqli_stmt_bind_param($stmt, "dd", $defaultMin, $defaultFee);
        
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if (!$success) {
            error_log("Failed to insert default delivery rules: " . mysqli_error($conn));
            return defaultDeliveryRules();
        }
        
        // Get the inserted default values
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . mysqli_error($conn));
            return defaultDeliveryRules();
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Failed to execute statement: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return defaultDeliveryRules();
        }
        
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        } else {
            return defaultDeliveryRules();
        }
    }
}

// Function to provide default delivery rules when DB fails
function defaultDeliveryRules() {
    return [
        'min_order_for_free_delivery' => 50.00,
        'standard_delivery_fee' => 5.00
    ];
}

// Function to get history log entries
function getDeliveryRulesHistory($conn) {
    $query = "SELECT * FROM delivery_rules ORDER BY updated_at DESC LIMIT 10";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        return [];
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Failed to execute statement: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    if (!$result) {
        error_log("Failed to retrieve delivery history: " . mysqli_error($conn));
        return [];
    }
    
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    
    return $history;
}

// Verify CSRF token
function verifyCSRFToken() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Security validation failed. Please refresh the page and try again.'
        ]);
        exit;
    }
}

// Regenerate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Handle delivery rules form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_delivery') {
    header('Content-Type: application/json');
    
    verifyCSRFToken();
    
    // Validate form data
    $minOrder = isset($_POST['min_order_amount']) ? 
        filter_var($_POST['min_order_amount'], FILTER_VALIDATE_FLOAT) : false;
    $deliveryFee = isset($_POST['delivery_fee']) ? 
        filter_var($_POST['delivery_fee'], FILTER_VALIDATE_FLOAT) : false;
    
    // Validate required fields
    if ($minOrder === false || $deliveryFee === false) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid input values for minimum order amount or delivery fee'
        ]);
        exit;
    }
    
    // Additional validation
    if ($minOrder < 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Minimum order amount cannot be negative'
        ]);
        exit;
    }
    
    if ($deliveryFee < 0 || $deliveryFee > 50) {
        echo json_encode([
            'success' => false, 
            'message' => 'Delivery fee must be between 0 and 50'
        ]);
        exit;
    }
    
    // Insert new delivery rules
    $query = "INSERT INTO delivery_rules 
              (min_order_for_free_delivery, standard_delivery_fee) 
              VALUES (?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        echo json_encode([
            'success' => false, 
            'message' => 'Database error. Please try again later.'
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "dd", $minOrder, $deliveryFee);
    
    $success = mysqli_stmt_execute($stmt);
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Delivery rules updated successfully!'
        ]);
    } else {
        error_log("Failed to update delivery rules: " . $error);
        echo json_encode([
            'success' => false, 
            'message' => 'Error updating delivery rules. Please try again.'
        ]);
    }
    
    exit;
}

// Get current delivery rules and history
try {
    $deliveryRules = getDeliveryRules($conn);
    $historyLog = getDeliveryRulesHistory($conn);
} catch (Exception $e) {
    error_log("Error retrieving data: " . $e->getMessage());
    $deliveryRules = defaultDeliveryRules();
    $historyLog = [];
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Helper function to safely output HTML
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Settings</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'">
</head>
<body>
    <div class="admin-container">
        <h2>Delivery Settings</h2>
        <div class="status-message success" id="success-message" style="display: none;"></div>
        <div class="status-message error" id="error-message" style="display: none;"></div>
        
        <div class="delivery-form-container">
            <form id="delivery-form">
                <div class="form-row">
                    <label for="min-order-amount">Minimum Order for Free Delivery ($)</label>
                    <input type="number" id="min-order-amount" name="min_order_amount" 
                           value="<?php echo e($deliveryRules['min_order_for_free_delivery']); ?>" 
                           placeholder="Enter amount for free delivery (e.g., 50)" required min="0" step="0.01">
                    <div class="error-message" id="min-order-error"></div>
                </div>
                
                <div class="form-row">
                    <label for="delivery-fee">Standard Delivery Fee ($)</label>
                    <input type="number" id="delivery-fee" name="delivery_fee" 
                           value="<?php echo e($deliveryRules['standard_delivery_fee']); ?>" 
                           placeholder="Enter delivery fee" required min="0" max="50" step="0.01">
                    <div class="error-message" id="delivery-fee-error"></div>
                </div>
                
                <div class="form-buttons">
                    <input type="hidden" name="action" value="update_delivery">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <button type="submit" class="btn-primary">Save Rules</button>
                    <button type="button" class="btn-secondary" id="delivery-reset">Reset</button>
                </div>
            </form>
        </div>
        
        <div class="history-log">
            <h4>Change History</h4>
            <div class="log-container">
                <?php if (empty($historyLog)): ?>
                    <div class="log-item">No history available.</div>
                <?php else: ?>
                    <?php foreach ($historyLog as $log): ?>
                        <div class="log-item">
                            <span class="log-date"><?php echo e(date('Y-m-d H:i', strtotime($log['updated_at']))); ?></span>
                            <span class="log-action">
                                Updated rules: Free delivery threshold $<?php echo e(number_format($log['min_order_for_free_delivery'], 2)); ?>, 
                                Delivery fee $<?php echo e(number_format($log['standard_delivery_fee'], 2)); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show/hide message with fade effect
        function showMessage(type, message, duration = 5000) {
            const messageElement = document.getElementById(type + '-message');
            messageElement.textContent = message;
            messageElement.style.display = 'block';
            
            setTimeout(function() {
                messageElement.style.opacity = '0';
                setTimeout(function() {
                    messageElement.style.display = 'none';
                    messageElement.style.opacity = '1';
                }, 500);
            }, duration);
        }
        
        // Clear all error messages
        function clearErrorMessages() {
            document.querySelectorAll('.error-message').forEach(function(element) {
                element.textContent = '';
            });
        }
        
        // Delivery form validation
        const deliveryForm = document.getElementById('delivery-form');
        if (deliveryForm) {
            deliveryForm.addEventListener('submit', function(e) {
                e.preventDefault();
                clearErrorMessages();
                
                // Validate minimum order amount
                const minOrderAmount = document.getElementById('min-order-amount').value;
                const minOrderError = document.getElementById('min-order-error');
                if (isNaN(minOrderAmount) || parseFloat(minOrderAmount) < 0) {
                    minOrderError.textContent = 'Please enter a valid minimum order amount (0 or greater)';
                    return;
                }
                
                // Validate delivery fee
                const deliveryFee = document.getElementById('delivery-fee').value;
                const deliveryFeeError = document.getElementById('delivery-fee-error');
                if (isNaN(deliveryFee) || parseFloat(deliveryFee) < 0 || parseFloat(deliveryFee) > 50) {
                    deliveryFeeError.textContent = 'Delivery fee must be between 0 and 50';
                    return;
                }
                
                // Collect form data and submit via AJAX
                const formData = new FormData(deliveryForm);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', data.message);
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('error', 'An unexpected error occurred. Please try again.');
                });
            });
            
            // Handle delivery form reset
            document.getElementById('delivery-reset').addEventListener('click', function() {
                deliveryForm.reset();
                clearErrorMessages();
            });
        }
    });
    </script>
</body>
</html>