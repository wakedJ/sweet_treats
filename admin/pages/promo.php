<?php
ob_start();
error_reporting(0); 
// Include database connection and admin check
require_once './includes/check_admin.php';
require_once '../includes/db.php';

// Make sure no output is sent before headers


// Function to get all promo codes
function getPromoCodes($conn) {
    $query = "SELECT * FROM promo_codes ORDER BY end_date DESC";
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
        error_log("Failed to retrieve promo codes: " . mysqli_error($conn));
        return [];
    }
    
    $promoCodes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $promoCodes[] = $row;
    }
    
    return $promoCodes;
}

// Function to get a single promo code by ID
function getPromoCode($conn, $promoId) {
    $query = "SELECT * FROM promo_codes WHERE promo_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $promoId);
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Failed to execute statement: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }
    
    return mysqli_fetch_assoc($result);
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

// Handle promo code form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_promo') {
    // Ensure clean output buffer
    ob_clean();
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    try {
        verifyCSRFToken();
        
        // Validate promo code data
        $code = isset($_POST['promo_code']) ? trim($_POST['promo_code']) : '';
        $discountType = isset($_POST['discount_type']) ? $_POST['discount_type'] : '';
        $discountValue = isset($_POST['discount_value']) ? 
            filter_var($_POST['discount_value'], FILTER_VALIDATE_FLOAT) : false;
        $minOrderValue = isset($_POST['min_order_value']) ? 
            filter_var($_POST['min_order_value'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) : 0;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : '';
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : '';
        $maxUses = isset($_POST['max_uses']) && $_POST['max_uses'] !== '' ? 
            filter_var($_POST['max_uses'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Basic validation
        if (empty($code) || !in_array($discountType, ['percentage', 'fixed']) || 
            $discountValue === false || empty($startDate) || empty($endDate)) {
            echo json_encode([
                'success' => false,
                'message' => 'Please fill all required fields with valid values'
            ]);
            exit;
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $code)) {
            echo json_encode([
                'success' => false,
                'message' => 'Promo code can only contain letters, numbers, underscores and hyphens'
            ]);
            exit;
        }
        
        if ($discountType === 'percentage' && ($discountValue <= 0 || $discountValue > 100)) {
            echo json_encode([
                'success' => false,
                'message' => 'Percentage discount must be between 0 and 100'
            ]);
            exit;
        }
        
        if ($discountValue <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Discount value must be greater than zero'
            ]);
            exit;
        }
        
        // Validate dates
        $startDateObj = DateTime::createFromFormat('Y-m-d', $startDate);
        $endDateObj = DateTime::createFromFormat('Y-m-d', $endDate);
        
        if (!$startDateObj || !$endDateObj) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date format. Please use YYYY-MM-DD'
            ]);
            exit;
        }
        
        if ($endDateObj < $startDateObj) {
            echo json_encode([
                'success' => false,
                'message' => 'End date must be after start date'
            ]);
            exit;
        }
        
        $startDateFormatted = $startDateObj->format('Y-m-d 00:00:00');
        $endDateFormatted = $endDateObj->format('Y-m-d 23:59:59');
        
        $promoId = isset($_POST['promo_id']) && !empty($_POST['promo_id']) ? 
            filter_var($_POST['promo_id'], FILTER_VALIDATE_INT) : null;
        
        if ($promoId) {
            // Update existing promo code
            $query = "UPDATE promo_codes SET 
                      code = ?, discount_type = ?, discount_value = ?,
                      minimum_order_value = ?, start_date = ?, end_date = ?,
                      max_uses = ?, is_active = ?
                      WHERE promo_id = ?";
                      
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . mysqli_error($conn));
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error. Please try again later.'
                ]);
                exit;
            }
            
            mysqli_stmt_bind_param($stmt, "ssddssiii", 
                $code, $discountType, $discountValue, $minOrderValue, 
                $startDateFormatted, $endDateFormatted, $maxUses, $isActive, $promoId);
        } else {
            // Check if code already exists
            $checkQuery = "SELECT code FROM promo_codes WHERE code = ?";
            $checkStmt = mysqli_prepare($conn, $checkQuery);
            if (!$checkStmt) {
                error_log("Failed to prepare statement: " . mysqli_error($conn));
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error. Please try again later.'
                ]);
                exit;
            }
            
            mysqli_stmt_bind_param($checkStmt, "s", $code);
            
            if (!mysqli_stmt_execute($checkStmt)) {
                error_log("Failed to execute statement: " . mysqli_stmt_error($checkStmt));
                mysqli_stmt_close($checkStmt);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error. Please try again later.'
                ]);
                exit;
            }
            
            $checkResult = mysqli_stmt_get_result($checkStmt);
            mysqli_stmt_close($checkStmt);
            
            if (mysqli_num_rows($checkResult) > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Promo code already exists'
                ]);
                exit;
            }
            
            // Insert new promo code
            $query = "INSERT INTO promo_codes 
                      (code, discount_type, discount_value, minimum_order_value,
                       start_date, end_date, max_uses, is_active, current_uses)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
                      
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . mysqli_error($conn));
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error. Please try again later.'
                ]);
                exit;
            }
            
            mysqli_stmt_bind_param($stmt, "ssddssii", 
                $code, $discountType, $discountValue, $minOrderValue, 
                $startDateFormatted, $endDateFormatted, $maxUses, $isActive);
        }
        
        $success = mysqli_stmt_execute($stmt);
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        
        if ($success) {
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Promo code ' . ($promoId ? 'updated' : 'created') . ' successfully!'
            ]);
            ob_end_flush();
            exit;
        } else {
            error_log("Failed to save promo code: " . $error);
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Error saving promo code. Please try again.'
            ]);
            ob_end_flush();
            exit;
        }
    } catch (Exception $e) {
        error_log("Exception in promo code save: " . $e->getMessage());
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'An unexpected error occurred. Please try again. Details: ' . $e->getMessage()
        ]);
        ob_end_flush();
            exit;
    }
    exit;
}

// Handle promo code deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_promo') {
    // Ensure clean output buffer
    ob_clean();

    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
        

    try {
        verifyCSRFToken();

        $promoId = isset($_POST['promo_id']) ? 
            filter_var($_POST['promo_id'], FILTER_VALIDATE_INT) : false;

        if (!$promoId) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid promo ID.'
            ]);
            exit;
        }

        $query = "DELETE FROM promo_codes WHERE promo_id = ?";
        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) {
            error_log("Failed to prepare statement: " . mysqli_error($conn));
            echo json_encode([
                'success' => false,
                'message' => 'Database error. Please try again.'
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "i", $promoId);
        $success = mysqli_stmt_execute($stmt);
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Promo code deleted successfully.'
            ]);
        } else {
            error_log("Failed to delete promo code: " . $error);
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting promo code. Please try again.'
            ]);
        }
    } catch (Exception $e) {
        error_log("Exception in promo code delete: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An unexpected error occurred. Please try again.'
        ]);
    }
    exit;
}

        

// Get promo codes
try {
    $promoCodes = getPromoCodes($conn);
} catch (Exception $e) {
    error_log("Error retrieving promo codes: " . $e->getMessage());
    $promoCodes = [];
}

// Get promo code for editing if ID is provided
$editPromo = null;
if (isset($_GET['edit_promo']) && !empty($_GET['edit_promo'])) {
    $editPromoId = filter_var($_GET['edit_promo'], FILTER_VALIDATE_INT);
    if ($editPromoId) {
        $editPromo = getPromoCode($conn, $editPromoId);
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Helper function to safely output HTML
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<?php
// Handle promo code status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    // Ensure clean output buffer
    ob_clean();
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        verifyCSRFToken();
        
        $promoId = isset($_POST['promo_id']) ? 
            filter_var($_POST['promo_id'], FILTER_VALIDATE_INT) : false;
        $currentStatus = isset($_POST['current_status']) ? $_POST['current_status'] : '';
        
        if (!$promoId) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid promo ID.'
            ]);
            exit;
        }
        
        if (!in_array($currentStatus, ['active', 'inactive'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid current status.'
            ]);
            exit;
        }
        
        // Toggle the status (if currently active, make inactive and vice versa)
        $newStatus = ($currentStatus === 'active') ? 0 : 1;
        $statusText = $newStatus ? 'active' : 'inactive';
        
        $query = "UPDATE promo_codes SET is_active = ? WHERE promo_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            error_log("Failed to prepare statement: " . mysqli_error($conn));
            echo json_encode([
                'success' => false,
                'message' => 'Database error. Please try again.'
            ]);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $newStatus, $promoId);
        $success = mysqli_stmt_execute($stmt);
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Promo code status updated to ' . $statusText . ' successfully.',
                'new_status' => $statusText
            ]);
        } else {
            error_log("Failed to update promo code status: " . $error);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating promo code status. Please try again.'
            ]);
        }
    } catch (Exception $e) {
        error_log("Exception in promo code status toggle: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An unexpected error occurred. Please try again.'
        ]);
    }
    exit;
}
?>

    <div class="table-container">
        <h2>Promo Code Management</h2>
        <div class="status-message success" id="success-message" style="display: none;"></div>
        <div class="status-message error" id="error-message" style="display: none;"></div>
        
        <div class="promo-list">
            <h4>Active Promo Codes</h4>
            <?php if (empty($promoCodes)): ?>
                <p>No promo codes available.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Min. Order</th>
                            <th>Validity</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promoCodes as $promo): ?>
                            <tr>
                                <td><?php echo e($promo['code']); ?></td>
                                <td>
                                    <?php if ($promo['discount_type'] === 'percentage'): ?>
                                        <?php echo e(number_format($promo['discount_value'], 0)); ?>%
                                    <?php else: ?>
                                        $<?php echo number_format($promo['discount_value'], 2); ?>
                                    <?php endif; ?>

                                </td>
                                <td>
                                    $<?php echo e(number_format($promo['minimum_order_value'], 2)); ?>
                                </td>
                                <td>
                                    <?php echo e(date('Y-m-d', strtotime($promo['start_date']))); ?> to 
                                    <?php echo e(date('Y-m-d', strtotime($promo['end_date']))); ?>
                                </td>
                                <td>
                                    <?php echo e($promo['current_uses']); ?> 
                                    <?php if ($promo['max_uses']): ?>/ <?php echo e($promo['max_uses']); ?><?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" 
                                            class="status-toggle <?php echo $promo['is_active'] ? 'active' : 'inactive'; ?>" 
                                            data-id="<?php echo e($promo['promo_id']); ?>" 
                                            data-status="<?php echo $promo['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $promo['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </button>
                                    <span class="status-indicator badge badge-<?php echo $promo['is_active'] ? 'active' : 'inactive'; ?>" style="display: none;">
                                        <?php echo $promo['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <div class="action-icons">
                                        
                                        <div class="action-icon delete-icon" data-id="<?php echo e($promo['promo_id']); ?>">🗑️</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>    
                </table>
            <?php endif; ?>
            
            <button type="button" class="btn-primary" id="add-promo-btn" style="margin-top: 20px;">Add New Promo Code</button>
        </div>
        
        <div class="promo-form-container" id="promo-form-container" style="<?php echo $editPromo || isset($_GET['add_promo']) ? '' : 'display: none;'; ?>">
            <h4><?php echo $editPromo ? 'Edit Promo Code' : 'Add New Promo Code'; ?></h4>
            <form id="promo-form">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="promo-code">Promo Code</label>
                        <input type="text" id="promo-code" name="promo_code" 
                               value="<?php echo $editPromo ? e($editPromo['code']) : ''; ?>" 
                               placeholder="e.g., SUMMER25" required pattern="[a-zA-Z0-9_-]+" 
                               title="Only letters, numbers, underscores and hyphens are allowed">
                        <div class="error-message" id="promo-code-error"></div>
                    </div>
                    
                    <div class="form-field">
                        <label for="discount-type">Discount Type</label>
                        <select id="discount-type" name="discount_type" required>
                            <option value="">Select type</option>
                            <option value="percentage" <?php echo $editPromo && $editPromo['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                            <option value="fixed" <?php echo $editPromo && $editPromo['discount_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount ($)</option>
                        </select>
                        <div class="error-message" id="discount-type-error"></div>
                    </div>
                    
                    <div class="form-field">
                        <label for="discount-value">Discount Value</label>
                        <input type="number" id="discount-value" name="discount_value" 
                               value="<?php echo $editPromo ? e($editPromo['discount_value']) : ''; ?>" 
                               placeholder="Enter discount value" required min="0.01" step="0.01">
                        <div class="error-message" id="discount-value-error"></div>
                    </div>
                    
                    <div class="form-field">
                        <label for="min-order-value">Minimum Order Value ($)</label>
                        <input type="number" id="min-order-value" name="min_order_value" 
                               value="<?php echo $editPromo ? e($editPromo['minimum_order_value']) : '0.00'; ?>" 
                               placeholder="0" min="0" step="0.01">
                        <div class="error-message" id="min-order-value-error"></div>
                    </div>
                    
                    <div class="form-field">
                        <label for="start-date">Start Date</label>
                        <input type="date" id="start-date" name="start_date" 
                               value="<?php echo $editPromo ? date('Y-m-d', strtotime($editPromo['start_date'])) : date('Y-m-d'); ?>" 
                               required>
                        <div class="error-message" id="start-date-error"></div>
                    </div>
                    
                    <div class="form-field">
                        <label for="end-date">End Date</label>
                        <input type="date" id="end-date" name="end_date" 
                               value="<?php echo $editPromo ? date('Y-m-d', strtotime($editPromo['end_date'])) : date('Y-m-d', strtotime('+30 days')); ?>" 
                               required>
                        <div class="error-message" id="end-date-error"></div>
                    </div>
                    
                    <div class="form-field">
                        <label for="max-uses">Maximum Uses</label>
                        <input type="number" id="max-uses" name="max_uses" 
                               value="<?php echo $editPromo && $editPromo['max_uses'] ? e($editPromo['max_uses']) : ''; ?>" 
                               placeholder="Leave empty for unlimited" min="1" step="1">
                        <div class="error-message" id="max-uses-error"></div>
                    </div>
                    
                    <div class="form-field">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is-active" name="is_active" 
                                   <?php echo $editPromo && $editPromo['is_active'] ? 'checked' : ''; ?>>
                            Active
                        </label>
                    </div>
                </div>
                
                <div class="form-buttons">
                    <?php if ($editPromo): ?>
                        <input type="hidden" name="promo_id" value="<?php echo e($editPromo['promo_id']); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="action" value="save_promo">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <button type="submit" class="btn-primary">Save Promo Code</button>
                    <a href="?" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal" id="delete-modal">
        <div class="modal-content">
            <h4>Confirm Deletion</h4>
            <p>Are you sure you want to delete this promo code? This action cannot be undone.</p>
            <div class="modal-actions">
                <form id="delete-promo-form">
                    <input type="hidden" name="action" value="delete_promo">
                    <input type="hidden" name="promo_id" id="delete-promo-id">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <button type="submit" class="btn-danger">Delete</button>
                    <button type="button" class="btn-secondary" id="cancel-delete">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script>document.addEventListener('DOMContentLoaded', function() {
    // Show/hide message with fade effect
    function showMessage(type, message, duration = 5000) {
        const messageElement = document.getElementById(type + '-message');
        if (!messageElement) {
            console.error('Message element not found:', type + '-message');
            return;
        }
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
    
    // Promo code form validation
    const promoForm = document.getElementById('promo-form');
    if (promoForm) {
        promoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            clearErrorMessages();
            
            let hasError = false;
            
            // Validate promo code - FIXED REGEX
            const promoCode = document.getElementById('promo-code').value.trim();
            const promoCodeError = document.getElementById('promo-code-error');
            if (!promoCode) {
                promoCodeError.textContent = 'Please enter a promo code';
                hasError = true;
            } else if (!/^[a-zA-Z0-9_\-]+$/.test(promoCode)) {
                promoCodeError.textContent = 'Promo code can only contain letters, numbers, underscores and hyphens';
                hasError = true;
            }
            
            // Validate discount type
            const discountType = document.getElementById('discount-type').value;
            const discountTypeError = document.getElementById('discount-type-error');
            if (!discountType) {
                discountTypeError.textContent = 'Please select a discount type';
                hasError = true;
            }
            
            // Validate discount value
            const discountValue = document.getElementById('discount-value').value;
            const discountValueError = document.getElementById('discount-value-error');
            if (!discountValue || isNaN(discountValue) || parseFloat(discountValue) <= 0) {
                discountValueError.textContent = 'Please enter a valid discount value greater than zero';
                hasError = true;
            } else if (discountType === 'percentage' && parseFloat(discountValue) > 100) {
                discountValueError.textContent = 'Percentage discount cannot exceed 100%';
                hasError = true;
            }
            
            // Validate dates
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            const startDateError = document.getElementById('start-date-error');
            const endDateError = document.getElementById('end-date-error');
            
            if (!startDate) {
                startDateError.textContent = 'Please select a start date';
                hasError = true;
            }
            
            if (!endDate) {
                endDateError.textContent = 'Please select an end date';
                hasError = true;
            }
            
            if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                endDateError.textContent = 'End date must be after start date';
                hasError = true;
            }
            
            if (hasError) {
                return;
            }
            
            // Show loading indicator
            showMessage('success', 'Processing your request...');
            
            // Collect form data and submit via AJAX
            const formData = new FormData(promoForm);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                // IMPROVED: First get the response text
                return response.text().then(text => {
                    // Try to extract JSON from the response
                    let jsonData = null;
                    let isValidJson = false;
                    
                    // Look for JSON at the end of the response (common pattern)
                    const jsonMatch = text.match(/\{[^}]*"success"[^}]*\}$/);
                    if (jsonMatch) {
                        try {
                            jsonData = JSON.parse(jsonMatch[0]);
                            isValidJson = true;
                        } catch (e) {
                            console.warn('Failed to parse extracted JSON:', e);
                        }
                    }
                    
                    // If no JSON found at end, try parsing the entire response
                    if (!isValidJson) {
                        try {
                            jsonData = JSON.parse(text);
                            isValidJson = true;
                        } catch (e) {
                            // Not valid JSON, that's okay
                        }
                    }
                    
                    return {
                        ok: response.ok,
                        isJson: isValidJson,
                        data: jsonData,
                        text: text,
                        statusCode: response.status
                    };
                });
            })
            .then(result => {
                if (!result.ok) {
                    if (result.isJson) {
                        showMessage('error', result.data.message || 'Server returned an error');
                    } else {
                        console.error('Non-JSON response:', result.text);
                        showMessage('error', `Server error (${result.statusCode}). Please check server logs.`);
                    }
                    return;
                }
                
                if (result.isJson && result.data) {
                    if (result.data.success) {
                        showMessage('success', result.data.message);
                        
                        // Redirect after successful submission
                        setTimeout(() => {
                            window.location.href = window.location.pathname + window.location.search;
                        }, 1500);
                    } else {
                        showMessage('error', result.data.message || 'An error occurred while saving the promo code.');
                    }
                } else {
                    // IMPROVED: Check if the operation was likely successful based on content
                    if (result.text.includes('"success":true') || result.text.includes('successfully')) {
                        showMessage('success', 'Promo code saved successfully!');
                        setTimeout(() => {
                            window.location.href = window.location.pathname + window.location.search;
                        }, 1500);
                    } else {
                        console.error('Unexpected response format:', result.text);
                        showMessage('error', 'Unexpected server response format. Operation may have completed - please refresh to check.');
                    }
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showMessage('error', 'Network or processing error: ' + error.message);
            });
        });
    }
    
    // Handle "Add New Promo Code" button
    const addPromoBtn = document.getElementById('add-promo-btn');
    if (addPromoBtn) {
        addPromoBtn.addEventListener('click', function() {
            document.getElementById('promo-form-container').style.display = 'block';
            document.getElementById('promo-code').focus();
            // Scroll to the form
            document.getElementById('promo-form-container').scrollIntoView({ behavior: 'smooth' });
        });
    }
    
    // Handle delete promo code buttons
    const deleteButtons = document.querySelectorAll('.delete-icon');
    const deleteModal = document.getElementById('delete-modal');
    const cancelDelete = document.getElementById('cancel-delete');
    
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const promoId = this.getAttribute('data-id');
                if (promoId && document.getElementById('delete-promo-id')) {
                    document.getElementById('delete-promo-id').value = promoId;
                    if (deleteModal) {
                        deleteModal.style.display = 'flex';
                    }
                }
            });
        });
    }
    
    if (cancelDelete) {
        cancelDelete.addEventListener('click', function() {
            if (deleteModal) {
                deleteModal.style.display = 'none';
            }
        });
    }
    
    // Handle delete form submission
    const deleteForm = document.getElementById('delete-promo-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(deleteForm);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                // IMPROVED: Same logic as above
                return response.text().then(text => {
                    let jsonData = null;
                    let isValidJson = false;
                    
                    const jsonMatch = text.match(/\{[^}]*"success"[^}]*\}$/);
                    if (jsonMatch) {
                        try {
                            jsonData = JSON.parse(jsonMatch[0]);
                            isValidJson = true;
                        } catch (e) {
                            console.warn('Failed to parse extracted JSON:', e);
                        }
                    }
                    
                    if (!isValidJson) {
                        try {
                            jsonData = JSON.parse(text);
                            isValidJson = true;
                        } catch (e) {
                            // Not valid JSON
                        }
                    }
                    
                    return {
                        ok: response.ok,
                        isJson: isValidJson,
                        data: jsonData,
                        text: text,
                        statusCode: response.status
                    };
                });
            })
            .then(result => {
                if (!result.ok) {
                    if (result.isJson) {
                        showMessage('error', result.data.message || 'Server returned an error');
                    } else {
                        console.error('Non-JSON response:', result.text);
                        showMessage('error', `Server error (${result.statusCode}). Please check server logs.`);
                    }
                    if (deleteModal) {
                        deleteModal.style.display = 'none';
                    }
                    return;
                }
                
                if (result.isJson && result.data) {
                    if (result.data.success) {
                        showMessage('success', result.data.message);
                        // Reload page after successful delete
                        setTimeout(() => {
                            window.location.href = window.location.pathname + window.location.search;
                        }, 1500);
                    } else {
                        showMessage('error', result.data.message || 'An error occurred while deleting the promo code.');
                    }
                } else {
                    if (result.text.includes('"success":true') || result.text.includes('deleted')) {
                        showMessage('success', 'Promo code deleted successfully!');
                        setTimeout(() => {
                            window.location.href = window.location.pathname + window.location.search;
                        }, 1500);
                    } else {
                        console.error('Unexpected response format:', result.text);
                        showMessage('error', 'Unexpected server response format. Operation may have completed - please refresh to check.');
                    }
                }
                
                if (deleteModal) {
                    deleteModal.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showMessage('error', 'Network or processing error: ' + error.message);
                if (deleteModal) {
                    deleteModal.style.display = 'none';
                }
            });
        });
    }
    
    // Close modal when clicking outside of it
    if (deleteModal) {
        window.addEventListener('click', function(event) {
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
        });
    }
    
    // Discount type change handler
    const discountType = document.getElementById('discount-type');
    const discountValue = document.getElementById('discount-value');
    
    if (discountType && discountValue) {
        discountType.addEventListener('change', function() {
            const type = this.value;
            if (type === 'percentage') {
                discountValue.setAttribute('max', '100');
                if (parseFloat(discountValue.value) > 100) {
                    discountValue.value = '100';
                }
            } else {
                discountValue.removeAttribute('max');
            }
        });
    }
    
    // Handle toggle active/inactive status
    // Handle toggle active/inactive status
const toggleButtons = document.querySelectorAll('.toggle-status-btn, .status-toggle');
if (toggleButtons.length > 0) {
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const promoId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            
            if (!promoId) {
                console.error('No promo ID found');
                return;
            }
            
            // Show loading state
            const originalText = this.textContent;
            this.textContent = 'Updating...';
            this.disabled = true;
            
            // Get CSRF token - try multiple methods to find it
            let csrfToken = null;
            
            // Method 1: Try to get from the promo form
            const promoFormToken = document.querySelector('#promo-form input[name="csrf_token"]');
            if (promoFormToken) {
                csrfToken = promoFormToken.value;
            }
            
            // Method 2: Try to get from the delete form
            if (!csrfToken) {
                const deleteFormToken = document.querySelector('#delete-promo-form input[name="csrf_token"]');
                if (deleteFormToken) {
                    csrfToken = deleteFormToken.value;
                }
            }
            
            // Method 3: Try to get from any csrf_token input
            if (!csrfToken) {
                const anyToken = document.querySelector('input[name="csrf_token"]');
                if (anyToken) {
                    csrfToken = anyToken.value;
                }
            }
            
            if (!csrfToken) {
                console.error('CSRF token not found');
                showMessage('error', 'Security token not found. Please refresh the page.');
                this.textContent = originalText;
                this.disabled = false;
                return;
            }
            
            // Create form data for the toggle request
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('promo_id', promoId);
            formData.append('current_status', currentStatus);
            formData.append('csrf_token', csrfToken);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                return response.text().then(text => {
                    let jsonData = null;
                    let isValidJson = false;
                    
                    const jsonMatch = text.match(/\{[^}]*"success"[^}]*\}$/);
                    if (jsonMatch) {
                        try {
                            jsonData = JSON.parse(jsonMatch[0]);
                            isValidJson = true;
                        } catch (e) {
                            console.warn('Failed to parse extracted JSON:', e);
                        }
                    }
                    
                    if (!isValidJson) {
                        try {
                            jsonData = JSON.parse(text);
                            isValidJson = true;
                        } catch (e) {
                            // Not valid JSON
                        }
                    }
                    
                    return {
                        ok: response.ok,
                        isJson: isValidJson,
                        data: jsonData,
                        text: text,
                        statusCode: response.status
                    };
                });
            })
            .then(result => {
                // Reset button state
                this.textContent = originalText;
                this.disabled = false;
                
                if (!result.ok) {
                    if (result.isJson) {
                        showMessage('error', result.data.message || 'Server returned an error');
                    } else {
                        console.error('Non-JSON response:', result.text);
                        showMessage('error', `Server error (${result.statusCode}). Please check server logs.`);
                    }
                    return;
                }
                
                if (result.isJson && result.data) {
                    if (result.data.success) {
                        showMessage('success', result.data.message || 'Status updated successfully');
                        
                        // Update button appearance and data
                        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                        this.setAttribute('data-status', newStatus);
                        
                        // Update button text and styling
                        if (newStatus === 'active') {
                            this.textContent = 'Active';
                            this.className = this.className.replace('inactive', 'active');
                        } else {
                            this.textContent = 'Inactive';
                            this.className = this.className.replace('active', 'inactive');
                        }
                        
                        // Optional: Update any status indicators in the row
                        const statusIndicator = this.closest('tr')?.querySelector('.status-indicator');
                        if (statusIndicator) {
                            statusIndicator.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                            statusIndicator.className = statusIndicator.className.replace(/(active|inactive)/, newStatus);
                        }
                    } else {
                        showMessage('error', result.data.message || 'An error occurred while updating the status.');
                    }
                } else {
                    if (result.text.includes('"success":true') || result.text.includes('updated')) {
                        showMessage('success', 'Status updated successfully!');
                        
                        // Update button state even if JSON parsing failed
                        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                        this.setAttribute('data-status', newStatus);
                        
                        if (newStatus === 'active') {
                            this.textContent = 'Active';
                            this.className = this.className.replace('inactive', 'active');
                        } else {
                            this.textContent = 'Inactive';
                            this.className = this.className.replace('active', 'inactive');
                        }
                    } else {
                        console.error('Unexpected response format:', result.text);
                        showMessage('error', 'Unexpected server response format. Please refresh to check status.');
                    }
                }
            })
            .catch(error => {
                // Reset button state
                this.textContent = originalText;
                this.disabled = false;
                
                console.error('Fetch error:', error);
                showMessage('error', 'Network or processing error: ' + error.message);
            });
        });
    });
}
});
    </script>
<style>
    .status-toggle {
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}
.status-toggle.active {
    background-color: #28a745;
    color: white;
}
.status-toggle.inactive {
    background-color: #dc3545;
    color: white;
}
</style>