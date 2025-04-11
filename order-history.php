<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Order History - Sweet Treats</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Base styles from your CSS */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fff8fa;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.828-1.415 1.415L51.8 0h2.827zM5.373 0l-.83.828L5.96 2.243 8.2 0H5.374zM48.97 0l3.657 3.657-1.414 1.414L46.143 0h2.828zM11.03 0L7.372 3.657 8.787 5.07 13.857 0H11.03zm32.284 0L49.8 6.485 48.384 7.9l-7.9-7.9h2.83zM16.686 0L10.2 6.485 11.616 7.9l7.9-7.9h-2.83zm20.97 0l9.315 9.314-1.414 1.414L34.828 0h2.83zM22.344 0L13.03 9.314l1.414 1.414L25.172 0h-2.83zM32 0l12.142 12.142-1.414 1.414L30 2.828 17.272 15.556l-1.414-1.414L28 2.828 17.272 14.142 15.858 12.73 28 .587l3.415 3.414L40.143 0H32zM0 0l28 28-1.414 1.414L0 2.828V0zm0 5.657l28 28L26.586 35.07 0 8.485v-2.83zm0 5.657l28 28-1.414 1.414L0 14.142v-2.83zm0 5.657l28 28L26.586 46.4 0 19.8v-2.83zm0 5.657l28 28-1.414 1.414L0 25.456v-2.83zm0 5.657l28 28-1.414 1.414L0 31.113v-2.83zM0 40l28 28-1.414 1.414L0 43.24v-3.24zm0 5.656l28 28L26.586 75.07 0 48.485v-2.83zm0 5.656l28 28-1.414 1.414L0 54.142v-2.83zm0 5.657l28 28-1.414 1.414L0 59.8v-2.83zm54.627 8.657L28 28 29.414 26.586 60 57.172v2.83zM54.627 60L28 33.373 29.414 31.96 60 62.544v-2.83zm-5.657 0L28 39.03l1.414-1.414L54.627 60h-5.657zm-5.657 0L28 44.686l1.414-1.414L48.97 60h-5.657zm-5.657 0L28 50.343l1.414-1.414L43.314 60h-5.657zm-5.657 0L28 56l1.414-1.414L37.657 60h-5.657z' fill='%23ff69b4' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        h1, h2, h3 {
            font-family: 'Fredoka One', cursive;
        }
        
        /* Order History Page Styles */
        .history-hero {
            background: linear-gradient(135deg, #ffccd5 0%, #ffd1dc 100%);
            padding: 50px 0;
            text-align: center;
            margin-bottom: 40px;
            border-radius: 0 0 50% 50% / 20px;
            position: relative;
            overflow: hidden;
        }
        
        .history-hero::before {
            content: "";
            position: absolute;
            top: -20px;
            left: -20px;
            right: -20px;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='52' height='26' viewBox='0 0 52 26' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.2'%3E%3Cpath d='M10 10c0-2.21-1.79-4-4-4-3.314 0-6-2.686-6-6h2c0 2.21 1.79 4 4 4 3.314 0 6 2.686 6 6 0 2.21 1.79 4 4 4 3.314 0 6 2.686 6 6 0 2.21 1.79 4 4 4v2c-3.314 0-6-2.686-6-6 0-2.21-1.79-4-4-4-3.314 0-6-2.686-6-6zm25.464-1.95l8.486 8.486-1.414 1.414-8.486-8.486 1.414-1.414z' /%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: 0;
        }
        
        .history-hero h1 {
            color: #ff1493;
            margin-bottom: 15px;
            font-size: 2.5rem;
            text-shadow: 3px 3px 0px rgba(255,255,255,0.5);
            position: relative;
            z-index: 1;
        }
        
        .history-hero p {
            color: #8a2be2;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Decorative candy icons */
        .candy-icon {
            position: absolute;
            opacity: 0.6;
            z-index: 0;
            animation: float 6s ease-in-out infinite;
        }
        
        .candy-1 {
            top: 20%;
            left: 5%;
            font-size: 2rem;
            color: #8a2be2;
            animation-delay: 0s;
        }
        
        .candy-2 {
            top: 60%;
            left: 15%;
            font-size: 1.5rem;
            color: #ff1493;
            animation-delay: 1s;
        }
        
        .candy-3 {
            top: 30%;
            right: 10%;
            font-size: 2.2rem;
            color: #00bfff;
            animation-delay: 2s;
        }
        
        .candy-4 {
            top: 70%;
            right: 5%;
            font-size: 1.8rem;
            color: #32cd32;
            animation-delay: 1.5s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-15px) rotate(5deg);
            }
            100% {
                transform: translateY(0) rotate(0deg);
            }
        }
        
        /* Order History Specific Styles */
        .order-history {
            margin-bottom: 60px;
        }
        
        .order-card {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255,105,180,0.15);
        }
        
        .order-header {
            background: linear-gradient(90deg, #fff0f5, #ffeaeb);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed #ffd1dc;
        }
        
        .order-number {
            color: #ff1493;
            margin: 0;
            font-size: 1.1rem;
        }
        
        .order-date {
            color: #8a2be2;
            font-weight: 600;
        }
        
        .order-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-delivered {
            background-color: #e6ffea;
            color: #32cd32;
            border: 1px solid #b3ffb3;
        }
        
        .status-processing {
            background-color: #fff8e6;
            color: #ffa500;
            border: 1px solid #ffe6b3;
        }
        
        .status-shipped {
            background-color: #e6f2ff;
            color: #0080ff;
            border: 1px solid #b3d9ff;
        }
        
        .order-items {
            padding: 0;
        }
        
        .order-item {
            display: flex;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s;
        }
        
        .order-item:hover {
            background-color: #fff8fa;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
            margin-right: 15px;
            background: #f8f0ff;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-size: 1rem;
            margin: 0 0 5px;
            color: #333;
        }
        
        .item-price {
            font-weight: 600;
            color: #8a2be2;
            margin: 0 0 8px;
        }
        
        .item-quantity {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }
        
        .item-rating {
            display: flex;
            align-items: center;
            margin-top: 8px;
        }
        
        .stars {
            display: inline-flex;
            position: relative;
        }
        
        .stars input {
            display: none;
        }
        
        .stars label {
            cursor: pointer;
            color: #ddd;
            font-size: 20px;
            padding: 0 2px;
            transition: color 0.2s;
        }
        
        .stars input:checked ~ label {
            color: #ffcc00;
        }
        
        .stars:not(:checked) > label:hover,
        .stars:not(:checked) > label:hover ~ label {
            color: #ffcc00;
        }
        
        .rating-text {
            margin-left: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .has-rated .stars label {
            pointer-events: none;
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            background-color: #fafafa;
        }
        
        .order-total {
            font-weight: 600;
            color: #ff1493;
        }
        
        .order-actions a {
            text-decoration: none;
            color: #8a2be2;
            font-weight: 600;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .order-actions a:hover {
            color: #ff1493;
        }
        
        .order-actions a + a {
            margin-left: 15px;
        }
        
        .ratings-note {
            text-align: center;
            margin: 30px 0;
            padding: 15px;
            background-color: #fff0f5;
            border-radius: 10px;
            border: 2px dashed #ffd1dc;
        }
        
        .ratings-note p {
            margin: 0;
            color: #ff1493;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-date {
                margin-top: 5px;
            }
            
            .order-item {
                flex-direction: column;
            }
            
            .item-image {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="history-hero">
        <div class="container">
            <h1>Your Sweet History</h1>
            <p>Revisit your favorite sweet memories and rate your yummy adventures!</p>
        </div>
        
        <!-- Decorative Candy Icons -->
        <span class="candy-icon candy-1">🍭</span>
        <span class="candy-icon candy-2">🍬</span>
        <span class="candy-icon candy-3">🍩</span>
        <span class="candy-icon candy-4">🍪</span>
    </div>
    
    <div class="container">
        <div class="order-history">
            <!-- Order 1 -->
            <div class="order-card">
                <div class="order-header">
                    <h3 class="order-number">Order #12345</h3>
                    <div class="order-date">April 2, 2025</div>
                    <div class="order-status status-delivered">Delivered</div>
                </div>
                <ul class="order-items">
                    <li class="order-item">
                        <div class="item-image">
                            <img src="/api/placeholder/80/80" alt="Cotton Candy Clouds">
                        </div>
                        <div class="item-details">
                            <h4 class="item-name">Cotton Candy Clouds</h4>
                            <p class="item-price">$12.99</p>
                            <p class="item-quantity">Quantity: 2</p>
                            <div class="item-rating">
                                <div class="stars">
                                    <input type="radio" id="star5-1" name="rating-1" value="5">
                                    <label for="star5-1" class="fas fa-star"></label>
                                    <input type="radio" id="star4-1" name="rating-1" value="4">
                                    <label for="star4-1" class="fas fa-star"></label>
                                    <input type="radio" id="star3-1" name="rating-1" value="3">
                                    <label for="star3-1" class="fas fa-star"></label>
                                    <input type="radio" id="star2-1" name="rating-1" value="2">
                                    <label for="star2-1" class="fas fa-star"></label>
                                    <input type="radio" id="star1-1" name="rating-1" value="1">
                                    <label for="star1-1" class="fas fa-star"></label>
                                </div>
                                <span class="rating-text">Rate this sweet!</span>
                            </div>
                        </div>
                    </li>
                    <li class="order-item">
                        <div class="item-image">
                            <img src="/api/placeholder/80/80" alt="Bubblegum Blast">
                        </div>
                        <div class="item-details">
                            <h4 class="item-name">Bubblegum Blast</h4>
                            <p class="item-price">$8.99</p>
                            <p class="item-quantity">Quantity: 1</p>
                            <div class="item-rating has-rated">
                                <div class="stars">
                                    <input type="radio" id="star5-2" name="rating-2" value="5" checked>
                                    <label for="star5-2" class="fas fa-star"></label>
                                    <input type="radio" id="star4-2" name="rating-2" value="4">
                                    <label for="star4-2" class="fas fa-star"></label>
                                    <input type="radio" id="star3-2" name="rating-2" value="3">
                                    <label for="star3-2" class="fas fa-star"></label>
                                    <input type="radio" id="star2-2" name="rating-2" value="2">
                                    <label for="star2-2" class="fas fa-star"></label>
                                    <input type="radio" id="star1-2" name="rating-2" value="1">
                                    <label for="star1-2" class="fas fa-star"></label>
                                </div>
                                <span class="rating-text">You rated: 5 stars!</span>
                            </div>
                        </div>
                    </li>
                </ul>
                <div class="order-footer">
                    <div class="order-total">Total: $34.97</div>
                    <div class="order-actions">
                        <a href="#" class="view-details">View Details</a>
                        <a href="#" class="reorder">Reorder</a>
                    </div>
                </div>
            </div>
            
            <!-- Order 2 -->
            <div class="order-card">
                <div class="order-header">
                    <h3 class="order-number">Order #12289</h3>
                    <div class="order-date">March 28, 2025</div>
                    <div class="order-status status-delivered">Delivered</div>
                </div>
                <ul class="order-items">
                    <li class="order-item">
                        <div class="item-image">
                            <img src="/api/placeholder/80/80" alt="Cherry Blast Lollipops">
                        </div>
                        <div class="item-details">
                            <h4 class="item-name">Cherry Blast Lollipops</h4>
                            <p class="item-price">$9.99</p>
                            <p class="item-quantity">Quantity: 3</p>
                            <div class="item-rating has-rated">
                                <div class="stars">
                                    <input type="radio" id="star5-3" name="rating-3" value="5">
                                    <label for="star5-3" class="fas fa-star"></label>
                                    <input type="radio" id="star4-3" name="rating-3" value="4" checked>
                                    <label for="star4-3" class="fas fa-star"></label>
                                    <input type="radio" id="star3-3" name="rating-3" value="3">
                                    <label for="star3-3" class="fas fa-star"></label>
                                    <input type="radio" id="star2-3" name="rating-3" value="2">
                                    <label for="star2-3" class="fas fa-star"></label>
                                    <input type="radio" id="star1-3" name="rating-3" value="1">
                                    <label for="star1-3" class="fas fa-star"></label>
                                </div>
                                <span class="rating-text">You rated: 4 stars!</span>
                            </div>
                        </div>
                    </li>
                </ul>
                <div class="order-footer">
                    <div class="order-total">Total: $29.97</div>
                    <div class="order-actions">
                        <a href="#" class="view-details">View Details</a>
                        <a href="#" class="reorder">Reorder</a>
                    </div>
                </div>
            </div>
            
            <!-- Order 3 -->
            <div class="order-card">
                <div class="order-header">
                    <h3 class="order-number">Order #12167</h3>
                    <div class="order-date">March 15, 2025</div>
                    <div class="order-status status-shipped">Shipped</div>
                </div>
                <ul class="order-items">
                    <li class="order-item">
                        <div class="item-image">
                            <img src="/api/placeholder/80/80" alt="Rainbow Gummy Bears">
                        </div>
                        <div class="item-details">
                            <h4 class="item-name">Rainbow Gummy Bears</h4>
                            <p class="item-price">$14.99</p>
                            <p class="item-quantity">Quantity: 1</p>
                            <div class="item-rating">
                                <div class="stars">
                                    <input type="radio" id="star5-4" name="rating-4" value="5">
                                    <label for="star5-4" class="fas fa-star"></label>
                                    <input type="radio" id="star4-4" name="rating-4" value="4">
                                    <label for="star4-4" class="fas fa-star"></label>
                                    <input type="radio" id="star3-4" name="rating-4" value="3">
                                    <label for="star3-4" class="fas fa-star"></label>
                                    <input type="radio" id="star2-4" name="rating-4" value="2">
                                    <label for="star2-4" class="fas fa-star"></label>
                                    <input type="radio" id="star1-4" name="rating-4" value="1">
                                    <label for="star1-4" class="fas fa-star"></label>
                                </div>
                                <span class="rating-text">Cannot rate yet - still in transit</span>
                            </div>
                        </div>
                    </li>
                    <li class="order-item">
                        <div class="item-image">
                            <img src="/api/placeholder/80/80" alt="Chocolate Dreams">
                        </div>
                        <div class="item-details">
                            <h4 class="item-name">Chocolate Dreams</h4>
                            <p class="item-price">$16.99</p>
                            <p class="item-quantity">Quantity: 2</p>
                            <div class="item-rating">
                                <div class="stars">
                                    <input type="radio" id="star5-5" name="rating-5" value="5">
                                    <label for="star5-5" class="fas fa-star"></label>
                                    <input type="radio" id="star4-5" name="rating-5" value="4">
                                    <label for="star4-5" class="fas fa-star"></label>
                                    <input type="radio" id="star3-5" name="rating-5" value="3">
                                    <label for="star3-5" class="fas fa-star"></label>
                                    <input type="radio" id="star2-5" name="rating-5" value="2">
                                    <label for="star2-5" class="fas fa-star"></label>
                                    <input type="radio" id="star1-5" name="rating-5" value="1">
                                    <label for="star1-5" class="fas fa-star"></label>
                                </div>
                                <span class="rating-text">Cannot rate yet - still in transit</span>
                            </div>
                        </div>
                    </li>
                </ul>
                <div class="order-footer">
                    <div class="order-total">Total: $48.97</div>
                    <div class="order-actions">
                        <a href="#" class="view-details">View Details</a>
                        <a href="#" class="track-order">Track Order</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="ratings-note">
            <p>You can only rate products from delivered orders. Thanks for your sweet feedback! 🍭</p>
        </div>
    </div>

    <script>
        // Simple script to handle star rating interactions
        document.addEventListener('DOMContentLoaded', function() {
            const ratingContainers = document.querySelectorAll('.item-rating:not(.has-rated)');
            
            ratingContainers.forEach(container => {
                const stars = container.querySelectorAll('input[type="radio"]');
                const ratingText = container.querySelector('.rating-text');
                const orderStatus = container.closest('.order-card').querySelector('.order-status').textContent.trim();
                
                // Disable rating for non-delivered items
                if (orderStatus !== 'Delivered') {
                    stars.forEach(star => {
                        star.disabled = true;
                    });
                    return;
                }
                
                stars.forEach(star => {
                    star.addEventListener('change', function() {
                        const rating = this.value;
                        ratingText.textContent = `You rated: ${rating} stars!`;
                        container.classList.add('has-rated');
                        
                        // This would normally send the rating to a server
                        console.log(`Product rated: ${rating} stars`);
                        
                        // Disable further rating
                        setTimeout(() => {
                            const labels = container.querySelectorAll('label');
                            labels.forEach(label => {
                                label.style.pointerEvents = 'none';
                            });
                        }, 100);
                    });
                });
            });
        });
    </script>
</body>
</html>