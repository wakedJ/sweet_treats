<?php
require_once 'includes/db.php';
session_start();

// This would normally come from the cart page
$subtotal = isset($_SESSION['cart_subtotal']) ? $_SESSION['cart_subtotal'] : 12.00;
$delivery_fee = isset($_SESSION['delivery_fee']) ? $_SESSION['delivery_fee'] : 0.00;
$total_price = $subtotal + $delivery_fee;
$delivery_method = isset($_SESSION['delivery_method']) ? $_SESSION['delivery_method'] : 'pickup';
$promo_code = isset($_SESSION['promo_code']) ? $_SESSION['promo_code'] : '';
$discount_amount = isset($_SESSION['discount_amount']) ? $_SESSION['discount_amount'] : 0.00;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get common data
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to 1 if not logged in
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $order_type = $_POST['order_type'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Handle based on order type
        if ($order_type === 'delivery') {
            // For delivery orders, save address first
            $first_name = $_POST['firstName'];
            $last_name = $_POST['lastName'];
            $street_address = $_POST['address'];
            $city = $_POST['city'];
            $postal_code = isset($_POST['postal_code']) ? $_POST['postal_code'] : '';
            $state = isset($_POST['state']) ? $_POST['state'] : '';
            
            // Insert address
            $address_sql = "INSERT INTO user_addresses (user_id, first_name, last_name, street_address, city, 
                            state, postal_code, phone_number, is_default) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
            
            $address_stmt = mysqli_prepare($conn, $address_sql);
            mysqli_stmt_bind_param($address_stmt, "isssssss", $user_id, $first_name, $last_name, $street_address, 
                                  $city, $state, $postal_code, $phone);
            mysqli_stmt_execute($address_stmt);
            $address_id = mysqli_insert_id($conn);
            
            // Insert order with address_id
            $order_sql = "INSERT INTO orders (user_id, total_price, status, payment_status, delivery_method, 
                          delivery_address_id, promo_code, discount_amount, subtotal, delivery_fee, order_type) 
                          VALUES (?, ?, 'pending', 'pending', 'home delivery', ?, ?, ?, ?, ?, 'delivery')";
            
            $order_stmt = mysqli_prepare($conn, $order_sql);
            mysqli_stmt_bind_param($order_stmt, "idisddd", $user_id, $total_price, $address_id, $promo_code, 
                                  $discount_amount, $subtotal, $delivery_fee);
            
        } else {
            // For pickup orders
            $pickup_first_name = $_POST['pickup_firstName'];
            $pickup_last_name = $_POST['pickup_lastName'];
            $pickup_email = $_POST['email'];
            $pickup_phone = $_POST['phone'];
            $pickup_time = $_POST['pickup_time'];
            $pickup_instructions = isset($_POST['pickup_instructions']) ? $_POST['pickup_instructions'] : '';
            
            // Insert pickup order
            $order_sql = "INSERT INTO orders (user_id, total_price, status, payment_status, delivery_method, 
                          pickup_first_name, pickup_last_name, pickup_email, pickup_phone, pickup_time, 
                          pickup_instructions, promo_code, discount_amount, subtotal, delivery_fee, order_type) 
                          VALUES (?, ?, 'pending', 'pending', 'pickup', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pickup')";
            
            $order_stmt = mysqli_prepare($conn, $order_sql);
            mysqli_stmt_bind_param($order_stmt, "idssssssdddd", $user_id, $total_price, $pickup_first_name, 
                                  $pickup_last_name, $pickup_email, $pickup_phone, $pickup_time, 
                                  $pickup_instructions, $promo_code, $discount_amount, $subtotal, $delivery_fee);
        }
        
        // Execute order insertion
        mysqli_stmt_execute($order_stmt);
        $order_id = mysqli_insert_id($conn);
        
        // Apply promo code uses if one was used
        if (!empty($promo_code)) {
            $promo_sql = "UPDATE promo_codes SET current_uses = current_uses + 1 WHERE code = ? AND is_active = 1";
            $promo_stmt = mysqli_prepare($conn, $promo_sql);
            mysqli_stmt_bind_param($promo_stmt, "s", $promo_code);
            mysqli_stmt_execute($promo_stmt);
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
        unset($_SESSION['cart_subtotal']);
        unset($_SESSION['delivery_fee']);
        unset($_SESSION['delivery_method']);
        unset($_SESSION['promo_code']);
        unset($_SESSION['discount_amount']);
        
        // Output success JSON for AJAX
        echo json_encode(['success' => true, 'order_id' => $order_id]);
        exit;
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
                
                <?php if ($discount_amount > 0): ?>
                <div class="summary-row discount">
                    <div>Discount</div>
                    <div>-$<?php echo number_format($discount_amount, 2); ?></div>
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
                    
                    <!-- Delivery Method Selector -->
                    <div class="delivery-selector">
                        <label class="method-option <?php echo ($delivery_method !== 'delivery') ? 'active' : ''; ?>">
                            <input type="radio" name="delivery_option" value="pickup" <?php echo ($delivery_method !== 'delivery') ? 'checked' : ''; ?>>
                            <span>Store Pickup</span>
                        </label>
                        <label class="method-option <?php echo ($delivery_method === 'delivery') ? 'active' : ''; ?>">
                            <input type="radio" name="delivery_option" value="delivery" <?php echo ($delivery_method === 'delivery') ? 'checked' : ''; ?>>
                            <span>Home Delivery</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-container">
            <form id="checkout-form" method="POST">
                <input type="hidden" name="order_type" id="order_type" value="<?php echo $delivery_method === 'delivery' ? 'delivery' : 'pickup'; ?>">
                
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
                
                <!-- Delivery Address Section -->
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
                
                <!-- Pickup Section -->
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
                            <!-- Placeholder for a map -->
                            <div class="map-placeholder">
                                <i class="fas fa-map"></i>
                                <span>Store Location Map</span>
                            </div>
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
        // When the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Set up date restrictions for pickup time
            setupPickupDateLimits();
            
            // Set up delivery method toggle
            setupDeliveryToggle();
        });
        
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
        
        function setupDeliveryToggle() {
            // Get delivery option radio buttons
            const deliveryOptions = document.querySelectorAll('input[name="delivery_option"]');
            
            // Add event listeners to radio buttons
            deliveryOptions.forEach(option => {
                option.addEventListener('change', function() {
                    // Update hidden input with order type
                    const orderTypeInput = document.getElementById('order_type');
                    orderTypeInput.value = this.value;
                    
                    // Update display text
                    const methodText = document.getElementById('delivery-method-text');
                    methodText.textContent = this.value === 'delivery' ? 'Home Delivery' : 'Store Pickup';
                    
                    // Update icon
                    const methodIcon = methodText.previousElementSibling;
                    methodIcon.className = this.value === 'delivery' ? 'fas fa-truck' : 'fas fa-store';
                    
                    // Toggle active class on labels
                    const labels = document.querySelectorAll('.method-option');
                    labels.forEach(label => {
                        if (label.querySelector('input').value === this.value) {
                            label.classList.add('active');
                        } else {
                            label.classList.remove('active');
                        }
                    });
                    
                    // Update form sections visibility and required fields
                    updateFormSections();
                });
            });
        }
        
        // Toggle between delivery and pickup sections based on selected method
        function updateFormSections() {
            const orderType = document.getElementById('order_type').value;
            const deliverySection = document.getElementById('delivery-section');
            const pickupSection = document.getElementById('pickup-section');
            
            if (orderType === 'delivery') {
                deliverySection.classList.remove('hidden');
                pickupSection.classList.add('hidden');
                
                // Make delivery fields required
                document.querySelectorAll('#delivery-section input[required]').forEach(input => {
                    input.setAttribute('required', '');
                });
                
                // Remove required from pickup fields
                document.querySelectorAll('#pickup-section input[required]').forEach(input => {
                    input.removeAttribute('required');
                });
                
            } else {
                deliverySection.classList.add('hidden');
                pickupSection.classList.remove('hidden');
                
                // Make pickup fields required
                document.querySelectorAll('#pickup-section input[required]').forEach(input => {
                    input.setAttribute('required', '');
                });
                
                // Remove required from delivery fields
                document.querySelectorAll('#delivery-section input[required]').forEach(input => {
                    input.removeAttribute('required');
                });
            }
        }
        
        // Handle form submission via AJAX
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
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
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show confirmation modal
                    const email = document.getElementById('email').value;
                    document.getElementById('confirmation-email').textContent = email;
                    document.getElementById('order-number').textContent = 'SWEET-' + data.order_id;
                    document.getElementById('confirmation-modal').style.display = 'flex';
                } else {
                    alert('Error: ' + (data.error || 'Failed to process your order. Please try again.'));
                    // Restore button
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
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
    
    <style>
        /* Additional styles specific to this checkout page */
        .hidden {
            display: none;
        }
        
        .form-section {
            margin-bottom: 40px;
        }
        
        .form-section h2 {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .delivery-method-summary {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px dashed #ffccd5;
        }
        
        .delivery-method-summary h3 {
            color: #ff1493;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .selected-method {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .selected-method i {
            color: #8a2be2;
            font-size: 1.2rem;
        }
        
        /* Delivery Method Selector */
        .delivery-selector {
            display: flex;
            gap: 10px;
            margin-left: auto;
            flex-wrap: wrap;
        }
        
        .method-option {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 50px;
            border: 2px solid #ffccd5;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .method-option input[type="radio"] {
            display: none;
        }
        
        .method-option.active {
            background-color: #ff69b4;
            color: white;
            border-color: #ff69b4;
        }
        
        .summary-items {
            margin-bottom: 15px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-row.discount {
            color: #ff1493;
        }
        
        .store-info {
            background-color: #fff8fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
            border: 1px dashed #ffccd5;
        }
        
        .store-info h3 {
            color: #ff1493;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .store-info p {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .store-info i {
            color: #8a2be2;
        }
        
        .store-map {
            margin-top: 15px;
        }
        
        .map-placeholder {
            height: 150px;
            background-color: #f0e6ff;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #8a2be2;
        }
        
        .map-placeholder i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .delivery-selector {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
                justify-content: space-between;
            }
            
            .method-option {
                flex: 1;
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</body>
</html>