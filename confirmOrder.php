<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Form</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/confirmOrder.css">
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
            <div class="order-item">
                <div>Cotton Candy Dreams Plush</div>
                <div>$29.99</div>
            </div>
            <div class="order-item">
                <div>Rainbow Sprinkle Cushion</div>
                <div>$49.99</div>
            </div>
            <div class="order-item">
                <div>Bubblegum Scented Notebook</div>
                <div>$19.99</div>
            </div>
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
                    <div class="form-col">
                        <div class="form-group">
                            <label for="state">State/Province *</label>
                            <input type="text" id="state" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="zip">Zip/Postal Code *</label>
                            <input type="text" id="zip" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="country">Country *</label>
                            <select id="country" required>
                                <option value="">Select Country</option>
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="GB">United Kingdom</option>
                                <option value="AU">Australia</option>
                                <option value="FR">France</option>
                                <option value="DE">Germany</option>
                                <option value="JP">Japan</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" required>
                </div>
                
                <h2>Payment Information</h2>
                <div class="form-group">
                    <label for="cardName">Name on Card *</label>
                    <input type="text" id="cardName" required>
                </div>
                
                <div class="form-group">
                    <label for="cardNumber">Card Number *</label>
                    <input type="text" id="cardNumber" placeholder="XXXX XXXX XXXX XXXX" required>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="expDate">Expiration Date *</label>
                            <input type="text" id="expDate" placeholder="MM/YY" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="cvv">CVV *</label>
                            <input type="text" id="cvv" placeholder="123" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Order Notes (Optional)</label>
                    <textarea id="notes" rows="3" placeholder="Special instructions for delivery"></textarea>
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
        // Function to get a cookie value by name
        function getCookie(name) {
            let nameEQ = name + "=";
            let ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        // Autofill the form with data from cookies on page load
        window.onload = function() {
            document.getElementById('firstName').value = getCookie('firstName');
            document.getElementById('lastName').value = getCookie('lastName');
            document.getElementById('email').value = getCookie('email');
        };

        // Simulate order processing
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Save user details to cookies
            document.cookie = "firstName=" + document.getElementById('firstName').value + "; path=/";
            document.cookie = "lastName=" + document.getElementById('lastName').value + "; path=/";
            document.cookie = "email=" + document.getElementById('email').value + "; path=/";
            
            // Continue with order processing...
            setTimeout(function() {
                const orderNumber = 'SWEET-' + Math.floor(100000 + Math.random() * 900000);
                const email = document.getElementById('email').value;
                document.getElementById('confirmation-email').textContent = email;
                document.getElementById('order-number').textContent = orderNumber;
                document.getElementById('confirmation-modal').style.display = 'flex';
                console.log('Confirmation email sent to:', email);
            }, 1500);
        });

        // Function to close the modal
        function closeModal() {
            document.getElementById('confirmation-modal').style.display = 'none';
        }
    </script>
</body>

    </script>
</body>
</html>