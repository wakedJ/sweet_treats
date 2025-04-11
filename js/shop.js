document.addEventListener('DOMContentLoaded', function() {
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const categoryParam = urlParams.get('category');
    
    // Get all products and store the original list
    const productCards = document.querySelectorAll('.product-card');
    const productsGrid = document.querySelector('.products-grid');
    const originalProducts = Array.from(productCards);
    
    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    const searchButton = document.querySelector('.search-button');
    
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        // If search term is empty, show all products
        if (searchTerm === '') {
            resetFilters();
            return;
        }
        
        // Clear the products grid
        productsGrid.innerHTML = '';
        
        // Filter products based on search term
        const filteredProducts = originalProducts.filter(product => {
            const productName = product.querySelector('h3').textContent.toLowerCase();
            return productName.includes(searchTerm);
        });
        
        // Display filtered products or show "no results" message
        if (filteredProducts.length > 0) {
            filteredProducts.forEach(product => {
                productsGrid.appendChild(product.cloneNode(true));
            });
            attachAddToCartListeners();
        } else {
            productsGrid.innerHTML = '<div class="no-results">No products found matching your search.</div>';
        }
    }
    
    // Add event listeners for search
    searchButton.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    
    // Filter functionality
    const categoryFilter = document.querySelector('.filter-group:nth-child(1) .filter-select');
    const sortFilter = document.querySelector('.filter-group:nth-child(2) .filter-select');
    const priceFilter = document.querySelector('.filter-group:nth-child(3) .filter-select');
    
    function applyFilters() {
        // Get filter values
        const categoryValue = categoryFilter.value;
        const sortValue = sortFilter.value;
        const priceValue = priceFilter.value;
        
        // Start with all original products
        let filteredProducts = [...originalProducts];
        
        // Apply category filter
        if (categoryValue) {
            filteredProducts = filteredProducts.filter(product => {
                const categoryString = product.getAttribute('data-category') || '';
                return categoryString === categoryValue;
            });
        }
        
        // Apply price filter
        if (priceValue) {
            filteredProducts = filteredProducts.filter(product => {
                const priceElement = product.querySelector('.product-price');
                const price = parseFloat(priceElement.textContent.replace('$', ''));
                
                switch(priceValue) {
                    case 'under-5':
                        return price < 5;
                    case '5-10':
                        return price >= 5 && price <= 10;
                    case '10-20':
                        return price > 10 && price <= 20;
                    case 'over-20':
                        return price > 20;
                    default:
                        return true;
                }
            });
        }
        
        // Apply sorting
        if (sortValue) {
            filteredProducts.sort((a, b) => {
                const getPriceValue = product => {
                    const priceText = product.querySelector('.product-price').textContent;
                    return parseFloat(priceText.replace('$', ''));
                };
                
                const getRatingValue = product => {
                    const ratingText = product.querySelector('.rating-number').textContent;
                    return parseFloat(ratingText.replace('(', '').replace(')', ''));
                };
                
                switch(sortValue) {
                    case 'price-low':
                        return getPriceValue(a) - getPriceValue(b);
                    case 'price-high':
                        return getPriceValue(b) - getPriceValue(a);
                    case 'rating':
                        return getRatingValue(b) - getRatingValue(a);
                    case 'newest':
                        // This would require additional data, using product ID as a proxy
                        const getIdValue = product => {
                            const idAttr = product.querySelector('.add-to-cart-btn').getAttribute('data-product-id');
                            return parseInt(idAttr);
                        };
                        return getIdValue(b) - getIdValue(a);
                    default:
                        return 0; // featured - keep original order
                }
            });
        }
        
        // Update the products grid
        productsGrid.innerHTML = '';
        
        if (filteredProducts.length > 0) {
            filteredProducts.forEach(product => {
                productsGrid.appendChild(product.cloneNode(true));
            });
            attachAddToCartListeners();
        } else {
            productsGrid.innerHTML = '<div class="no-results">No products match the selected filters.</div>';
        }
    }
    
    // Add event listeners for filters
    categoryFilter.addEventListener('change', applyFilters);
    sortFilter.addEventListener('change', applyFilters);
    priceFilter.addEventListener('change', applyFilters);
    
    // Reset filters function
    function resetFilters() {
        // Reset all filter dropdowns
        categoryFilter.value = '';
        sortFilter.value = 'featured';
        priceFilter.value = '';
        
        // Clear search input
        searchInput.value = '';
        
        // Restore original products
        productsGrid.innerHTML = '';
        originalProducts.forEach(product => {
            productsGrid.appendChild(product.cloneNode(true));
        });
        
        // Reattach event listeners
        attachAddToCartListeners();
    }
    
    // Add reset button
    const filterRow = document.querySelector('.filter-row');
    const resetButton = document.createElement('button');
    resetButton.className = 'reset-filters-btn';
    resetButton.innerHTML = '<i class="fas fa-undo"></i> Reset';
    resetButton.addEventListener('click', resetFilters);
    filterRow.appendChild(resetButton);
    
    // Handle filters dropdown toggle
    const filtersDropdown = document.querySelector('.filters-dropdown');
    const filtersBtn = document.querySelector('.filters-btn');
    
    if (filtersBtn) {
        filtersBtn.addEventListener('click', function() {
            filtersDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (filtersDropdown && !filtersDropdown.contains(event.target) && event.target !== filtersBtn) {
                filtersDropdown.classList.remove('show');
            }
        });
    }
    
    // Function to attach add to cart listeners
    function attachAddToCartListeners() {
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                
                // Add 'added' class for animation
                this.classList.add('added');
                
                // Remove the class after animation completes
                setTimeout(() => {
                    this.classList.remove('added');
                }, 500);
                
                // Create confetti effect
                createConfetti();
                
                // Here you would typically have AJAX to add to cart
                console.log(`Product ${productId} added to cart!`);
                
                // Update cart count in header (example)
                const cartCountElement = document.querySelector('.cart-count');
                if (cartCountElement) {
                    let currentCount = parseInt(cartCountElement.textContent);
                    cartCountElement.textContent = currentCount + 1;
                }
            });
        });
    }
    
    // Confetti animation function
    function createConfetti() {
        for (let i = 0; i < 30; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            
            // Random position
            confetti.style.left = Math.random() * 100 + 'vw';
            
            // Random delay
            confetti.style.animationDelay = Math.random() * 2 + 's';
            
            // Random shape
            const shape = Math.floor(Math.random() * 3);
            if (shape === 0) {
                confetti.style.borderRadius = '50%';
            } else if (shape === 1) {
                confetti.style.width = '7px';
                confetti.style.height = '14px';
            }
            
            document.body.appendChild(confetti);
            
            // Remove confetti after animation
            setTimeout(() => {
                confetti.remove();
            }, 5000);
        }
    }
    
    // If category parameter exists, select it in the dropdown
    if (categoryParam) {
        if (categoryFilter) {
            categoryFilter.value = categoryParam;
            
            // Trigger the change event to apply the filter
            const changeEvent = new Event('change');
            categoryFilter.dispatchEvent(changeEvent);
        }
    }
    
    // Initial setup
    attachAddToCartListeners();
    
    // SCROLL UP BUTTON - Fixed and tested scroll function
    // Check if the button exists, if not, create it
    let btnScrollToTop = document.querySelector("#btnTop");
    
    if (!btnScrollToTop) {
        // Create the button if it doesn't exist
        btnScrollToTop = document.createElement("button");
        btnScrollToTop.id = "btnTop";
        btnScrollToTop.innerHTML = "↑"; // Up arrow character
        btnScrollToTop.title = "Scroll to top";
        
        // Style the button
        btnScrollToTop.style.position = "fixed";
        btnScrollToTop.style.bottom = "20px";
        btnScrollToTop.style.right = "20px";
        btnScrollToTop.style.zIndex = "1000";
        btnScrollToTop.style.fontSize = "24px";
        btnScrollToTop.style.width = "40px";
        btnScrollToTop.style.height = "40px";
        btnScrollToTop.style.borderRadius = "50%";
        btnScrollToTop.style.backgroundColor = "#3498db";
        btnScrollToTop.style.color = "white";
        btnScrollToTop.style.border = "none";
        btnScrollToTop.style.cursor = "pointer";
        btnScrollToTop.style.display = "none"; // Initially hidden
        btnScrollToTop.style.boxShadow = "0 2px 5px rgba(0,0,0,0.3)";
        
        // Add hover effect
        btnScrollToTop.addEventListener("mouseover", function() {
            this.style.backgroundColor = "#2980b9";
        });
        
        btnScrollToTop.addEventListener("mouseout", function() {
            this.style.backgroundColor = "#3498db";
        });
        
        // Add to the document
        document.body.appendChild(btnScrollToTop);
    }
    
    // Add click event listener with multiple scroll methods for compatibility
    btnScrollToTop.addEventListener("click", function(e) {
        e.preventDefault(); // Prevent any default behavior
        console.log("Scroll button clicked"); // Debug log
        
        // Try multiple ways to ensure scrolling works across browsers
        
        // Method 1: Modern smooth scrolling
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        
        // Method 2: Fallback for older browsers
        document.body.scrollTop = 0; // For Safari
        document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
        
        // Method 3: Animation fallback using requestAnimationFrame
        function scrollToTop(duration) {
            const start = window.pageYOffset;
            const startTime = 'now' in window.performance ? performance.now() : new Date().getTime();
            
            function scroll() {
                const now = 'now' in window.performance ? performance.now() : new Date().getTime();
                const time = Math.min(1, (now - startTime) / duration);
                
                window.scrollTo(0, Math.ceil((1 - time) * start));
                
                if (time < 1) {
                    requestAnimationFrame(scroll);
                }
            }
            
            requestAnimationFrame(scroll);
        }
        
        // If the smooth scroll doesn't work, try the animation
        setTimeout(function() {
            if (window.pageYOffset > 0) {
                scrollToTop(500); // 500ms duration
            }
        }, 200); // Wait a bit to see if the first method worked
        
        return false; // Prevent event bubbling
    });
    
    // Add scroll event listener
    window.addEventListener("scroll", function() {
        if (window.pageYOffset > 300) { // Show button after scrolling 300px
            btnScrollToTop.style.display = "block";
        } else {
            btnScrollToTop.style.display = "none";
        }
    });
    
    // Force a check on page load (some browsers might load with scroll position)
    if (window.pageYOffset > 300) {
        btnScrollToTop.style.display = "block";
    }
    
    // Debug log to confirm initialization
    console.log("Scroll button initialized");
});