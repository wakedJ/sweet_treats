<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Treats</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/style.css">
    <?php include "includes/db.php"?>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="page-wrapper" id="homeWrapper">
        
         <div class="home-hero">
        <div class="candy-icon candy-1"><i class="fas fa-candy-cane"></i></div>
        <div class="candy-icon candy-2"><i class="fas fa-cookie"></i></div>
        <div class="candy-icon candy-3"><i class="fas fa-ice-cream"></i></div>
        <div class="candy-icon candy-4"><i class="fas fa-birthday-cake"></i></div>
        
        <div class="container">
            <h1>Craving Something Special?</h1>
            <p>We've got unique items that you won't find anywhere else! From imported candies to specialty treats.</p>
            <a href="#categoriesSection" class="shop-now-btn" id="shopNowBtn">Shop Now</a>
        </div>
    </div>
        <div class="melting-bg" id="meltingBg"></div>
    </div>
    
    <!-- Categories Section -->
    <section id="categoriesSection" class="categories-section">
        <h2>Shop by Category</h2>
        
        <div class="shop-categories">
            <div class="category">
                <div class="category-icon">
                    <i class="fas fa-broom"></i>
                </div>
                <h3>Cleaning</h3>
            </div>
            <div class="category">
                <div class="category-icon">
                    <i class="fas fa-ice-cream"></i>
                </div>
                <h3>Ice Cream</h3>
            </div>
            <div class="category">
                <div class="category-icon">
                    <i class="fas fa-coffee"></i>
                </div>
                <h3>Drinks</h3>
            </div>
            <div class="category">
                <div class="category-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3>Cooking</h3>
            </div>
            <div class="category">
                <div class="category-icon">
                    <i class="fas fa-candy-cane"></i>
                </div>
                <h3>Candy</h3>
            </div>
            <div class="category">
                <div class="category-icon">
                    <i class="fas fa-globe-asia"></i>
                </div>
                <h3>Korean</h3>
            </div>
            <div class="category">
                <div class="category-icon">
                    <i class="fas fa-pepper-hot"></i>
                </div>
                <h3>Spices</h3>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <div class="featured-products-section">
        <h2>Featured Items</h2>
        
        <div class="featured-products">
            <div class="product-card">
                <div class="product-image">
                    <img src="images/products/Cookie-Dough-Edible-Snickers.jpg" alt="Snickers Edible Cookie Dough">
                </div>
                <div class="product-details">
                    <h3>Snickers Edible Cookie Dough</h3>
                    <p class="product-price">$15</p>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
           
            <div class="product-card">
                <div class="product-image">
                    <img src="images/products/Sparkling-ChupaChups-Strawberry&Cream.jpg" alt="Chuppa Chups Starwberry & Cream">
                </div>
                <div class="product-details">
                    <h3>Chuppa Chups Starwberry & Cream</h3>
                    
                    <p class="product-price">$3</p>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">
                    <img src="images/products/mac&cheese-3cheese.jpg" alt="Mac & Cheese">
                </div>
                <div class="product-details">
                    <h3>Mac & Cheese (Original Flavor)</h3>
                    <p class="product-price">$7</p>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">
                    <img src="images/products/biscoff_spread.jpg" alt="Biscoff Spread">
                </div>
                <div class="product-details">
                    <h3>Lotus Biscoff Cookie Spread</h3>
                    <p class="product-price">$8</p>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">
                    <img src="images/products/biscoff_spread.jpg" alt="Biscoff Spread">
                </div>
                <div class="product-details">
                    <h3>Lotus Biscoff Cookie Spread</h3>
                    <p class="product-price">$8</p>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
        </div>

        <div class="view-all-container">
            <button class="view-all-items-btn" id="viewAllItemsBtn">
                View All Items
                <i class="fas fa-shopping-bag"></i>
            </button>
        </div>
    </div>

 <!-- Customer Reviews Section -->
<section class="customer-reviews-section">
    <h2>Sweet Feedback</h2>
    <div class="reviews-wrapper">
        <div class="candy-icon review-candy-1"><i class="fas fa-star"></i></div>
        <div class="candy-icon review-candy-2"><i class="fas fa-heart"></i></div>
        <div class="candy-icon review-candy-3"><i class="fas fa-thumbs-up"></i></div>
        
        <div class="reviews-container">
            <!-- Reviews will be dynamically loaded from the database -->
            <?php
            // Fetch reviews from database
            $reviews = $conn->query("SELECT * FROM reviews ORDER BY created_at DESC LIMIT 6");

            if ($reviews && $reviews->num_rows > 0) {
                while ($review = $reviews->fetch_assoc()) {
            ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="reviewer-name"><?php echo htmlspecialchars($review['name']); ?></div>
                    </div>
                    <div class="review-subject"><?php echo htmlspecialchars($review['subject']); ?></div>
                    <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                    <div class="review-message"><?php echo nl2br(htmlspecialchars($review['message'])); ?></div>
                    <div class="review-email"><?php echo htmlspecialchars($review['email']); ?></div>
                </div>
            <?php
                }
            } else {
            ?>
                <div class="no-reviews">
                    <i class="fas fa-comment-slash"></i>
                    <p>No reviews yet! Our treats are so delicious, everyone's too busy enjoying them to write reviews!</p>
                </div>
            <?php } ?>
        </div>
        
        <!-- Review slider controls -->
        <div class="review-controls">
            <button class="review-nav-btn prev-review" id="prevReview">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="review-dots" id="reviewDots">
                <!-- Dots will be added by JavaScript based on the number of reviews -->
            </div>
            <button class="review-nav-btn next-review" id="nextReview">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Sweet Treats</h3>
                <p>Your one-stop shop for unique treats and imports from around the world.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="shop.php">Shop All</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Info</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Main Street, near Taha Station</li>
                    <li><i class="fas fa-phone"></i> 76 921 300</li>
                    <li><i class="fas fa-envelope"></i> info@sweettreats.com</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; 2025 Sweet Treats. All rights reserved.
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    // Smooth scroll to target
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Fixed header on scroll
        window.addEventListener('scroll', function() {
            const header = document.getElementById('mainHeader');
            const scrollPosition = window.scrollY;
            
            // Fix header when scrolled down
            if (scrollPosition > 50) {
                header.classList.add('fixed-header');
            } else {
                header.classList.remove('fixed-header');
            }
        });
        
        // Shop Now button click
        document.getElementById('shopNowBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Scroll to categories section
            document.getElementById('categoriesSection').scrollIntoView({
                behavior: 'smooth'
            });
        });
        
        // View All Items button click
        document.getElementById('viewAllItemsBtn').addEventListener('click', function() {
            // Redirect to shop page
            window.location.href = 'shop.php';
        });

        // Add to cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productName = this.parentElement.querySelector('h3').textContent;
                
                // Show a simple confirmation
                const originalText = this.textContent;
                this.textContent = "Added!";
                this.style.backgroundColor = "#ff69b4";
                this.style.color = "white";
                
                // Reset button after animation
                setTimeout(() => {
                    this.textContent = originalText;
                    this.style.backgroundColor = "";
                    this.style.color = "";
                }, 1500);
                
                // You could add actual cart functionality here
                console.log(`Added ${productName} to cart`);
            });
        });
        // Add this to your existing JavaScript at the bottom of the homepage
document.addEventListener('DOMContentLoaded', function() {
    // Make category items clickable
    const categoryItems = document.querySelectorAll('.category');
    
    categoryItems.forEach(category => {
        category.addEventListener('click', function() {
            // Get the category name
            const categoryName = this.querySelector('h3').textContent.toLowerCase();
            
            // Map the category name to the value expected in shop.php
            let categoryValue;
            switch(categoryName) {
                case 'cleaning':
                    categoryValue = 'cleaning';
                    break;
                case 'ice cream':
                    categoryValue = 'ice-cream';
                    break;
                case 'drinks':
                    categoryValue = 'drinks';
                    break;
                case 'cooking':
                    categoryValue = 'cooking';
                    break;
                case 'candy':
                    categoryValue = 'candy';
                    break;
                case 'korean':
                    categoryValue = 'korean';
                    break;
                case 'spices':
                    categoryValue = 'spices';
                    break;
                default:
                    categoryValue = '';
            }
            
            // Redirect to shop page with category parameter
            window.location.href = `shop.php?category=${categoryValue}`;
        });
        
        // Add cursor pointer to show it's clickable
        category.style.cursor = 'pointer';
    });
});
document.addEventListener('DOMContentLoaded', function() {
    // Set up review carousel
    const reviewsContainer = document.querySelector('.reviews-container');
    const prevReviewBtn = document.getElementById('prevReview');
    const nextReviewBtn = document.getElementById('nextReview');
    const reviewDotsContainer = document.getElementById('reviewDots');
    
    if (!reviewsContainer || !prevReviewBtn || !nextReviewBtn || !reviewDotsContainer) return;
    
    const reviewCards = reviewsContainer.querySelectorAll('.review-card');
    if (reviewCards.length === 0) return;
    
    // Calculate how many cards are visible at once based on screen size
    let visibleCards = 3;
    if (window.innerWidth <= 992) visibleCards = 2;
    if (window.innerWidth <= 768) visibleCards = 1;
    
    // Calculate total number of pages
    const totalPages = Math.ceil(reviewCards.length / visibleCards);
    let currentPage = 0;
    
    // Create dots
    for (let i = 0; i < totalPages; i++) {
        const dot = document.createElement('div');
        dot.classList.add('review-dot');
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', () => {
            goToPage(i);
        });
        reviewDotsContainer.appendChild(dot);
    }
    
    // Set up click handlers for navigation buttons
    prevReviewBtn.addEventListener('click', () => {
        goToPage(currentPage - 1);
    });
    
    nextReviewBtn.addEventListener('click', () => {
        goToPage(currentPage + 1);
    });
    
    // Function to navigate to specific page
    function goToPage(pageIndex) {
        if (pageIndex < 0) pageIndex = totalPages - 1;
        if (pageIndex >= totalPages) pageIndex = 0;
        
        currentPage = pageIndex;
        
        // Calculate scroll position
        const cardWidth = reviewCards[0].offsetWidth + 25; // 25px is the gap
        reviewsContainer.scrollTo({
            left: pageIndex * cardWidth * visibleCards,
            behavior: 'smooth'
        });
        
        // Update active dot
        document.querySelectorAll('.review-dot').forEach((dot, index) => {
            dot.classList.toggle('active', index === pageIndex);
        });
    }
    
    // Handle window resize event to adjust visible cards
    window.addEventListener('resize', () => {
        let newVisibleCards = 3;
        if (window.innerWidth <= 992) newVisibleCards = 2;
        if (window.innerWidth <= 768) newVisibleCards = 1;
        
        if (newVisibleCards !== visibleCards) {
            visibleCards = newVisibleCards;
            const newTotalPages = Math.ceil(reviewCards.length / visibleCards);
            
            // Recreate dots if needed
            if (newTotalPages !== totalPages) {
                // Clear dots
                reviewDotsContainer.innerHTML = '';
                
                // Create new dots
                for (let i = 0; i < newTotalPages; i++) {
                    const dot = document.createElement('div');
                    dot.classList.add('review-dot');
                    if (i === 0) dot.classList.add('active');
                    dot.addEventListener('click', () => {
                        goToPage(i);
                    });
                    reviewDotsContainer.appendChild(dot);
                }
                
                currentPage = 0;
                goToPage(0);
            }
        }
    });
    
    // Add animation to review cards
    reviewCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate-in');
    });
});
    </script>
</body>
</html>