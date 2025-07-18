<?php
session_start();

// Get the delivery type from the POST data
$delivery_type = isset($_POST['delivery_type']) ? $_POST['delivery_type'] : 'pickup';

// Store the delivery type in the session
$_SESSION['delivery_type'] = $delivery_type;

// Update delivery fee in cart summary if it exists
if (isset($_SESSION['cart_summary'])) {
    $_SESSION['cart_summary']['delivery_type'] = $delivery_type;
    
    // Recalculate delivery fee if needed
    $subtotal = $_SESSION['cart_summary']['subtotal'];
    $promo_discount = $_SESSION['cart_summary']['promo_discount'];
    
    // Get delivery rules from DB
    require_once 'includes/db.php';
    $delivery_query = "SELECT * FROM delivery_rules ORDER BY id DESC LIMIT 1";
    $delivery_result = $conn->query($delivery_query);
    $delivery_rules = $delivery_result->fetch_assoc();
    
    // Default delivery fee if no rules found
    $standard_delivery_fee = $delivery_rules ? $delivery_rules['standard_delivery_fee'] : 3.00;
    $min_order_for_free_delivery = $delivery_rules ? $delivery_rules['min_order_for_free_delivery'] : 50.00;
    
    // Calculate if eligible for free delivery
    $delivery_fee = $standard_delivery_fee;
    if ($subtotal >= $min_order_for_free_delivery) {
        $delivery_fee = 0;
    }
    
    // Update delivery fee based on selected type
    $_SESSION['cart_summary']['delivery_fee'] = ($delivery_type == 'delivery') ? $delivery_fee : 0;
    
    // Recalculate total
    $_SESSION['cart_summary']['total'] = $subtotal - $promo_discount + $_SESSION['cart_summary']['delivery_fee'];
}

// Return success with the updated cart summary
echo json_encode([
    'success' => true,
    'delivery_type' => $delivery_type,
    'cart_summary' => isset($_SESSION['cart_summary']) ? $_SESSION['cart_summary'] : null
]);
?>