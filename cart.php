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
        <div class="cart-container">
            <div class="cart-header">
                <div>Image</div>
                <div>Item</div>
                <div>Qty</div>
                <div>Price</div>
                <div>Total</div>
            </div>
            
            <!-- Cart Item 1 -->
            <div class="cart-item">
                <div class="cart-image">
                    <img src="images/products/lays_sourcream.jpg" alt="Lays Sour Cream & Onion">
                </div>
                <div class="cart-name">
                    <h3>Lays Sour Cream & Onion Chips</h3>
                </div>
                <div class="cart-quantity">
                    <button class="quantity-btn">-</button>
                    <input type="text" class="quantity-input" value="1" readonly>
                    <button class="quantity-btn">+</button>
                </div>
                <div class="cart-price">2$</div>
                <div class="cart-total">2$</div>
            </div>
            
            <!-- Cart Item 2 -->
            <div class="cart-item">
                <div class="cart-image">
                    <img src="images/products/nerds.jpg" alt="Nerds Gummy Clusters">
                </div>
                <div class="cart-name">
                    <h3>Nerds</h3>
                </div>
                <div class="cart-quantity">
                    <button class="quantity-btn">-</button>
                    <input type="text" class="quantity-input" value="4" readonly>
                    <button class="quantity-btn">+</button>
                </div>
                <div class="cart-price">1.5$</div>
                <div class="cart-total">6$</div>
            </div>
            
            <!-- Cart Item 3 -->
            <div class="cart-item">
                <div class="cart-image">
                    <img src="images/products/Sparkling-ChupaChups-Strawberry&Cream.jpg" alt="ChupaChups Strawberry & Cream">
                </div>
                <div class="cart-name">
                    <h3>Chupa Chups Strawberry & Cream</h3>
                </div>
                <div class="cart-quantity">
                    <button class="quantity-btn">-</button>
                    <input type="text" class="quantity-input" value="1" readonly>
                    <button class="quantity-btn">+</button>
                </div>
                <div class="cart-price">4$</div>
                <div class="cart-total">4$</div>
            </div>
        </div>
        
        <!-- Cart Summary Section -->
        <div class="cart-summary">
            <!-- Delivery Options -->
            <div class="delivery-options">
                <h2>Delivery Options</h2>
                
                <div class="delivery-option selected">
                    <input type="radio" name="delivery" id="store-pickup" checked>
                    <label for="store-pickup">
                        Store Pickup
                        <span class="delivery-time">Available in 3 days</span>
                    </label>
                    <span class="delivery-price">FREE</span>
                </div>
                
                <div class="delivery-option">
                    <input type="radio" name="delivery" id="home-delivery">
                    <label for="home-delivery">
                        Delivery at Home
                        <span class="delivery-time">2-3 days delivery time</span>
                    </label>
                    <span class="delivery-price">3$</span>
                </div>
                
                <div class="promo-code">
                    <h3>Promo Code</h3>
                    <div class="promo-input">
                        <input type="text" placeholder="Enter promo code">
                        <button class="apply-btn">Apply</button>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <h2>Order Summary</h2>
                
            <div class="summary-row">
                <div>Subtotal</div>
                <div>12$</div>
            </div>
            <div class="summary-row">
                <div>Delivery</div>
                <div id="delivery-fee">3$</div>
            </div>
            <div class="summary-row total">
                <div>Total</div>
                <div id="order-total">15$</div>
            </div>

            <!-- Add the checkout button -->
            <button class="checkout-btn">
            <a href="checkout.php">
                Proceed to Checkout <i class="fas fa-arrow-right"></i>
                </a>
            </button>

            <!-- Add the continue shopping link -->
            <a href="index.php" class="continue-shopping">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
    </div>
    <script>

            const deliveryOptions = document.querySelectorAll('.delivery-option');
            const deliveryFee = document.getElementById('delivery-fee');
            const orderTotal = document.getElementById('order-total');
            const subtotal = 12; // This should be calculated from your cart items

            deliveryOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    deliveryOptions.forEach(opt => opt.classList.remove('selected'));
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Update the radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Update delivery fee and total
                    const fee = this.querySelector('.delivery-price').textContent === 'FREE' ? 0 : 3;
                    deliveryFee.textContent = fee === 0 ? 'FREE' : fee + '$';
                    orderTotal.textContent = (subtotal + fee) + '$';
                });
            });
    </script>
</body>