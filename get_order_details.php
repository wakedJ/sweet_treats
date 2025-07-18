<?php
// File: get_order_details.php
session_start();
include "includes/db.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a log function
function debug_log($message, $data = null) {
    // Log to a file
    $log_message = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $log_message .= ' - ' . json_encode($data);
    }
    file_put_contents('order_debug.log', $log_message . "\n", FILE_APPEND);
}

debug_log('Script started');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    debug_log('User not logged in');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get order ID from request
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
debug_log('Order ID received', $order_id);

// Validate order ID
if ($order_id <= 0) {
    debug_log('Invalid order ID');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

// Check if this order belongs to the logged-in user
$user_id = $_SESSION['user_id'];
debug_log('User ID', $user_id);

try {
    // First, check if order belongs to user
    $query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    debug_log('Query to check order ownership', $query);
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        debug_log('Prepare failed', $conn->error);
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $order_id, $user_id);
    if (!$stmt->execute()) {
        debug_log('Execute failed', $stmt->error);
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    debug_log('Result row count', $result->num_rows);
    
    if ($result->num_rows !== 1) {
        debug_log('Order not found or unauthorized');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Order not found or unauthorized']);
        exit();
    }
    
    // Get order details
    $order = $result->fetch_assoc();
    debug_log('Order data fetched', array_keys($order));
    $stmt->close();
    
    // Initialize items array
    $order['items'] = [];
    
    // Get order items
    $query = "SELECT oi.*, p.name, p.image as image_url 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
              
    debug_log('Query for order items', $query);
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        debug_log('Prepare failed for items query', $conn->error);
        throw new Exception("Failed to prepare items statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $order_id);
    
    if (!$stmt->execute()) {
        debug_log('Execute failed for items query', $stmt->error);
        throw new Exception("Failed to execute items statement: " . $stmt->error);
    }
    
    $items_result = $stmt->get_result();
    debug_log('Items result count', $items_result->num_rows);
    
    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        // Ensure numeric values are properly formatted
        $item['price'] = (float)$item['price'];
        $item['quantity'] = (int)$item['quantity'];
        $items[] = $item;
    }
    $stmt->close();
    
    $order['items'] = $items;
    debug_log('Items added to order', count($items));
    
    // Ensure created_at is properly formatted for JSON
    if (isset($order['created_at'])) {
        $order['created_at'] = $order['created_at'];
    }
    
    // Handle shipping address
    // Default values for shipping info
    $order['shipping_name'] = $order['shipping_name'] ?? 'N/A';
    $order['shipping_address'] = $order['shipping_address'] ?? 'N/A';
    $order['shipping_city'] = $order['shipping_city'] ?? 'N/A';
    $order['shipping_state'] = $order['shipping_state'] ?? 'N/A';
    $order['shipping_postal_code'] = $order['shipping_postal_code'] ?? 'N/A';
    $order['shipping_phone'] = $order['shipping_phone'] ?? 'N/A';
    
    // Try to get shipping address if we have an ID
    if (isset($order['shipping_address_id']) && $order['shipping_address_id'] > 0) {
        debug_log('Fetching shipping address', $order['shipping_address_id']);
        
        try {
            $query = "SELECT * FROM user_addresses WHERE id = ?";
            $stmt = $conn->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param("i", $order['shipping_address_id']);
                if ($stmt->execute()) {
                    $address_result = $stmt->get_result();
                    
                    if ($address_result->num_rows === 1) {
                        $address = $address_result->fetch_assoc();
                        debug_log('Address found', array_keys($address));
                        
                        // Use address data - careful with null values
                        $order['shipping_name'] = $address['recipient_name'] ?? $order['shipping_name'];
                        $order['shipping_address'] = $address['street_address'] ?? $order['shipping_address'];
                        $order['shipping_city'] = $address['city'] ?? $order['shipping_city'];
                        $order['shipping_state'] = $address['state'] ?? $order['shipping_state'];
                        $order['shipping_postal_code'] = $address['postal_code'] ?? $order['shipping_postal_code'];
                        $order['shipping_phone'] = $address['phone_number'] ?? $order['shipping_phone'];
                    } else {
                        debug_log('No address found for ID', $order['shipping_address_id']);
                    }
                }
                $stmt->close();
            } else {
                debug_log('Failed to prepare address statement', $conn->error);
            }
        } catch (Exception $e) {
            debug_log('Error fetching address', $e->getMessage());
            // Continue processing - we already have fallback values
        }
    } else {
        debug_log('No shipping_address_id found');
    }
    
    // Payment info
    $order['payment_method'] = $order['payment_method'] ?? 'Credit Card';
    $order['payment_last_four'] = $order['payment_last_four'] ?? '****';
    
    // Calculate subtotal
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $order['subtotal'] = (float)($order['subtotal'] ?? $subtotal);
    
    // Set default values if not present
    $order['shipping_cost'] = (float)($order['shipping_cost'] ?? 5.99);
    $order['tax_amount'] = (float)($order['tax_amount'] ?? $order['subtotal'] * 0.08);
    
    // Make sure total price is calculated correctly
    if (!isset($order['total_price']) || (float)$order['total_price'] <= 0) {
        $order['total_price'] = $order['subtotal'] + $order['shipping_cost'] + $order['tax_amount'];
    } else {
        $order['total_price'] = (float)$order['total_price'];
    }
    
    // Set status if not already set
    $order['status'] = $order['status'] ?? 'Processing';
    
    debug_log('Final order data', array_keys($order));
    
    // Ensure all data is properly sanitized for JSON
    $sanitized_order = array_map(function($value) {
        if (is_string($value)) {
            return htmlspecialchars_decode($value);
        }
        return $value;
    }, $order);
    
    // Return successful response
    header('Content-Type: application/json');
    
    // Use JSON_NUMERIC_CHECK to ensure numbers are properly formatted
    $json_response = json_encode(['success' => true, 'order' => $sanitized_order], JSON_NUMERIC_CHECK);
    
    if ($json_response === false) {
        debug_log('JSON encoding failed', json_last_error_msg());
        throw new Exception("Failed to encode order data: " . json_last_error_msg());
    }
    
    echo $json_response;
    debug_log('Response sent successfully');
    
} catch (Exception $e) {
    debug_log('Exception caught', $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error retrieving order: ' . $e->getMessage()]);
    exit();
}
?>