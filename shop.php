<?php
// The session_start is in the header.php file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Sweet Treats</title>
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="css/shop.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Shop Hero Section -->
    <div class="shop-hero">
        <div class="candy-icon candy-1"><i class="fas fa-candy-cane"></i></div>
        <div class="candy-icon candy-2"><i class="fas fa-cookie"></i></div>
        <div class="candy-icon candy-3"><i class="fas fa-ice-cream"></i></div>
        <div class="candy-icon candy-4"><i class="fas fa-birthday-cake"></i></div>
        
        <div class="container">
            <h1>Discover Our Sweet Collection</h1>
            <p>Browse our unique selection of treats and products from around the world</p>
        </div>
    </div>
    
    <!-- Shop Content -->
    <div class="container">
        <!-- Top Search and Filter Bar -->
        <div class="top-filters-bar">
            <div class="search-row">
                <div class="search-box">
                    <input type="text" placeholder="Search for yummy treats...">
                    <button class="search-button">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <span class="filter-label">Category:</span>
                    <select class="filter-select">
                        <option value="">All Categories</option>
                        <option value="candy">Candy</option>
                        <option value="ice-cream">Ice Cream</option>
                        <option value="drinks">Drinks</option>
                        <option value="cooking">Cooking</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="korean">Korean</option>
                        <option value="spices">Spices</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <span class="filter-label">Sort by:</span>
                    <select class="filter-select">
                        <option value="featured">Featured</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="rating">Highest Rated</option>
                        <option value="newest">Newest</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <span class="filter-label">Price:</span>
                    <select class="filter-select">
                        <option value="">All Prices</option>
                        <option value="under-5">Under $5</option>
                        <option value="5-10">$5 - $10</option>
                        <option value="10-20">$10 - $20</option>
                        <option value="over-20">$20+</option>
                    </select>
                </div>
    </div>          

<!-- Products Grid Section -->
<div class="products-grid">
    <?php
    // Sample products array - in a real application, this would come from a database
    $products = [
        [
            'id' => 1,
            'name' => 'Sparkling Chupa Chups Strawberry & Cream',
            'price' => 3.99,
            'image' => 'images/products/Sparkling-ChupaChups-Strawberry&Cream.jpg',
            'category' => 'drinks',
            'rating' => 4.5,
            'reviews' => 28,
            'badge' => 'New'
        ],
        [
            'id' => 2,
            'name' => 'mac & cheese (Three Cheese)',
            'price' => 6.99,
            'image' => 'images/products/mac&cheese(three cheese).jpg',
            'category' => 'cooking',
            'rating' => 4.8,
            'reviews' => 42,
            'badge' => 'Best Seller'
        ],
        [
            'id' => 3,
            'name' => 'Twix Edible Cookie Dough',
            'price' => 2.49,
            'image' => 'images/products/Cookie-Dough-Edible-Twix.jpg',
            'category' => 'candy',
            'rating' => 4.0,
            'reviews' => 15,
            'badge' => ''
        ],
        [
            'id' => 4,
            'name' => 'Korean Ramen Bundle',
            'price' => 8.99,
            'image' => 'images/products/korean-ramen-bundle(5).jpg',
            'category' => 'korean',
            'rating' => 4.7,
            'reviews' => 36,
            'badge' => 'Popular'
        ],
        [
            'id' => 5,
            'name' => 'Cracotte Chocolate',
            'price' => 12.99,
            'image' => 'images/products/Cracotte-Chocolate.jpg',
            'category' => 'candy',
            'rating' => 4.9,
            'reviews' => 53,
            'badge' => 'Hot'
        ],
        [
            'id' => 6,
            'name' => 'Great Value Ice Cream Sandwich (Vanilla)',
            'price' => 5.49,
            'image' => 'images/products/greatvalue-vanilla-icecream.jpg',
            'category' => 'ice-cream',
            'rating' => 4.3,
            'reviews' => 19,
            'badge' => ''
        ],
        [
            'id' => 7,
            'name' => 'Boba Milk Tea Kit (Matcha)',
            'price' => 15.99,
            'image' => 'images/products/Milk_Tea_Boba_Kit_Matcha.jpg',
            'category' => 'drinks',
            'rating' => 4.6,
            'reviews' => 31,
            'badge' => 'Limited'
        ],
        [
            'id' => 8,
            'name' => 'Spicy Curry Mix',
            'price' => 7.49,
            'image' => 'images/products/spicy_curry_mix.jpg',
            'category' => 'spices',
            'rating' => 4.2,
            'reviews' => 22,
            'badge' => ''
        ]
    ];

    // Display each product
    foreach ($products as $product) :
    ?>
        <div class="product-card" data-category="<?php echo $product['category']; ?>">
            <?php if (!empty($product['badge'])) : ?>
                <div class="product-badge"><?php echo $product['badge']; ?></div>
            <?php endif; ?>
            
            <div class="product-image">
                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
            </div>
            
            <div class="product-details">
                <h3><?php echo $product['name']; ?></h3>
                
                <div class="star-rating">
                    <?php
                    // Display star rating
                    $rating = $product['rating'];
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= floor($rating)) {
                            echo '<i class="fas fa-star"></i>';
                        } elseif ($i - 0.5 <= $rating) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        } else {
                            echo '<i class="far fa-star"></i>';
                        }
                    }
                    ?>
                    <span class="rating-number">(<?php echo $product['reviews']; ?>)</span>
                </div>
                
                <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                
                <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
            </div>
           
        </div>
    <?php endforeach; ?>
</div>

<!-- Pagination Section -->
<div class="pagination-container">
    <ul class="pagination">
        <li><a href="#" class="active">1</a></li>
        <li><a href="#">2</a></li>
        <li><a href="#">3</a></li>
        <li><a href="#"><i class="fas fa-chevron-right"></i></a></li>
    </ul>
</div>
<button id="btnTop">
    <i class="material-icons">arrow_upward</i>
</button>

<!-- footer -->
<?php include 'includes/footer.php'; ?>

<!-- Add JavaScript for interactive elements -->
<script src="js/shop.js"></script>
</body> 
</html>                           

