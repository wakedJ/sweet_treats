<?php
// Start the session to maintain user state
session_start();

// Include database connection
require_once "includes/db.php";

// Initialize user variables
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$is_logged_in = isset($_SESSION['user_id']);

// Handle quantity updates via AJAX
if (isset($_POST['action']) && $_POST['action'] == 'update_quantity') {
    $cart_item_id = $_POST['cart_item_id'];
    $new_quantity = $_POST['quantity'];
    
    // If logged in, update in database
    if ($is_logged_in) {
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND user_id = ?");
        $stmt->bind_param("iii", $new_quantity, $cart_item_id, $user_id);
        $stmt->execute();
    } else {
        // Update in session cart
        if (isset($_SESSION['cart'][$cart_item_id])) {
            $_SESSION['cart'][$cart_item_id]['quantity'] = $new_quantity;
        }
    }
    
    // Return updated price information
    echo json_encode(['success' => true]);
    exit();
}

// Handle item deletion
if (isset($_POST['action']) && $_POST['action'] == 'delete_item') {
    $cart_item_id = $_POST['cart_item_id'];
    
    // If logged in, delete from database
    if ($is_logged_in) {
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_item_id, $user_id);
        $stmt->execute();
    } else {
        // Remove from session cart
        if (isset($_SESSION['cart'][$cart_item_id])) {
            unset($_SESSION['cart'][$cart_item_id]);
        }
    }
    
    // Return success
    echo json_encode(['success' => true]);
    exit();
}

// Get delivery rules
$delivery_query = "SELECT * FROM delivery_rules ORDER BY id DESC LIMIT 1";
$delivery_result = $conn->query($delivery_query);
$delivery_rules = $delivery_result->fetch_assoc();

// Default delivery fee if no rules found
$standard_delivery_fee = $delivery_rules ? $delivery_rules['standard_delivery_fee'] : 3.00;
$min_order_for_free_delivery = $delivery_rules ? $delivery_rules['min_order_for_free_delivery'] : 50.00;

// Initialize promo code variables
$promo_discount = 0;
$promo_message = "";
$applied_promo = "";

// Handle promo code application
if (isset($_POST['apply_promo'])) {
    $promo_code = trim($_POST['promo_code']);
    
    // Validate promo code
    $stmt = $conn->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1 AND current_uses < max_uses AND NOW() BETWEEN start_date AND end_date");
    $stmt->bind_param("s", $promo_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $promo = $result->fetch_assoc();
        $_SESSION['applied_promo'] = $promo;
        $applied_promo = $promo_code;
        $promo_message = "Promo code applied successfully!";
    } else {
        $promo_message = "Invalid or expired promo code.";
    }
}

// Get the promo code from session if previously applied
if (isset($_SESSION['applied_promo'])) {
    $applied_promo = $_SESSION['applied_promo']['code'];
}

$cart_items = [];
$subtotal = 0;

if ($is_logged_in) {
    // Fetch cart items from database for logged-in users
    $cart_query = "SELECT ci.cart_item_id, ci.quantity, p.id as product_id, p.name, p.price, p.image, p.description 
                  FROM cart_items ci
                  JOIN products p ON ci.product_id = p.id
                  WHERE ci.user_id = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();

    // Process cart items
    while ($item = $cart_result->fetch_assoc()) {
        $item['total'] = $item['price'] * $item['quantity'];
        $subtotal += $item['total'];
        $cart_items[] = $item;
    }
} else {
    // Use session cart for non-logged-in users
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // If session cart exists, fetch product details for each item
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $stmt = $conn->prepare("SELECT id, name, price, image, description FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($product = $result->fetch_assoc()) {
                $cart_item = [
                    'cart_item_id' => $product_id, // Use product_id as identifier
                    'product_id' => $product_id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image_url' => $product['image'],
                    'description' => $product['description'],
                    'quantity' => $item['quantity'],
                    'total' => $product['price'] * $item['quantity']
                ];
                
                $subtotal += $cart_item['total'];
                $cart_items[] = $cart_item;
            }
        }
    }
}

// Calculate promo discount if applicable
if (isset($_SESSION['applied_promo'])) {
    $promo = $_SESSION['applied_promo'];
    
    // Check minimum order value
    if ($subtotal >= $promo['minimum_order_value']) {
        if ($promo['discount_type'] == 'percentage') {
            $promo_discount = $subtotal * ($promo['discount_value'] / 100);
        } else {
            $promo_discount = $promo['discount_value'];
        }
    } else {
        $promo_message = "Minimum order value not met for this promo code.";
        unset($_SESSION['applied_promo']);
        $applied_promo = "";
    }
}

// Calculate if eligible for free delivery
$delivery_fee = $standard_delivery_fee;
if ($subtotal >= $min_order_for_free_delivery) {
    $delivery_fee = 0;
}

// Initialize delivery type (default to pickup/store pickup which is free)
$selectedDeliveryType = isset($_SESSION['delivery_type']) ? $_SESSION['delivery_type'] : 'pickup';
$displayDeliveryFee = ($selectedDeliveryType == 'delivery') ? $delivery_fee : 0;
$displayTotal = $subtotal - $promo_discount + $displayDeliveryFee;

// Save cart info in session for checkout
$_SESSION['cart_summary'] = [
    'subtotal' => $subtotal,
    'promo_discount' => $promo_discount,
    'delivery_fee' => $displayDeliveryFee,
    'total' => $displayTotal,
    'delivery_type' => $selectedDeliveryType,
    'applied_promo' => $applied_promo
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Sweet Treats</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">  
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
    <?php include "includes/header.php" ?>
    
    <!-- Cart Hero Section -->
    <div class="cart-hero">
        <div class="candy-icon candy-1"><i class="fas fa-candy-cane"></i></div>
        <div class="candy-icon candy-2"><i class="fas fa-cookie"></i></div>
        <div class="candy-icon candy-3"><i class="fas fa-ice-cream"></i></div>
        <div class="candy-icon candy-4"><i class="fas fa-birthday-cake"></i></div>
        
        <div class="container">
            <h1>Your Sweet Cart</h1>
            <p>Review your treats before checkout</p>
        </div>
    </div>
    
    <!-- Cart Content -->
    <div class="container">
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any treats to your cart yet.</p>
                <a href="shop.php" class="continue-shopping-btn">
                    <i class="fas fa-cookie-bite"></i> Explore Sweet Treats
                </a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-header">
                    <div>Image</div>
                    <div>Item</div>
                    <div>Qty</div>
                    <div>Price</div>
                    <div>Total</div>
                    <div>Action</div>
                </div>
                
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" data-item-id="<?php echo $item['cart_item_id']; ?>">
                        <div class="cart-image">
                            <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                        </div>
                        <div class="cart-name">
                            <h3><?php echo $item['name']; ?></h3>
                        </div>
                        <div class="cart-quantity">
                            <button class="quantity-btn decrease">-</button>
                            <input type="text" class="quantity-input" value="<?php echo $item['quantity']; ?>" readonly>
                            <button class="quantity-btn increase">+</button>
                        </div>
                        <div class="cart-price"><?php echo number_format($item['price'], 2); ?>$</div>
                        <div class="cart-total"><?php echo number_format($item['total'], 2); ?>$</div>
                        <div class="cart-actions">
                            <button class="delete-btn" title="Remove item">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Cart Summary Section -->
            <div class="cart-summary">
                <!-- Delivery Options -->
                <div class="delivery-options">
                    <h2>Delivery Options</h2>
                    
                    <div class="delivery-option <?php echo $selectedDeliveryType == 'pickup' ? 'selected' : ''; ?>">
                        <input type="radio" name="delivery" id="store-pickup" value="pickup" <?php echo $selectedDeliveryType == 'pickup' ? 'checked' : ''; ?>>
                        <label for="store-pickup">
                            Store Pickup
                            <span class="delivery-time">Available in 3 days</span>
                        </label>
                        <span class="delivery-price">FREE</span>
                    </div>
                    
                    <div class="delivery-option <?php echo $selectedDeliveryType == 'delivery' ? 'selected' : ''; ?>">
                        <input type="radio" name="delivery" id="home-delivery" value="delivery" <?php echo $selectedDeliveryType == 'delivery' ? 'checked' : ''; ?>>
                        <label for="home-delivery">
                            Delivery at Home
                            <span class="delivery-time">2-3 days delivery time</span>
                        </label>
                        <span class="delivery-price"><?php echo $delivery_fee > 0 ? number_format($delivery_fee, 2) . '$' : 'FREE'; ?></span>
                    </div>
                    
                    <div class="promo-code">
                        <h3>Promo Code</h3>
                        <form method="post" action="" class="promo-form">
                            <div class="promo-input">
                                <input type="text" name="promo_code" placeholder="Enter promo code" value="<?php echo $applied_promo; ?>">
                                <button type="submit" name="apply_promo" class="apply-btn">Apply</button>
                            </div>
                            <?php if ($promo_message): ?>
                                <div class="promo-message <?php echo strpos($promo_message, 'successfully') !== false ? 'success' : 'error'; ?>">
                                    <?php echo $promo_message; ?>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    
                    <div class="summary-row">
                        <div>Subtotal</div>
                        <div id="subtotal"><?php echo number_format($subtotal, 2); ?>$</div>
                    </div>
                    
                    <?php if ($promo_discount > 0): ?>
                    <div class="summary-row discount">
                        <div>Discount</div>
                        <div id="discount">-<?php echo number_format($promo_discount, 2); ?>$</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <div>Delivery</div>
                        <div id="delivery-fee"><?php echo $displayDeliveryFee > 0 ? number_format($displayDeliveryFee, 2) . '$' : 'FREE'; ?></div>
                    </div>
                    
                    <div class="summary-row total">
                        <div>Total</div>
                        <div id="order-total"><?php echo number_format($displayTotal, 2); ?>$</div>
                    </div>

                   
                    <!-- Checkout button -->
                    <button class="checkout-btn">
                        <a href="<?php echo $is_logged_in ? 'checkout.php' : 'login.php?redirect=checkout'; ?>">
                            Proceed to Checkout <i class="fas fa-arrow-right"></i>
                        </a>
                    </button>

                    <!-- Continue shopping link -->
                    <a href="index.php" class="continue-shopping">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include "includes/footer.php" ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Delivery option selection
            const deliveryOptions = document.querySelectorAll('.delivery-option');
            const deliveryFee = document.getElementById('delivery-fee');
            const orderTotal = document.getElementById('order-total');
            const subtotal = <?php echo $subtotal - $promo_discount; ?>;
            
            deliveryOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    deliveryOptions.forEach(opt => opt.classList.remove('selected'));
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Update the radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Get delivery type
                    const deliveryType = radio.value;
                    
                    // Save delivery type via AJAX
                    $.ajax({
                        url: 'update_delivery.php',
                        type: 'POST',
                        data: { delivery_type: deliveryType },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Update UI
                                const fee = deliveryType === 'pickup' ? 0 : <?php echo $delivery_fee; ?>;
                                deliveryFee.textContent = fee === 0 ? 'FREE' : fee.toFixed(2) + '$';
                                orderTotal.textContent = (subtotal + fee).toFixed(2) + '$';
                            }
                        }
                    });
                });
            });
            
            // Quantity update
            $('.quantity-btn').on('click', function() {
                const itemContainer = $(this).closest('.cart-item');
                const cartItemId = itemContainer.data('item-id');
                const quantityInput = itemContainer.find('.quantity-input');
                const currentQuantity = parseInt(quantityInput.val());
                
                let newQuantity = currentQuantity;
                
                if ($(this).hasClass('increase')) {
                    newQuantity = currentQuantity + 1;
                } else if ($(this).hasClass('decrease') && currentQuantity > 1) {
                    newQuantity = currentQuantity - 1;
                }
                
                if (newQuantity !== currentQuantity) {
                    // Update quantity in database
                    $.ajax({
                        url: 'cart.php',
                        type: 'POST',
                        data: {
                            action: 'update_quantity',
                            cart_item_id: cartItemId,
                            quantity: newQuantity
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Reload page to reflect changes
                                location.reload();
                            }
                        }
                    });
                }
            });
            
            // Delete item
            $('.delete-btn').on('click', function() {
                if (confirm('Are you sure you want to remove this item from your cart?')) {
                    const itemContainer = $(this).closest('.cart-item');
                    const cartItemId = itemContainer.data('item-id');
                    
                    $.ajax({
                        url: 'cart.php',
                        type: 'POST',
                        data: {
                            action: 'delete_item',
                            cart_item_id: cartItemId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Reload page to reflect changes
                                location.reload();
                            }
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>