document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const loggedInContainer = document.querySelector('.logged-in-container');
    if (loggedInContainer) {
        loggedInContainer.style.display = 'block';
    }
    const tabs = document.querySelectorAll('.account-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Initialize tabs - hide all except the active one
    function initializeTabs() {
        // Hide all tab contents first
        tabContents.forEach(content => {
            content.style.display = 'none';
        });
        
        // Show only the active tab content
        const activeTab = document.querySelector('.account-tab.active');
        if (activeTab) {
            const activeTabId = activeTab.getAttribute('data-tab');
            const activeContent = document.getElementById(activeTabId + 'Tab');
            if (activeContent) {
                activeContent.style.display = 'block';
            } else {
                console.error(`Tab content with ID ${activeTabId}Tab not found`);
                // Set default tab if active tab content not found
                if (tabContents.length > 0) {
                    tabContents[0].style.display = 'block';
                    tabs[0].classList.add('active');
                }
            }
        } else {
            // No active tab found, set first tab as active
            if (tabs.length > 0 && tabContents.length > 0) {
                tabs[0].classList.add('active');
                tabContents[0].style.display = 'block';
            }
        }
    }
    
    // Initialize on page load
    initializeTabs();
    
    // Handle tab clicks
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Show selected tab content
            const tabId = this.getAttribute('data-tab');
            const selectedContent = document.getElementById(tabId + 'Tab');
            
            if (selectedContent) {
                selectedContent.style.display = 'block';
                
                // Initialize rating system if orders tab is selected
                if (tabId === 'orders') {
                    setupRatingSystem();
                }
            } else {
                console.error(`Tab content with ID ${tabId}Tab not found`);
            }
        });
    });
    
    // Check if orders tab is visible initially and set up rating system
    const ordersTab = document.getElementById('ordersTab');
    if (ordersTab && ordersTab.style.display === 'block') {
        setupRatingSystem();
    }
    
    // Product rating functionality
    function setupRatingSystem() {
        // Find all rating containers for delivered orders
        const ratingContainers = document.querySelectorAll('.item-rating:not(.has-rated) .stars:not(.disabled)');
        
        ratingContainers.forEach(container => {
            const ratingContainer = container.closest('.item-rating');
            const productId = ratingContainer.dataset.productId;
            const originalText = ratingContainer.dataset.originalText;
            const stars = container.querySelectorAll('input[type="radio"]');
            const ratingText = ratingContainer.querySelector('.rating-text');
            const labels = container.querySelectorAll('label.fas.fa-star');
            
            // Enhanced hover effects for star labels with direct label handling
            labels.forEach(label => {
                const rating = label.dataset.rating; // Get rating from data attribute
                
                // Mouse enter effect - fill this star and all previous stars
                label.addEventListener('mouseenter', function() {
                    if (!ratingContainer.classList.contains('has-rated')) {
                        // Fill in this star and all stars before it
                        labels.forEach(star => {
                            if (parseInt(star.dataset.rating) <= parseInt(rating)) {
                                star.classList.add('hover');
                            }
                        });
                        
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
                    } else {
                        ratingText.textContent = `You rated: ${checkedStar.value} stars!`;
                    }
                }
            });
            
            // Handle star click via radio buttons
            stars.forEach(star => {
                star.addEventListener('change', function() {
                    const rating = this.value;
                    saveRating(productId, rating, container, originalText);
                });
            });
        });
    }
    
    function saveRating(productId, rating, container, originalText) {
        // Show loading indicator
        const ratingContainer = container.closest('.item-rating');
        const ratingText = ratingContainer.querySelector('.rating-text');
        ratingText.textContent = 'Saving...';
        
        // Create form data for the AJAX request
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('rating', rating);
        formData.append('action', 'save_rating');
        
        // Send AJAX request
        fetch('./partials/rating_handler.php', {  // Updated path to correct directory
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
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
            alert('An error occurred while saving your rating. Please try again.');
        });
    }
});