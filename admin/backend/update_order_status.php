<?php
// Start session at the beginning of the file
session_start();

// Define if we're in debug mode
define('DEBUG_MODE', true); // Set to false in production

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/order_status_update.log');

// Function to log with timestamp
function log_message($message) {
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $message);
}

log_message("update_order_status.php started");

// Include the database connection
$db_included = false;

// Path when accessed directly via backend/update_order_status.php
if (file_exists("../../includes/db.php")) {
    log_message("Trying DB file: ../../includes/db.php");
    require_once "../../includes/db.php";
    $db_included = true;
    log_message("Successfully connected using: ../../includes/db.php");
}

// Path when accessed through admin/index.php?page=orders
if (!$db_included && file_exists("../includes/db.php")) {
    log_message("Trying DB file: ../includes/db.php");
    require_once "../includes/db.php";
    $db_included = true;
    log_message("Successfully connected using: ../includes/db.php");
}

// Root level path
if (!$db_included && file_exists("includes/db.php")) {
    log_message("Trying DB file: includes/db.php");
    require_once "includes/db.php";
    $db_included = true;
    log_message("Successfully connected using: includes/db.php");
}

// Check database connection
if (!isset($pdo) || !($pdo instanceof PDO)) {
    log_message("ERROR: PDO connection not established");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
} else {
    log_message("Database connection successful");
}

// Get data from various possible sources
$data = [];

// First try POST data
if (!empty($_POST)) {
    $data = $_POST;
    log_message("Using POST data: " . print_r($_POST, true));
}
// Then try GET data
else if (!empty($_GET)) {
    $data = $_GET;
    log_message("Using GET data: " . print_r($_GET, true));
}
// Finally try raw input (for JSON requests)
else {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        log_message("Raw input received: " . $raw_input);
        $json_data = json_decode($raw_input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $json_data;
            log_message("Parsed data: " . print_r($data, true));
        } else {
            log_message("Failed to parse JSON: " . json_last_error_msg());
        }
    } else {
        log_message("No input data received");
    }
}

// Extract order_id and status
$order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;
$status = isset($data['status']) ? trim($data['status']) : '';

log_message("Attempting to update order ID: $order_id with status: $status");

// Validate inputs
if ($order_id <= 0) {
    log_message("ERROR: Invalid order ID: $order_id");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    log_message("ERROR: Invalid status: $status");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

// Set proper content type header
header('Content-Type: application/json');

try {
    // Verify table exists
    try {
        $check_table = $pdo->query("SHOW TABLES LIKE 'orders'");
        if ($check_table->rowCount() === 0) {
            log_message("ERROR: 'orders' table does not exist");
            throw new Exception("Table 'orders' does not exist in the database");
        }
        log_message("'orders' table exists");
        
        // Check table structure
        $columns = $pdo->query("SHOW COLUMNS FROM orders");
        $column_names = [];
        while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
            $column_names[] = $col['Field'];
        }
        log_message("Table columns: " . implode(", ", $column_names));
        
        if (!in_array('status', $column_names)) {
            log_message("ERROR: 'status' column does not exist in orders table");
            throw new Exception("Column 'status' does not exist in orders table");
        }
        
        if (!in_array('updated_at', $column_names)) {
            log_message("WARNING: 'updated_at' column not found, will use NOW() directly");
            $has_updated_at = false;
        } else {
            $has_updated_at = true;
        }
    } catch (Exception $e) {
        log_message("Error checking table structure: " . $e->getMessage());
        throw $e;
    }
    
    // First check if the order exists
    $check_stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
    $check_stmt->execute([$order_id]);
    if ($check_stmt->rowCount() === 0) {
        log_message("ERROR: Order with ID $order_id not found");
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    log_message("Order with ID $order_id found, proceeding with update");
    
    // Begin transaction
    $pdo->beginTransaction();
    log_message("Transaction started");
    
    // Prepare update SQL based on column structure
    if ($has_updated_at) {
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $params = [$status, $order_id];
    } else {
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $params = [$status, $order_id];
    }
    
    log_message("Executing SQL: $sql with params: status=$status, id=$order_id");
    
    // Execute update
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        $affected_rows = $stmt->rowCount();
        log_message("Update successful. Affected rows: $affected_rows");
        
        // Try to log the change in status history if table exists
        try {
            $check_history_table = $pdo->query("SHOW TABLES LIKE 'order_status_history'");
            if ($check_history_table->rowCount() > 0) {
                log_message("Logging status change to history table");
                $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
                $history_sql = "INSERT INTO order_status_history (order_id, status, changed_by, changed_at) VALUES (?, ?, ?, NOW())";
                $history_stmt = $pdo->prepare($history_sql);
                $history_stmt->execute([$order_id, $status, $user_id]);
                log_message("Status change logged in history table");
            } else {
                log_message("order_status_history table not found, skipping history logging");
            }
        } catch (Exception $history_ex) {
            // Don't fail the whole operation if history logging fails
            log_message("Warning: Failed to log status history: " . $history_ex->getMessage());
        }
        
        // Commit transaction
        $pdo->commit();
        log_message("Transaction committed");
        
        // Return success response
        echo json_encode([
            'success' => true, 
            'message' => 'Order status updated successfully',
            'order_id' => $order_id,
            'status' => $status
        ]);
        log_message("Success response sent");
    } else {
        // Check for errors
        $error_info = $stmt->errorInfo();
        log_message("Update failed: " . print_r($error_info, true));
        throw new PDOException("Execute failed: " . implode(" ", $error_info));
    }
} catch (PDOException $e) {
    // Roll back transaction
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        log_message("Transaction rolled back");
    }
    
    // Log error
    log_message("Database error: " . $e->getMessage());
    if (method_exists($e, 'errorInfo') && is_array($e->errorInfo())) {
        log_message("PDO Error Info: " . print_r($e->errorInfo(), true));
    }
    
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'debug' => DEBUG_MODE ? $e->getMessage() : null
    ]);
    log_message("Error response sent");
} catch (Exception $e) {
    // Handle general exceptions
    log_message("General error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'debug' => DEBUG_MODE ? $e->getMessage() : null
    ]);
    log_message("Error response sent");
}
?>