<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Form</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
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
                <div class="order-total">
                    <span>Total:</span>
                    <span>$99.97</span>
                </div>
            </div>
            
            <div class="form-container">
                <form id="checkout-form">
                    <h2>Shipping Information</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="firstName">First Name *</label>
                                <input type="text" id="firstName" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="lastName">Last Name *</label>
                                <input type="text" id="lastName" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Street Address *</label>
                        <input type="text" id="address" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="city">City *</label>
                                <input type="text" id="city" required>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" required>
                    </div>
                    
                    <button type="submit" class="submit-btn">Complete Order</button>
                </form>
            </div>
        </div>
        
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
            // Simulate order processing
            document.getElementById('checkout-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Normally this would be a server call, but we'll simulate with a timeout
                setTimeout(function() {
                    // Generate a random order number
                    const orderNumber = 'SWEET-' + Math.floor(100000 + Math.random() * 900000);
                    
                    // Get the email to display in confirmation
                    const email = document.getElementById('email').value;
                    
                    // Update the confirmation modal
                    document.getElementById('confirmation-email').textContent = email;
                    document.getElementById('order-number').textContent = orderNumber;
                    
                    // Show the confirmation modal
                    document.getElementById('confirmation-modal').style.display = 'flex';
                    
                    // In a real application, this is where you would trigger the email sending
                    console.log('Confirmation email sent to:', email);
                }, 1500); // Simulate a 1.5 second processing time
            });
            
            function closeModal() {
                const modal = document.querySelector('.modal'); // Adjust selector if needed
                if (modal) {
                    modal.style.display = 'none';
                }

                // Redirect to the shop page
                window.location.href = 'shop.php';
            }
        </script>
    </body>
    </html>