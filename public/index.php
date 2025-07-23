<?php
include "includes/auth_check.php";

// Remove the forced login redirect - allow guests to browse
// Only redirect admins who shouldn't access regular user pages
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    // Admin trying to access user page - redirect to admin panel
    $admin_url = getAbsoluteUrl('admin/index.php');
    header("Location: $admin_url");
    exit();
}
?>
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
    <style>
        /* Review Success Modal */
        #review-success-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .review-modal-content {
            background-color: white;
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 500px;
            text-align: center;
            position: relative;
            border: 3px dashed #ff69b4;
            animation: popIn 0.5s ease-out forwards;
        }

        @keyframes popIn {
            0% {
                opacity: 0;
                transform: scale(0.9) translateY(-10px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .review-success-icon {
            color: #ff69b4;
            font-size: 80px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
            display: inline-block;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .review-modal-content h2 {
            color: #ff1493;
            margin-bottom: 15px;
            font-size: 2rem;
        }

        .review-modal-content p {
            margin-bottom: 25px;
            color: #8a2be2;
            font-size: 1.1rem;
            line-height: 1.5;
        }

        .review-modal-btn {
            background-color: #ff69b4;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .review-modal-btn:hover {
            background-color: #ff1493;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255,105,180,0.3);
        }

        .hidden {
            display: none;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .review-modal-content {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .review-success-icon {
                font-size: 60px;
            }
            
            .review-modal-content h2 {
                font-size: 1.5rem;
            }
        }
    </style>
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
                    <i class="fas fa-candy-cane"></i>
                </div>
                <h3>Candies & Gummies</h3>
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
                    <i class="fas fa-fire-burner"></i>
                </div>
                <h3>Cooking</h3>
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
        <?php
        // Get top 5 products with highest average rating
        $featured_query = "SELECT * FROM products 
                          WHERE stock > 0 
                          ORDER BY average_rating DESC, created_at DESC 
                          LIMIT 5";
        $featured_result = $conn->query($featured_query);
        
        if ($featured_result && $featured_result->num_rows > 0) {
            while ($product = $featured_result->fetch_assoc()) {
                // Format the price with 2 decimal places
                $price = number_format($product['price'], 2);
        ?>
        <div class="product-card">
            <div class="product-image">
                <img src="admin/uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php if ($product['average_rating'] > 0) { ?>
                <div class="product-rating">
                    <i class="fas fa-star"></i> <?php echo number_format($product['average_rating'], 1); ?>
                </div>
                <?php } ?>
            </div>
            <div class="product-details">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="product-price">$<?php echo $price; ?></p>
                <button class="view-product-btn" data-product-id="<?php echo $product['id']; ?>">View Details</button>
            </div>
        </div>
        <?php
            }
        } else {
            // Fallback if no products are found
            echo '<div class="no-products"><p>No featured products available at the moment.</p></div>';
        }
        ?>
    </div>

    <div class="view-all-container">
        <button class="view-all-items-btn" id="viewAllItemsBtn">
            View All Items
            <i class="fas fa-shopping-bag"></i>
        </button>
    </div>
</div>

    <!-- Customer Reviews Section -->
<section id="review-section" class="customer-reviews-section" >
    <h2>Sweet Feedback</h2>
    <div class="reviews-wrapper">
        <div class="candy-icon review-candy-1"><i class="fas fa-star"></i></div>
        <div class="candy-icon review-candy-2"><i class="fas fa-heart"></i></div>
        <div class="candy-icon review-candy-3"><i class="fas fa-thumbs-up"></i></div>
        
        <div class="reviews-container">
            <?php
            // Get the last 12 approved reviews and sort them from newest to oldest.
            $reviews = $conn->query("SELECT r.*, u.first_name 
                                    FROM reviews r
                                    JOIN users u ON r.user_id = u.id
                                    WHERE r.approval_status = 'approved' 
                                    ORDER BY r.submitted_at DESC 
                                    LIMIT 12");

            if ($reviews && $reviews->num_rows > 0) {
                while ($review = $reviews->fetch_assoc()) {
            ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="reviewer-name"><?php echo htmlspecialchars($review['first_name']); ?></div>
                    </div>
                    <div class="review-subject"><?php echo htmlspecialchars($review['subject']); ?></div>
                    <div class="review-date"><?php echo date('M d, Y', strtotime($review['submitted_at'])); ?></div>
                    <div class="review-message"><?php echo nl2br(htmlspecialchars($review['message'])); ?></div>
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
   
    <?php
    // Add this at the end of your customer reviews section but before the closing </section> tag
    // Add an id to this section for the anchor link
    echo '<a id="review-section"></a>';

    // Check if user is logged in
    $isLoggedIn = isset($_SESSION['user_id']);
    $userId = $isLoggedIn ? $_SESSION['user_id'] : null;
    $hasPurchased = false;

    // Check if user has purchased any products (only if logged in)
    if ($isLoggedIn) {
        $checkPurchase = $conn->prepare("SELECT COUNT(*) as count FROM orders 
                                        WHERE user_id = ? AND status = 'completed'");
        $checkPurchase->bind_param("i", $userId);
        $checkPurchase->execute();
        $result = $checkPurchase->get_result();
        $orderCount = $result->fetch_assoc()['count'];
        $hasPurchased = ($orderCount > 0);
    }
    ?>

    <!-- Write a Review Button Section -->
    <div class="write-review-section">
        <?php if ($isLoggedIn && $hasPurchased): ?>
            <!-- User is logged in and has made a purchase -->
            <button id="writeReviewBtn" class="candy-button write-review-btn">
                <i class="fas fa-pencil-alt"></i> Share Your Sweet Experience!
            </button>
        <?php elseif ($isLoggedIn): ?>
            <!-- User is logged in but hasn't made a purchase -->
            <div class="review-prompt">
                <p>Bought our sweets elsewhere? <a href="shop.php" class="candy-link">Try our treats</a> to share your thoughts!</p>
            </div>
        <?php else: ?>
            <!-- User is not logged in -->
            <div class="review-prompt">
                <p>Love our candy? <a href="login.php?redirect=index.php#review-section" class="candy-link">Log in</a>to share your sweet thoughts!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Review Form Modal (hidden by default) -->
    <div id="reviewModal" class="review-modal">
        <div class="review-modal-content">
            <span class="close-modal">&times;</span>
            <h3>Share Your Sweet Experience!</h3>
            
            <form id="reviewForm" action="submit_review.php" method="post">
                <div class="form-group">
                    <label for="reviewSubject">Subject</label>
                    <input type="text" id="reviewSubject" name="subject" required maxlength="50" placeholder="What's the main highlight?">
                </div>
                
                <div class="form-group">
                    <label for="reviewMessage">Your Review</label>
                    <textarea id="reviewMessage" name="message" required rows="5" maxlength="500" placeholder="Tell us about your candy experience!"></textarea>
                </div>
                
                <button type="submit" class="candy-button submit-review-btn">
                    <i class="fas fa-paper-plane"></i> Submit Review
                </button>
            </form>
        </div>
    </div>
    
    <!-- Review Success Modal -->
    <div id="review-success-modal">
        <div class="review-modal-content">
            <div class="review-success-icon">⭐</div>
            <h2>Amazing! Review Submitted!</h2>
            <p id="review-success-message">Thank you for sharing your thoughts with us!</p>
            <button onclick="closeReviewModal()" class="review-modal-btn">Continue Browsing</button>
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
            &copy; 2025 
             Treats. All rights reserved.
        </div>
    </footer>

    <script>
        // Review success modal functions
        function showReviewSuccessModal(message) {
            const modal = document.getElementById('review-success-modal');
            if (message) {
                document.getElementById('review-success-message').textContent = message;
            }
            modal.style.display = 'flex';
        }

        function closeReviewModal() {
            document.getElementById('review-success-modal').style.display = 'none';
        }

        <?php if (isset($_SESSION['review_success'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showReviewSuccessModal('<?php echo htmlspecialchars($_SESSION['review_success'], ENT_QUOTES); ?>');
            });
            <?php unset($_SESSION['review_success']); ?>
        <?php endif; ?>
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const writeReviewBtn = document.getElementById('writeReviewBtn');
            const reviewModal = document.getElementById('reviewModal');
            const closeModal = document.querySelector('.close-modal');
            
            // Only setup event handlers if the button exists (user is logged in and has purchased)
            if (writeReviewBtn) {
                writeReviewBtn.addEventListener('click', function() {
                    reviewModal.style.display = 'block';
                });
            }
            
            // Close modal when clicking the X
            if (closeModal) {
                closeModal.addEventListener('click', function() {
                    reviewModal.style.display = 'none';
                });
            }
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target === reviewModal) {
                    reviewModal.style.display = 'none';
                }
            });
        });
    </script>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Document ready function
        document.addEventListener('DOMContentLoaded', function() {
            // Shop Now button click
            const shopNowBtn = document.getElementById('shopNowBtn');
            if (shopNowBtn) {
                shopNowBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Scroll to categories section
                    const categoriesSection = document.getElementById('categoriesSection');
                    if (categoriesSection) {
                        categoriesSection.scrollIntoView({
                            behavior: 'smooth'
                        });
                    } else {
                        console.error('Element with ID "categoriesSection" not found');
                    }
                });
            }
            
            // Add event listeners for product detail buttons
            document.querySelectorAll('.view-product-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const productId = this.getAttribute('data-product-id');
                    console.log('Redirecting to product ID:', productId);
                    window.location.href = 'shop.php?product_id=' + productId + '#product-' + productId;
                });
            });
            
            // View All Items button click
            const viewAllItemsBtn = document.getElementById('viewAllItemsBtn');
            if (viewAllItemsBtn) {
                viewAllItemsBtn.addEventListener('click', function() {
                    window.location.href = 'shop.php';
                });
            }
            
            // Make category items clickable
            const categoryItems = document.querySelectorAll('.category');
            categoryItems.forEach(category => {
                category.addEventListener('click', function() {
                    // Get the category name
                    const categoryName = this.querySelector('h3').textContent.trim();
                    
                    // Map the category name to the corresponding ID in the database
                    let categoryId;
                    switch(categoryName) {
                        case 'Candies & Gummies':
                            categoryId = 8; 
                            break;
                        case 'Ice Cream':
                            categoryId = 6; 
                            break;
                        case 'Drinks':
                            categoryId = 7; 
                            break;
                        case 'Cooking':
                            categoryId = 13; 
                            break;
                        case 'Korean':
                            categoryId = 2; 
                            break;
                        case 'Spices':
                            categoryId = 3; 
                            break;
                        default:
                            categoryId = 0;
                    }
                    
                    window.location.href = `shop.php?category=${categoryId}`;
                });
                
                // Add cursor pointer to show it's clickable
                category.style.cursor = 'pointer';
            });
            
            // Set up review carousel
            setupReviewCarousel();
        });
        
        function setupReviewCarousel() {
            const reviewsContainer = document.querySelector('.reviews-container');
            const prevReviewBtn = document.getElementById('prevReview');
            const nextReviewBtn = document.getElementById('nextReview');
            const reviewDotsContainer = document.getElementById('reviewDots');
            
            if (!reviewsContainer || !prevReviewBtn || !nextReviewBtn || !reviewDotsContainer) return;
            
            const reviewCards = reviewsContainer.querySelectorAll('.review-card');
            if (reviewCards.length === 0) return;
            
            // Numbers of cards visible at each screen size
            let visibleCards = 3; // large
            if (window.innerWidth <= 992) visibleCards = 2; // medium
            if (window.innerWidth <= 768) visibleCards = 1; // small
            
            // Calculate total number of pages
            let totalPages = Math.ceil(reviewCards.length / visibleCards);
            let currentPage = 0;
            
            // Create initial dots
            createDots();
            
            // Set up click handlers for navigation buttons
            prevReviewBtn.addEventListener('click', () => {
                goToPage(currentPage - 1);
            });
            
            nextReviewBtn.addEventListener('click', () => {
                goToPage(currentPage + 1);
            });
            
            // Function to create dots
            function createDots() {
                reviewDotsContainer.innerHTML = ''; // Clear existing dots
                
                for (let i = 0; i < totalPages; i++) {
                    const dot = document.createElement('div');
                    dot.classList.add('review-dot');
                    if (i === currentPage) dot.classList.add('active');
                    dot.addEventListener('click', () => {
                        goToPage(i);
                    });
                    reviewDotsContainer.appendChild(dot);
                }
            }
            
            // Function to navigate to specific page
            function goToPage(pageIndex) {
                if (pageIndex < 0) pageIndex = totalPages - 1;
                if (pageIndex >= totalPages) pageIndex = 0;
                
                currentPage = pageIndex;
                
                // Calculate card width dynamically
                const cardWidth = reviewCards[0].offsetWidth;
                const gap = 25; // 25px gap between cards
                
                // Calculate scroll position with correct offsets
                const scrollPosition = pageIndex * (cardWidth + gap) * visibleCards;
                
                // Apply scroll
                reviewsContainer.scrollTo({
                    left: scrollPosition,
                    behavior: 'smooth'
                });
                
                // Update active dot
                document.querySelectorAll('.review-dot').forEach((dot, index) => {
                    dot.classList.toggle('active', index === pageIndex);
                });
            }
            
            // Handle window RESIZE event to adjust visible cards
            window.addEventListener('resize', () => {
                // Store the current page before resize
                const oldCurrentPage = currentPage;
                
                // Recalculate visible cards
                let newVisibleCards = 3;
                if (window.innerWidth <= 992) newVisibleCards = 2;
                if (window.innerWidth <= 768) newVisibleCards = 1;
                
                // Only update if the number of visible cards has changed
                if (newVisibleCards !== visibleCards) {
                    visibleCards = newVisibleCards;
                    const newTotalPages = Math.ceil(reviewCards.length / visibleCards);
                    
                    // Adjust current page if it would be out of bounds with new page count
                    if (currentPage >= newTotalPages) {
                        currentPage = newTotalPages - 1;
                    }
                    
                    // Update total pages and recreate dots
                    totalPages = newTotalPages;
                    createDots();
                    
                    // Immediately scroll to the correct position after resize
                    const cardWidth = reviewCards[0].offsetWidth;
                    const gap = 25;
                    reviewsContainer.scrollTo({
                        left: currentPage * (cardWidth + gap) * visibleCards,
                        behavior: 'auto'
                    });
                    
                    // If the number of visible cards changed, trigger a redraw
                    setTimeout(() => {
                        goToPage(currentPage);
                    }, 50);
                }
            });
            
            // Add animation to review cards
            reviewCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate-in');
            });
            
            // Initial page setup
            goToPage(0);
        }
    </script>
</body>
</html>