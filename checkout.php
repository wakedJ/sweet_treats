<?php
// Configure error handling for debugging
ini_set('display_errors', 0); // Don't display errors to browser
ini_set('log_errors', 1); // Log errors to file
ini_set('error_log', 'checkout_errors.log'); // Set path to error log
error_reporting(E_ALL); // Report all errors

require_once 'includes/db.php';
session_start();

// Debug function - log to file
function debugLog($message, $data = null) {
    $logFile = 'checkout_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    if ($data !== null) {
        $logMessage .= print_r($data, true) . "\n";
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Log cart data if it exists
if (isset($_SESSION['cart'])) {
    debugLog("SESSION cart data:", $_SESSION['cart']);
}
if (isset($_SESSION['cart_summary'])) {
    debugLog("SESSION cart_summary:", $_SESSION['cart_summary']);
}

// Verify database connection
if (!isset($conn) || mysqli_connect_errno()) {
    $error = "Failed to connect to MySQL: " . mysqli_connect_error();
    error_log($error);
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

// Get cart data from session (set in cart.php)
$cart_summary = isset($_SESSION['cart_summary']) ? $_SESSION['cart_summary'] : [];
$subtotal = isset($cart_summary['subtotal']) ? $cart_summary['subtotal'] : 0.00;
$promo_discount = isset($cart_summary['promo_discount']) ? $cart_summary['promo_discount'] : 0.00;
$delivery_fee = isset($cart_summary['delivery_fee']) ? $cart_summary['delivery_fee'] : 0.00;
$total_price = isset($cart_summary['total']) ? $cart_summary['total'] : 0.00;
$delivery_method = isset($cart_summary['delivery_type']) ? $cart_summary['delivery_type'] : 'pickup';
$promo_code = isset($cart_summary['applied_promo']) ? $cart_summary['applied_promo'] : '';
$promo_id = isset($cart_summary['promo_id']) ? $cart_summary['promo_id'] : null;

// Get cart items for inserting into order_items later
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a token check to prevent duplicate submissions
    $submission_token = isset($_POST['submission_token']) ? $_POST['submission_token'] : '';
    $stored_token = isset($_SESSION['submission_token']) ? $_SESSION['submission_token'] : '';
    
    // If there's a stored token and it matches the submitted one, this is a duplicate submission
    if (!empty($stored_token) && $stored_token === $submission_token) {
        debugLog("Duplicate submission detected. Token: $submission_token");
        echo json_encode(['success' => false, 'error' => 'This order has already been processed.']);
        exit;
    }
    
    // Store the new token for future checks
    $_SESSION['submission_token'] = $submission_token;
    debugLog("New submission with token: $submission_token");
    
    // Get common data
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to 1 if not logged in
    if (empty($cart_items) && isset($_SESSION['user_id'])) {
        // Try to load cart items from database
        $load_cart_stmt = $conn->prepare("SELECT ci.product_id, ci.quantity, p.price, p.name 
                                         FROM cart_items ci
                                         JOIN products p ON ci.product_id = p.id
                                         WHERE ci.user_id = ?");
        if ($load_cart_stmt) {
            $load_cart_stmt->bind_param("i", $user_id);
            $load_cart_stmt->execute();
            $cart_result = $load_cart_stmt->get_result();
            
            while ($item = $cart_result->fetch_assoc()) {
                $cart_items[$item['product_id']] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'name' => $item['name']
                ];
            }
            
            // Update session
            $_SESSION['cart'] = $cart_items;
            debugLog("Loaded cart from database:", $cart_items);
        }
    }
    
    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'error' => 'Your cart appears to be empty. Please add some products first.']);
        exit;
    }
    
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $order_type = $_POST['order_type'];
    
    // Validate required data
    if (empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'error' => 'Email and phone are required']);
        exit;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Common customer information for both order types
        $customer_email = $email;
        $customer_phone = $phone;
        
        // Handle based on order type
        if ($order_type === 'delivery') {
            // For delivery orders, save address first
            $first_name = $_POST['firstName'];
            $last_name = $_POST['lastName'];
            $street_address = $_POST['address'];
            $city = $_POST['city'];
            $postal_code = isset($_POST['postal_code']) ? $_POST['postal_code'] : '';
            $state = isset($_POST['state']) ? $_POST['state'] : '';
            
            // Validate required fields
            if (empty($first_name) || empty($last_name) || empty($street_address) || empty($city)) {
                throw new Exception("All required fields must be filled out");
            }
            
            // Check if this address already exists for this user
            $check_address_sql = "SELECT id FROM user_addresses 
                                 WHERE user_id = ? 
                                 AND street_address = ? 
                                 AND city = ? 
                                 AND state = ? 
                                 AND postal_code = ?";
                                 
            $check_address_stmt = mysqli_prepare($conn, $check_address_sql);
            if (!$check_address_stmt) {
                throw new Exception("Error preparing check address statement: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($check_address_stmt, "issss", 
                                  $user_id, $street_address, $city, $state, $postal_code);
            
            mysqli_stmt_execute($check_address_stmt);
            mysqli_stmt_store_result($check_address_stmt);
            
            if (mysqli_stmt_num_rows($check_address_stmt) > 0) {
                // Address exists, get its ID
                mysqli_stmt_bind_result($check_address_stmt, $address_id);
                mysqli_stmt_fetch($check_address_stmt);
                debugLog("Found existing address for user, address_id=" . $address_id);
                
                // Update the phone number if it's different
                $update_phone_sql = "UPDATE user_addresses SET phone_number = ? WHERE id = ?";
                $update_phone_stmt = mysqli_prepare($conn, $update_phone_sql);
                
                if ($update_phone_stmt) {
                    mysqli_stmt_bind_param($update_phone_stmt, "si", $phone, $address_id);
                    mysqli_stmt_execute($update_phone_stmt);
                    debugLog("Updated phone number for existing address");
                }
            } else {
                // Insert new address
                $address_sql = "INSERT INTO user_addresses (user_id, street_address, city, 
                                state, postal_code, phone_number, is_default) 
                                VALUES (?, ?, ?, ?, ?, ?, 0)";
                
                $address_stmt = mysqli_prepare($conn, $address_sql);
                if (!$address_stmt) {
                    throw new Exception("Error preparing address statement: " . mysqli_error($conn));
                }
                
                // Bind parameters for address
                mysqli_stmt_bind_param($address_stmt, "isssss", $user_id, $street_address, 
                                      $city, $state, $postal_code, $phone);
                
                if (!mysqli_stmt_execute($address_stmt)) {
                    throw new Exception("Error executing address statement: " . mysqli_stmt_error($address_stmt));
                }
                $address_id = mysqli_insert_id($conn);
                debugLog("Address inserted successfully, address_id=" . $address_id);
            }
            
            // Insert order with address_id
            $order_sql = "INSERT INTO orders (user_id, total_price, status, delivery_address_id, 
                          promo_code, promo_id, discount_amount, subtotal, delivery_fee, order_type, created_at) 
                          VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, 'delivery', NOW())";
            
            $order_stmt = mysqli_prepare($conn, $order_sql);
            if (!$order_stmt) {
                throw new Exception("Error preparing order statement: " . mysqli_error($conn));
            }
            
            // Debug the parameters before binding
            debugLog("Order parameters for delivery:", array(
                'user_id' => $user_id,
                'total_price' => $total_price,
                'address_id' => $address_id,
                'promo_code' => $promo_code,
                'promo_id' => $promo_id,
                'promo_discount' => $promo_discount,
                'subtotal' => $subtotal,
                'delivery_fee' => $delivery_fee,
                'order_type' => 'delivery'
            ));
            
            // Bind parameters for order
            mysqli_stmt_bind_param($order_stmt, "idisisdd", 
                                 $user_id, $total_price, $address_id, 
                                 $promo_code, $promo_id, $promo_discount, 
                                 $subtotal, $delivery_fee);
            
            // Execute order insertion
            if (!mysqli_stmt_execute($order_stmt)) {
                throw new Exception("Error executing order statement: " . mysqli_stmt_error($order_stmt));
            }
            
            $order_id = mysqli_insert_id($conn);
            debugLog("Order inserted successfully, order_id=" . $order_id);
        } else {
            // For pickup orders
            $pickup_first_name = $_POST['pickup_firstName'];
            $pickup_last_name = $_POST['pickup_lastName'];
            $pickup_time = $_POST['pickup_time'];
            $pickup_instructions = isset($_POST['pickup_instructions']) ? $_POST['pickup_instructions'] : '';

            // Validate required fields
            if (empty($pickup_first_name) || empty($pickup_last_name) || empty($pickup_time)) {
                throw new Exception("All required pickup fields must be filled out");
            }

            // Insert pickup order
            $order_sql = "INSERT INTO orders (user_id, total_price, status, 
                        promo_code, promo_id, discount_amount, subtotal, delivery_fee, order_type,
                        pickup_time, pickup_instructions, created_at) 
                        VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, 'pickup', ?, ?, NOW())";

            $order_stmt = mysqli_prepare($conn, $order_sql);
            if (!$order_stmt) {
                throw new Exception("Error preparing pickup order statement: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($order_stmt, "idisdddss", 
                                 $user_id, $total_price, 
                                 $promo_code, $promo_id, $promo_discount, 
                                 $subtotal, $delivery_fee,  
                                 $pickup_time, $pickup_instructions);
                                 
            // Execute order insertion
            if (!mysqli_stmt_execute($order_stmt)) {
                throw new Exception("Error executing order statement: " . mysqli_stmt_error($order_stmt));
            }
            $order_id = mysqli_insert_id($conn);
        }
        
        // Insert order items
        if (!empty($cart_items)) {
            debugLog("Processing cart_items:", $cart_items);
            
            $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $item_stmt = mysqli_prepare($conn, $item_sql);
            
            if (!$item_stmt) {
                throw new Exception("Error preparing order items statement: " . mysqli_error($conn));
            }
            
            // Process each item in the cart_items array
            foreach ($cart_items as $item_key => $item) {
                debugLog("Processing item:", $item);
                
                // Handle different cart structures
                if (is_array($item) && isset($item['product_id'])) {
                    $product_id = $item['product_id'];
                    $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                    $price = isset($item['price']) ? $item['price'] : null;
                } else {
                    // If cart is structured with product_id as the key
                    $product_id = is_numeric($item_key) ? $item_key : null;
                    $quantity = is_array($item) && isset($item['quantity']) ? $item['quantity'] : 1;
                    $price = is_array($item) && isset($item['price']) ? $item['price'] : null;
                }
                
                // If price is null, get it from database
                if ($price === null || $product_id === null) {
                    debugLog("Fetching product from database for missing data");
                    // Get product details from database
                    $prod_stmt = mysqli_prepare($conn, "SELECT id, price FROM products WHERE id = ?");
                    mysqli_stmt_bind_param($prod_stmt, "i", $product_id);
                    mysqli_stmt_execute($prod_stmt);
                    $result = mysqli_stmt_get_result($prod_stmt);
                    
                    if ($product_data = mysqli_fetch_assoc($result)) {
                        $product_id = $product_data['id'];
                        $price = $product_data['price'];
                        debugLog("Found product in DB:", $product_data);
                    } else {
                        debugLog("Product not found in database:", $product_id);
                        continue; // Skip this item
                    }
                }
                
                debugLog("Inserting order item: order_id=$order_id, product_id=$product_id, quantity=$quantity, price=$price");
                
                mysqli_stmt_bind_param($item_stmt, "iiid", $order_id, $product_id, $quantity, $price);
                if (!mysqli_stmt_execute($item_stmt)) {
                    $error = mysqli_stmt_error($item_stmt);
                    debugLog("Error inserting order item: " . $error);
                    throw new Exception("Error inserting order item: " . $error);
                } else {
                    debugLog("Successfully inserted order item");
                }
            }
        } else {
            debugLog("Cart is empty");
            throw new Exception("Your cart appears to be empty");
        }
        if (!empty($cart_items)) {
    debugLog("Starting stock reduction for cart items");
    
    $stock_update_sql = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
    $stock_stmt = mysqli_prepare($conn, $stock_update_sql);
    
    if (!$stock_stmt) {
        throw new Exception("Error preparing stock update statement: " . mysqli_error($conn));
    }
    
    foreach ($cart_items as $item_key => $item) {
        // Handle different cart structures (same logic as before)
        if (is_array($item) && isset($item['product_id'])) {
            $product_id = $item['product_id'];
            $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
        } else {
            $product_id = is_numeric($item_key) ? $item_key : null;
            $quantity = is_array($item) && isset($item['quantity']) ? $item['quantity'] : 1;
        }
        
        if ($product_id === null) {
            continue; // Skip invalid items
        }
        
        // First, check current stock level
        $check_stock_sql = "SELECT stock, name FROM products WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_stock_sql);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "i", $product_id);
            mysqli_stmt_execute($check_stmt);
            $stock_result = mysqli_stmt_get_result($check_stmt);
            
            if ($stock_data = mysqli_fetch_assoc($stock_result)) {
                $current_stock = $stock_data['stock'];
                $product_name = $stock_data['name'];
                
                debugLog("Product: $product_name (ID: $product_id) - Current stock: $current_stock, Ordered: $quantity");
                
                if ($current_stock < $quantity) {
                    debugLog("Insufficient stock for product ID $product_id. Available: $current_stock, Requested: $quantity");
                    throw new Exception("Insufficient stock for product: $product_name. Available: $current_stock, Requested: $quantity");
                }
                
                // Update stock quantity
                mysqli_stmt_bind_param($stock_stmt, "iii", $quantity, $product_id, $quantity);
                
                if (!mysqli_stmt_execute($stock_stmt)) {
                    $error = mysqli_stmt_error($stock_stmt);
                    debugLog("Error updating stock for product ID $product_id: " . $error);
                    throw new Exception("Error updating stock for product: $product_name - " . $error);
                }
                
                // Check if the update actually affected any rows (stock was sufficient)
                $affected_rows = mysqli_stmt_affected_rows($stock_stmt);
                
                if ($affected_rows === 0) {
                    debugLog("Stock update failed - insufficient quantity for product ID $product_id");
                    throw new Exception("Insufficient stock for product: $product_name. Please refresh and try again.");
                }
                
                debugLog("Successfully reduced stock for product ID $product_id by $quantity units");
                
                // Optional: Log stock alerts for low inventory
                $new_stock = $current_stock - $quantity;
                if ($new_stock <= 5) { // Alert threshold
                    debugLog("LOW STOCK ALERT: Product '$product_name' (ID: $product_id) now has only $new_stock units remaining");
                    
                    // You could also insert into a stock_alerts table or send email notification here
                    // Example:
                    // $alert_sql = "INSERT INTO stock_alerts (product_id, current_stock, alert_date) VALUES (?, ?, NOW())";
                    // $alert_stmt = mysqli_prepare($conn, $alert_sql);
                    // mysqli_stmt_bind_param($alert_stmt, "ii", $product_id, $new_stock);
                    // mysqli_stmt_execute($alert_stmt);
                }
                
            } else {
                debugLog("Product not found for stock check: ID $product_id");
                throw new Exception("Product not found for stock verification");
            }
        } else {
            throw new Exception("Error preparing stock check statement: " . mysqli_error($conn));
        }
    }
    
    debugLog("Stock reduction completed successfully for all items");
}
        // Apply promo code uses if one was used
      
// FIXED: Apply promo code uses if one was used
if (!empty($promo_code) && $promo_id) {
    // Check if promo code is still valid and has uses remaining
    $check_promo_sql = "SELECT current_uses, max_uses FROM promo_codes WHERE promo_id = ? AND is_active = 1";
    $check_promo_stmt = mysqli_prepare($conn, $check_promo_sql);
    
    if (!$check_promo_stmt) {
        throw new Exception("Error preparing promo check statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_promo_stmt, "i", $promo_id);
    mysqli_stmt_execute($check_promo_stmt);
    $promo_result = mysqli_stmt_get_result($check_promo_stmt);
    
    if ($promo_data = mysqli_fetch_assoc($promo_result)) {
        debugLog("Promo code check - Current uses: " . $promo_data['current_uses'] . ", Max uses: " . $promo_data['max_uses']);
        
        // Check if promo code has reached its limit
        if ($promo_data['current_uses'] >= $promo_data['max_uses']) {
            debugLog("Promo code usage limit reached");
            throw new Exception('This promo code has reached its usage limit.');
        }
        
        // Update promo code usage count
        $promo_sql = "UPDATE promo_codes SET current_uses = current_uses + 1 WHERE promo_id = ? AND is_active = 1";
        $promo_stmt = mysqli_prepare($conn, $promo_sql);
        
        if (!$promo_stmt) {
            throw new Exception("Error preparing promo update statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($promo_stmt, "i", $promo_id);
        
        if (!mysqli_stmt_execute($promo_stmt)) {
            throw new Exception("Error updating promo uses: " . mysqli_stmt_error($promo_stmt));
        }
        
        // Verify the update was successful
        $affected_rows = mysqli_stmt_affected_rows($promo_stmt);
        if ($affected_rows > 0) {
            debugLog("Successfully incremented promo code usage for promo_id: $promo_id");
        } else {
            debugLog("Warning: Promo code usage increment didn't affect any rows");
        }
        
    } else {
        debugLog("Promo code not found or inactive");
        throw new Exception('Invalid or inactive promo code.');
    }
} else {
    debugLog("No promo code applied to this order");
}

        // Commit transaction
        mysqli_commit($conn);
        
        // Save confirmation info to session
        $_SESSION['order_confirmation'] = [
            'order_id' => $order_id,
            'email' => $email,
            'total' => $total_price
        ];
        
        // Clear cart session
        unset($_SESSION['cart_summary']);
        unset($_SESSION['cart']);
        unset($_SESSION['applied_promo']);

        $_SESSION['cart_count'] = 0;

        if (isset($_SESSION['user_id'])) {
            // Clear user's cart from database
            $clear_cart_sql = "DELETE FROM cart_items WHERE user_id = ?";
            $clear_cart_stmt = mysqli_prepare($conn, $clear_cart_sql);
            if ($clear_cart_stmt) {
                mysqli_stmt_bind_param($clear_cart_stmt, "i", $user_id);
                mysqli_stmt_execute($clear_cart_stmt);
            }
        }
        
        // Output success JSON for AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'order_id' => $order_id]);
        exit;
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        
        // Log the error to server error log
        error_log("Checkout Error: " . $e->getMessage());
        
        // Return error as JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sweet Treats</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="css/checkout.css">
</head>
<body>
    <div class="container">
        <div class="checkout-hero">
            <h1>Checkout</h1>
            <p>You're just a few steps away from completing your sweet order!</p>
            
            <!-- Candy Icons -->
            <div class="candy-icon candy-1">🍭</div>
            <div class="candy-icon candy-2">🍬</div>
            <div class="candy-icon candy-3">🍪</div>
            <div class="candy-icon candy-4">🧁</div>
        </div>
        
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="summary-items">
                <div class="summary-row">
                    <div>Subtotal</div>
                    <div>$<?php echo number_format($subtotal, 2); ?></div>
                </div>
                
                <?php if ($promo_discount > 0): ?>
                <div class="summary-row discount">
                    <div>Discount</div>
                    <div>-$<?php echo number_format($promo_discount, 2); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="summary-row">
                    <div>Delivery Fee</div>
                    <div><?php echo ($delivery_fee > 0) ? '$'.number_format($delivery_fee, 2) : 'FREE'; ?></div>
                </div>
            </div>
            
            <div class="order-total">
                <span>Total:</span>
                <span>$<?php echo number_format($total_price, 2); ?></span>
            </div>
            
            <div class="delivery-method-summary">
                <h3>Delivery Method</h3>
                <div class="selected-method">
                    <i class="fas <?php echo ($delivery_method === 'delivery') ? 'fa-truck' : 'fa-store'; ?>"></i>
                    <span id="delivery-method-text"><?php echo ($delivery_method === 'delivery') ? 'Home Delivery' : 'Store Pickup'; ?></span>
                </div>
            </div>
        </div>
        
        <div class="form-container">
            <form id="checkout-form" method="POST">
                <input type="hidden" name="order_type" id="order_type" value="<?php echo $delivery_method; ?>">
                <!-- Add submission token to prevent duplicate submissions -->
                <input type="hidden" name="submission_token" id="submission_token" value="<?php echo uniqid('order_', true); ?>">
                
                <!-- Common Fields -->
                <div class="form-section">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>
                
                <!-- Delivery Address Section - show/hide based on delivery method -->
                <div id="delivery-section" class="form-section <?php echo $delivery_method !== 'delivery' ? 'hidden' : ''; ?>">
                    <h2><i class="fas fa-truck"></i> Shipping Information</h2>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="firstName">First Name *</label>
                                <input type="text" id="firstName" name="firstName" <?php echo $delivery_method === 'delivery' ? 'required' : ''; ?>>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="lastName">Last Name *</label>
                                <input type="text" id="lastName" name="lastName" <?php echo $delivery_method === 'delivery' ? 'required' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Street Address *</label>
                        <input type="text" id="address" name="address" <?php echo $delivery_method === 'delivery' ? 'required' : ''; ?>>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="city">City *</label>
                                <input type="text" id="city" name="city" <?php echo $delivery_method === 'delivery' ? 'required' : ''; ?>>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="postal_code">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="state">State/Province</label>
                        <input type="text" id="state" name="state">
                    </div>
                </div>
                
                <!-- Pickup Section - show/hide based on delivery method -->
                <div id="pickup-section" class="form-section <?php echo $delivery_method === 'delivery' ? 'hidden' : ''; ?>">
                    <h2><i class="fas fa-store"></i> Store Pickup Details</h2>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="pickup_firstName">First Name *</label>
                                <input type="text" id="pickup_firstName" name="pickup_firstName" <?php echo $delivery_method !== 'delivery' ? 'required' : ''; ?>>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="pickup_lastName">Last Name *</label>
                                <input type="text" id="pickup_lastName" name="pickup_lastName" <?php echo $delivery_method !== 'delivery' ? 'required' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="pickup_time">Pickup Time *</label>
                        <input type="datetime-local" id="pickup_time" name="pickup_time" <?php echo $delivery_method !== 'delivery' ? 'required' : ''; ?>>
                        <small>Please allow at least 3 hours for us to prepare your order. You can schedule a pickup up to 1 week in advance.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="pickup_instructions">Special Instructions (Optional)</label>
                        <textarea id="pickup_instructions" name="pickup_instructions" rows="3"></textarea>
                    </div>
                    
                    <div class="store-info">
                        <h3>Store Location</h3>
                        <p><i class="fas fa-map-marker-alt"></i> 123 Sweet Street, Candy City, CS 12345</p>
                        <p><i class="fas fa-clock"></i> Store Hours: 9:00 AM - 9:00 PM (Monday-Saturday)</p>
                        <div class="store-map">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d594.2147322222334!2d35.80823071825051!3d33.62786734496674!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sKamed%20El%20Laouz%20wakid&#39;s%20store!5e0!3m2!1sen!2sus!4v1741579218137!5m2!1sen!2sus" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe> 
                                            
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <span>Complete Order</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal" id="confirmation-modal">
        <div class="modal-content">
            <div class="success-icon">🎉</div>
            <h2>Yay! Order Confirmed!</h2>
            <p>Thank you for your purchase. We've sent a confirmation email to <span id="confirmation-email"></span>.</p>
            <p>Your order number is: <strong id="order-number"></strong></p>
            <button onclick="closeModal()" class="modal-btn">Continue Shopping</button>
        </div>
    </div>
    
    <script>
        // Generate a unique token for this form submission
        const submissionToken = 'order_' + Math.random().toString(36).substr(2, 9) + '_' + new Date().getTime();
        document.getElementById('submission_token').value = submissionToken;
        
        // When the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Set up date restrictions for pickup time
            setupPickupDateLimits();
            
            // Get the order type from the hidden input
            const orderType = document.getElementById('order_type').value;
            
            // Ensure form fields validation is set up properly based on delivery method
            updateRequiredFields(orderType);
        });
        
        function updateRequiredFields(orderType) {
            // Get delivery form fields
            const deliveryFields = ['firstName', 'lastName', 'address', 'city'];
            
            // Get pickup form fields
            const pickupFields = ['pickup_firstName', 'pickup_lastName', 'pickup_time'];
            
            if (orderType === 'delivery') {
                // Make delivery fields required
                deliveryFields.forEach(field => {
                    const element = document.getElementById(field);
                    if (element) element.required = true;
                });
                
                // Make pickup fields not required
                pickupFields.forEach(field => {
                    const element = document.getElementById(field);
                    if (element) element.required = false;
                });
                
                // Show delivery section and hide pickup
                document.getElementById('delivery-section').classList.remove('hidden');
                document.getElementById('pickup-section').classList.add('hidden');
            } else {
                // Make pickup fields required
                pickupFields.forEach(field => {
                    const element = document.getElementById(field);
                    if (element) element.required = true;
                });
                
                // Make delivery fields not required
                deliveryFields.forEach(field => {
                    const element = document.getElementById(field);
                    if (element) element.required = false;
                });
                
                // Show pickup section and hide delivery
                document.getElementById('pickup-section').classList.remove('hidden');
                document.getElementById('delivery-section').classList.add('hidden');
            }
        }
        
        function setupPickupDateLimits() {
            const pickupTimeInput = document.getElementById('pickup_time');
            if (pickupTimeInput) {
                // Minimum time (3 hours from now)
                const minTime = new Date();
                minTime.setHours(minTime.getHours() + 3);
                
                // Maximum time (1 week from now)
                const maxTime = new Date();
                maxTime.setDate(maxTime.getDate() + 7);
                
                // Format dates for the input
                const formatDateTime = (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    
                    return `${year}-${month}-${day}T${hours}:${minutes}`;
                };
                
                // Set min and max attributes
                pickupTimeInput.min = formatDateTime(minTime);
                pickupTimeInput.max = formatDateTime(maxTime);
                
                // Set default value to tomorrow at noon
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                tomorrow.setHours(12, 0, 0, 0);
                
                pickupTimeInput.value = formatDateTime(tomorrow);
            }
        }
        
        // Flag to track if form has been submitted already
        let formSubmitted = false;
        
        // Handle form submission via AJAX
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Prevent double submissions
            if (formSubmitted) {
                console.log('Form already submitted, ignoring duplicate submission');
                return false;
            }
            
            // Mark as submitted
            formSubmitted = true;
            
            const form = this;
            const formData = new FormData(form);
            
            // Create an animated loading state for the button
            const submitBtn = form.querySelector('.submit-btn');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            fetch('checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Try to parse response as JSON, but handle if it's not valid JSON
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // If the response is not valid JSON, show it as a raw error
                        console.error('Invalid JSON response:', text);
                        throw new Error('Server returned invalid response. See console for details.');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    // Show confirmation modal
                    const email = document.getElementById('email').value;
                    document.getElementById('confirmation-email').textContent = email;
                    document.getElementById('order-number').textContent = 'SWEET-' + data.order_id;
                    document.getElementById('confirmation-modal').style.display = 'flex';
                } else {
                    console.error('Order error:', data);
                    alert('Error: ' + (data.error || 'Failed to process your order. Please try again.'));
                    // Restore button
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Request error:', error);
                alert('An error occurred: ' + error.message);
                // Restore button
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            });
        });
        
        function closeModal() {
            document.getElementById('confirmation-modal').style.display = 'none';
            window.location.href = 'shop.php';
        }
    </script>
    
</body>
</html>