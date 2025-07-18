<div class="tab-content" id="ordersTab">
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
    
    <div class="order-history">
        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-shopping-bag" style="font-size: 3rem; color: #ffd1dc; margin-bottom: 20px;"></i>
                <p>You haven't placed any orders yet.</p>
                <a href="shop.php" class="account-btn">Shop Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <h3 class="order-number">Order #<?php echo htmlspecialchars($order['id']); ?></h3>
                        <div class="order-date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                        <div class="order-status status-<?php echo strtolower($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></div>
                    </div>
                    
                    <ul class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <?php 
                                // Get product rating if available
                                $rating_sql = "SELECT rating FROM ratings WHERE user_id = ? AND product_id = ?";
                                $rating_stmt = $conn->prepare($rating_sql);
                                $rating_stmt->bind_param("ii", $_SESSION['user_id'], $item['product_id']);
                                $rating_stmt->execute();
                                $rating_result = $rating_stmt->get_result();
                                $hasRated = false;
                                $userRating = 0;
                                
                                if ($rating_result->num_rows > 0) {
                                    $rating_data = $rating_result->fetch_assoc();
                                    $hasRated = true;
                                    $userRating = $rating_data['rating'];
                                }
                                $rating_stmt->close();
                                
                                // Fix the image path - this is the most likely cause of the issue
                                // We need to fetch the correct image from the products table
                                $product_image_sql = "SELECT image FROM products WHERE id = ?";
                                $product_image_stmt = $conn->prepare($product_image_sql);
                                $product_image_stmt->bind_param("i", $item['product_id']);
                                $product_image_stmt->execute();
                                $product_image_result = $product_image_stmt->get_result();
                                
                                // Default placeholder
                                $image_path = 'placeholder.png';
                                
                                if ($product_image_result && $product_image_result->num_rows > 0) {
                                    $product_image_data = $product_image_result->fetch_assoc();
                                    if (!empty($product_image_data['image'])) {
                                        $image_path = $product_image_data['image'];
                                    }
                                }
                                $product_image_stmt->close();
                            ?>
                            <li class="order-item">
                                <div class="item-image">
                                    <!-- Try multiple possible paths based on your project structure -->
                                    <img 
                                        src="../admin/uploads/products/<?php echo htmlspecialchars($image_path); ?>" 
                                        alt="<?php echo htmlspecialchars($item['name'] ?? 'Product'); ?>" 
                                        class="product-img"
                                        onerror="
                                            if (this.src.includes('../admin/uploads/products/')) {
                                                this.src = '/admin/uploads/products/<?php echo htmlspecialchars($image_path); ?>';
                                            } else if (this.src.includes('/admin/uploads/products/')) {
                                                this.src = '/sweet_treats/admin/uploads/products/<?php echo htmlspecialchars($image_path); ?>';
                                            } else if (this.src.includes('/sweet_treats/admin/uploads/products/')) {
                                                this.src = '../../admin/uploads/products/<?php echo htmlspecialchars($image_path); ?>';
                                            } else {
                                                this.onerror = null;
                                                this.src = '/assets/img/placeholder.png';
                                                this.classList.add('missing-img');
                                            }
                                        "
                                    >
                                </div>
                                <div class="item-details">
                                    <h4 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p class="item-price">$<?php echo number_format($item['price'], 2); ?></p>
                                    <p class="item-quantity">Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                                    
                                    <!-- Replace the rating star displays in your PHP code with this updated version -->
<?php if (strtolower($order['status']) == 'delivered'|| strtolower($order['status']) == 'completed'): ?>
    <div class="item-rating <?php echo $hasRated ? 'has-rated' : ''; ?>" 
        data-product-id="<?php echo $item['product_id']; ?>"
        data-original-text="<?php echo $hasRated ? 'You rated: ' . $userRating . ' stars!' : 'Rate this sweet!'; ?>"
        data-user-rating="<?php echo $hasRated ? $userRating : '0'; ?>">
        <div class="stars <?php echo $hasRated ? 'rated' : ''; ?>">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" id="star<?php echo $i; ?>-<?php echo $item['product_id']; ?>" 
                       name="rating-<?php echo $item['product_id']; ?>" 
                       value="<?php echo $i; ?>" 
                       <?php echo ($hasRated && $userRating == $i) ? 'checked' : ''; ?>>
                <label for="star<?php echo $i; ?>-<?php echo $item['product_id']; ?>" 
                       class="fas fa-star <?php echo ($hasRated && $i <= $userRating) ? 'active' : ''; ?>" 
                       data-rating="<?php echo $i; ?>"></label>
            <?php endfor; ?>
        </div>
        <span class="rating-text">
            <?php echo $hasRated ? 'You rated: ' . $userRating . ' stars!' : 'Rate this sweet!'; ?>
        </span>
    </div>
<?php else: ?>
    <div class="item-rating">
        <div class="stars disabled">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" disabled>
                <label class="fas fa-star"></label>
            <?php endfor; ?>
        </div>
        <span class="rating-text">Can't rate yet - still in transit</span>
    </div>
<?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="order-footer">
                        <div class="order-total">Total: $<?php echo number_format($order['total_price'], 2); ?></div>
                        <div class="order-actions">
                            <?php if (strtolower($order['status']) == 'delivered'||strtolower($order['status']) == 'completed'): ?>
                                <a href="reorder.php?id=<?php echo $order['id']; ?>" class="reorder">Reorder</a>
                            <?php else: ?>
                                <a href="track_order.php?id=<?php echo $order['id']; ?>" class="track-order">Track Order</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="ratings-note">
                <p>You can only rate products from delivered orders. Thanks for your sweet feedback! 🍭</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Consolidated and fixed JavaScript for the rating system -->
<script>document.addEventListener('DOMContentLoaded', function() {
    // Initialize the rating system when the tab is shown
    const initRatings = function() {
        if (document.querySelector('#ordersTab') && 
            document.querySelector('#ordersTab').offsetParent !== null) {
            setupRatingSystem();
            
            // Also initialize ratings for pre-rated items
            initPreRatedItems();
        }
    };
    
    function setupRatingSystem() {
        // Find all rating containers for delivered orders
        const ratingContainers = document.querySelectorAll('.item-rating .stars');
        
        ratingContainers.forEach(container => {
            const ratingContainer = container.closest('.item-rating');
            const productId = ratingContainer.dataset.productId;
            const originalText = ratingContainer.dataset.originalText;
            const stars = container.querySelectorAll('input[type="radio"]');
            const ratingText = ratingContainer.querySelector('.rating-text');
            const labels = container.querySelectorAll('label.fas.fa-star');
            
            // Skip further setup for disabled ratings
            if (container.classList.contains('disabled')) {
                return;
            }
            
            // Enhanced hover effects for star labels with direct label handling
            labels.forEach(label => {
                const rating = parseInt(label.dataset.rating);
                
                // Mouse enter effect - fill this star and all previous stars
                label.addEventListener('mouseenter', function() {
                    if (!ratingContainer.classList.contains('has-rated')) {
                        // Fill in this star and all stars before it
                        updateStarsDisplay(labels, rating, true);
                        
                        // Update rating text on hover
                        ratingText.textContent = `${rating} stars`;
                    }
                });
                
                // Click handler for the label
                label.addEventListener('click', function() {
                    if (!ratingContainer.classList.contains('has-rated')) {
                        const radioInput = document.getElementById(this.getAttribute('for'));
                        if (radioInput) {
                            radioInput.checked = true;
                            // Update display immediately
                            updateStarsDisplay(labels, rating);
                            saveRating(productId, rating, container, originalText);
                        }
                    }
                });
            });
            
            // Reset stars on mouse leave from the container
            container.addEventListener('mouseleave', function() {
                if (!ratingContainer.classList.contains('has-rated')) {
                    // Remove hover effect from all stars
                    labels.forEach(star => star.classList.remove('hover'));
                    
                    // Restore original text or show selected rating
                    const checkedStar = container.querySelector('input:checked');
                    if (!checkedStar) {
                        ratingText.textContent = originalText;
                        // Reset stars to no selection
                        updateStarsDisplay(labels, 0);
                    } else {
                        const rating = parseInt(checkedStar.value);
                        ratingText.textContent = `You rated: ${rating} stars!`;
                        updateStarsDisplay(labels, rating);
                    }
                }
            });
            
            // Handle star click via radio buttons
            stars.forEach(star => {
                star.addEventListener('change', function() {
                    const rating = parseInt(this.value);
                    saveRating(productId, rating, container, originalText);
                    updateStarsDisplay(labels, rating);
                });
            });
        });
    }
    
    // Call immediately for first load
    initRatings();
    
    // Also attach to any tab change events you might have
    if (typeof $ !== 'undefined') { // Check if jQuery is available
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (e.target.getAttribute('href') === '#ordersTab' || 
                e.target.getAttribute('data-target') === '#ordersTab') {
                initRatings();
            }
        });
    }
});

function initPreRatedItems() {
    // Find all items that already have ratings
    const ratedContainers = document.querySelectorAll('.item-rating.has-rated');
    
    ratedContainers.forEach(container => {
        // Extract user rating from text or data attribute
        const ratingText = container.querySelector('.rating-text');
        if (!ratingText) return;
        
        const ratingMatch = ratingText.textContent.match(/(\d+)\s+stars?/i);
        if (!ratingMatch) return;
        
        const userRating = parseInt(ratingMatch[1], 10);
        if (isNaN(userRating) || userRating < 1 || userRating > 5) return;
        
        // Apply active class to appropriate stars
        const stars = container.querySelectorAll('label.fas.fa-star');
        stars.forEach(star => {
            const starRating = parseInt(star.dataset.rating);
            if (starRating <= userRating) {
                star.classList.add('active');
            }
        });
    });
}

// Helper function to update star display based on rating
function updateStarsDisplay(labels, rating, isHover = false) {
    labels.forEach(star => {
        const starRating = parseInt(star.dataset.rating);
        
        // Remove any previous classes first
        star.classList.remove('active', 'hover');
        
        if (isHover) {
            // For hover effect
            if (starRating <= rating) {
                star.classList.add('hover');
            }
        } else {
            // For permanent selection
            if (starRating <= rating) {
                star.classList.add('active');
            }
        }
    });
}


function saveRating(productId, rating, container, originalText) {
    // Get current user ID from session
    const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
    
    if (!userId) {
        alert('You must be logged in to rate products.');
        return;
    }
    
    // Create form data for the AJAX request
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('rating', rating);
    formData.append('action', 'save_rating');
    
    // Show loading indicator
    const ratingContainer = container.closest('.item-rating');
    const ratingText = ratingContainer.querySelector('.rating-text');
    ratingText.textContent = 'Saving...';
    
    // Update data-user-rating attribute for future reference
    ratingContainer.dataset.userRating = rating;
    
    // Send AJAX request to the correct path
    fetch('partials/rating_handler.php', {  // Adjusted to partials folder
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update the UI to reflect the saved rating
            ratingContainer.classList.add('has-rated');
            container.classList.add('rated');
            ratingText.textContent = `You rated: ${rating} stars!`;
            
            // Update the data-original-text attribute for future reference
            ratingContainer.dataset.originalText = `You rated: ${rating} stars!`;
            
            // Make sure stars are properly highlighted
            const labels = container.querySelectorAll('label.fas.fa-star');
            updateStarsDisplay(labels, rating);
            
            // Show confirmation message
            const confirmMsg = document.createElement('span');
            confirmMsg.className = 'rating-saved';
            confirmMsg.textContent = 'Rating saved!';
            ratingText.after(confirmMsg);
            
            // Remove confirmation after animation
            setTimeout(() => {
                confirmMsg.remove();
            }, 2000);
            
            // Update product average rating if needed
            if (data.average_rating) {
                // If you have a place to display the average rating on page, update it
                const avgRatingElements = document.querySelectorAll(`.product-avg-rating[data-product-id="${productId}"]`);
                avgRatingElements.forEach(el => {
                    el.textContent = parseFloat(data.average_rating).toFixed(1);
                });
            }
        } else {
            console.error('Failed to save rating:', data.message);
            ratingText.textContent = originalText;
            alert('Could not save your rating. ' + (data.message || 'Please try again.'));
        }
    })
    .catch(error => {
        console.error('Error saving rating:', error);
        ratingText.textContent = originalText;
        alert('An error occurred while saving your rating: ' + error.message);
    });
}
</script>
<!-- Add this CSS to enhance the star rating hover effect -->
<style>.stars label.fas.fa-star {
    cursor: pointer;
    transition: all 0.2s ease;
    color: #e0e0e0; /* Default color for inactive stars */
}

/* Hover effect */
.stars:not(.disabled):not(.rated) label.fas.fa-star.hover {
    color: #FFD700 !important; /* Gold color for hover effect */
    transform: scale(1.2);
    text-shadow: 0 0 5px rgba(255, 215, 0, 0.7);
}

/* Fixed: Active stars (permanently selected) */
.stars label.fas.fa-star.active {
    color: #FFD700 !important; /* Gold color for active stars */
}

/* Override any other star coloring that might be happening */
.stars.rated label.fas.fa-star {
    color: #e0e0e0; /* Reset all stars in rated container to gray */
}

/* Then explicitly set active ones to gold */
.stars.rated label.fas.fa-star.active {
    color: #FFD700 !important; /* Gold color for active stars in rated container */
}

/* Fix the reverse order for star ratings to work properly */
.stars {
    display: inline-flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.stars input[type="radio"] {
    display: none;
}

.stars:not(.disabled) label {
    margin: 0 2px;
    font-size: 1.25em;
}

.rating-saved {
    color: #4CAF50;
    margin-left: 8px;
    font-size: 0.9em;
    animation: fadeOut 2s forwards;
    display: inline-block;
}

@keyframes fadeOut {
    0% { opacity: 1; }
    70% { opacity: 1; }
    100% { opacity: 0; }
}

/* Add some additional styling for the order cards */
.order-card {
    margin-bottom: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s ease;
}

.order-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.item-image img {
    max-width: 80px;
    max-height: 80px;
    border-radius: 5px;
    object-fit: cover;
}
</style>