<?php
// Start session
session_start();

// Include database connection and cart functions
require_once 'includes/db.php';
require_once 'includes/cart_functions.php';

// Initialize variables
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$is_logged_in = isset($_SESSION['user_id']);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 30;

// Get product_id from URL if present
$highlight_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Cache categories in session to avoid repeated queries
if (!isset($_SESSION['categories_cache']) || (time() - ($_SESSION['categories_cache_time'] ?? 0)) > 300) {
    $category_query = "SELECT id, name FROM categories ORDER BY name";
    $category_result = mysqli_query($conn, $category_query);
    $_SESSION['categories_cache'] = mysqli_fetch_all($category_result, MYSQLI_ASSOC);
    $_SESSION['categories_cache_time'] = time();
}
$categories = $_SESSION['categories_cache'];

// Initialize and validate filter variables
$category_filter = isset($_GET['category']) ? max(0, intval($_GET['category'])) : 0;
$sort_by = isset($_GET['sort']) && in_array($_GET['sort'], ['newest', 'price-low', 'price-high', 'rating', 'featured']) 
    ? $_GET['sort'] : 'newest';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$price_range = isset($_GET['price']) && in_array($_GET['price'], ['under-5', '5-10', '10-20', 'over-20']) 
    ? $_GET['price'] : '';

// Handle AJAX add to cart requests early
if (isset($_POST['add_to_cart']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    // Set proper content type for JSON response
    header('Content-Type: application/json');
    
    try {
        // Validate input
        if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }
        
        // Check if database connection exists
        if (!$conn || mysqli_connect_errno()) {
            echo json_encode(['success' => false, 'message' => 'Database connection error']);
            exit;
        }
        
        // Use prepared statement for stock check
        $stock_stmt = $conn->prepare("SELECT stock, name FROM products WHERE id = ? LIMIT 1");
        if (!$stock_stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        
        $stock_stmt->bind_param("i", $product_id);
        if (!$stock_stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Database execute error: ' . $stock_stmt->error]);
            exit;
        }
        
        $stock_result = $stock_stmt->get_result();
        $product = $stock_result->fetch_assoc();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found or inactive']);
            exit;
        }
        
        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available. Only ' . $product['stock'] . ' items left.']);
            exit;
        }
        
        if ($is_logged_in) {
            // Handle logged-in user cart
            $conn->begin_transaction();
            
            try {
                // Check if item already exists in cart
                $cart_stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? LIMIT 1");
                if (!$cart_stmt) {
                    throw new Exception("Prepare cart check failed: " . $conn->error);
                }
                
                $cart_stmt->bind_param("ii", $user_id, $product_id);
                if (!$cart_stmt->execute()) {
                    throw new Exception("Execute cart check failed: " . $cart_stmt->error);
                }
                
                $cart_result = $cart_stmt->get_result();
                $cart_item = $cart_result->fetch_assoc();
                
                if ($cart_item) {
                    // Update existing cart item
                    $new_quantity = $cart_item['quantity'] + $quantity;
                    if ($new_quantity > $product['stock']) {
                        $conn->rollback();
                        echo json_encode(['success' => false, 'message' => 'Cannot add more of this item. Total would exceed stock limit.']);
                        exit;
                    }
                    
                    $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, added_date = NOW() WHERE cart_item_id = ?");
                    if (!$update_stmt) {
                        throw new Exception("Prepare update failed: " . $conn->error);
                    }
                    
                    $update_stmt->bind_param("ii", $new_quantity, $cart_item['cart_item_id']);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Execute update failed: " . $update_stmt->error);
                    }
                } else {
                    // Insert new cart item
                    $insert_stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity, added_date) VALUES (?, ?, ?, NOW())");
                    if (!$insert_stmt) {
                        throw new Exception("Prepare insert failed: " . $conn->error);
                    }
                    
                    $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                    if (!$insert_stmt->execute()) {
                        throw new Exception("Execute insert failed: " . $insert_stmt->error);
                    }
                }
                
                // Get updated cart count
                $count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
                if (!$count_stmt) {
                    throw new Exception("Prepare count failed: " . $conn->error);
                }
                
                $count_stmt->bind_param("i", $user_id);
                if (!$count_stmt->execute()) {
                    throw new Exception("Execute count failed: " . $count_stmt->error);
                }
                
                $count_result = $count_stmt->get_result();
                $cart_count = $count_result->fetch_assoc()['total'] ?? 0;
                
                $conn->commit();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Item added to cart successfully!', 
                    'cart_count' => $cart_count,
                    'product_name' => $product['name']
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                exit;
            }
        } else {
            // Handle session cart for non-logged-in users
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            $current_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
            $new_quantity = $current_quantity + $quantity;
            
            if ($new_quantity > $product['stock']) {
                echo json_encode(['success' => false, 'message' => 'Cannot add more of this item. Total would exceed stock limit.']);
                exit;
            }
            
            $_SESSION['cart'][$product_id] = [
                'quantity' => $new_quantity,
                'added_date' => date('Y-m-d H:i:s')
            ];
            
            $cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
            
            echo json_encode([
                'success' => true, 
                'message' => 'Item added to cart successfully!', 
                'cart_count' => $cart_count,
                'product_name' => $product['name']
            ]);
        }
        
    } catch (Exception $e) {
        if ($is_logged_in && isset($conn)) {
            $conn->rollback();
        }
        echo json_encode(['success' => false, 'message' => 'Unexpected error: ' . $e->getMessage()]);
    }
    exit;
}

// Prepare the base query - using your original working structure
$base_query = "SELECT p.* FROM products p ";

// Add WHERE clauses based on filters
$where_clauses = [];
$params = [];
$types = "";

if ($category_filter > 0) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if ($search_term !== '') {
    $where_clauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($price_range !== '') {
    switch ($price_range) {
        case 'under-5':
            $where_clauses[] = "p.price < ?";
            $params[] = 5;
            $types .= "d";
            break;
        case '5-10':
            $where_clauses[] = "p.price >= ? AND p.price <= ?";
            $params[] = 5;
            $params[] = 10;
            $types .= "dd";
            break;
        case '10-20':
            $where_clauses[] = "p.price > ? AND p.price <= ?";
            $params[] = 10;
            $params[] = 20;
            $types .= "dd";
            break;
        case 'over-20':
            $where_clauses[] = "p.price > ?";
            $params[] = 20;
            $types .= "d";
            break;
    }
}

// Combine WHERE clauses if any exist
$query = $base_query;
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}

// Add ORDER BY clause based on sort selection
switch ($sort_by) {
    case 'price-low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price-high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'rating':
        $query .= " ORDER BY p.average_rating DESC";
        break;
    case 'newest':
        $query .= " ORDER BY p.updated_at DESC";
        break;
    default: // featured or any other value
        $query .= " ORDER BY FIELD(p.tag, 'hot', 'best seller', 'popular', 'new', 'limited', 'on sale', 'none'), p.name";
        break;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM products p";
if (!empty($where_clauses)) {
    $count_query .= " WHERE " . implode(' AND ', $where_clauses);
}

// Prepare and execute count statement
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_products = $count_row['total'];
$total_pages = ceil($total_products / $items_per_page);

// Add pagination parameters
$offset = ($current_page - 1) * $items_per_page;
$query .= " LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

// Prepare and execute the main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Helper function for building filter URLs
function buildFilterUrl($params) {
    $current_params = $_GET;
    $current_params = array_merge($current_params, $params);
    return '?' . http_build_query(array_filter($current_params));
}
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
    <style>
        /* Loading states */
        .filter-loading {
            pointer-events: none;
            opacity: 0.6;
            position: relative;
        }
        
        .filter-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes highlight {
            0% { box-shadow: 0 0 0 0 rgba(255,215,0, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255,215,0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255,215,0, 0); }
        }

        .highlight-product {
            animation: highlight 2s ease-in-out;
            border: 2px solid gold;
        }
        
        /* Error message styling */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
        }
    </style>
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
            <form method="GET" action="shop.php" id="filter-form">
                <div class="search-row">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search for yummy treats..." 
                               value="<?php echo htmlspecialchars($search_term); ?>" id="search-input">
                        <button type="submit" class="search-button">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <span class="filter-label">Category:</span>
                        <select name="category" class="filter-select" id="category-filter">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <span class="filter-label">Sort by:</span>
                        <select name="sort" class="filter-select" id="sort-filter">
                            <option value="featured" <?php echo ($sort_by == 'featured') ? 'selected' : ''; ?>>Featured</option>
                            <option value="price-low" <?php echo ($sort_by == 'price-low') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price-high" <?php echo ($sort_by == 'price-high') ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo ($sort_by == 'rating') ? 'selected' : ''; ?>>Highest Rated</option>
                            <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest</option>
                        </select>
                    </div>
                    
                    <div class="filter-group price-filter-group">
                        <span class="filter-label">Price:</span>
                        <select name="price" class="filter-select" id="price-filter">
                            <option value="" <?php echo ($price_range == '') ? 'selected' : ''; ?>>All Prices</option>
                            <option value="under-5" <?php echo ($price_range == 'under-5') ? 'selected' : ''; ?>>Under $5</option>
                            <option value="5-10" <?php echo ($price_range == '5-10') ? 'selected' : ''; ?>>$5 - $10</option>
                            <option value="10-20" <?php echo ($price_range == '10-20') ? 'selected' : ''; ?>>$10 - $20</option>
                            <option value="over-20" <?php echo ($price_range == 'over-20') ? 'selected' : ''; ?>>$20+</option>
                        </select>
                        <div class="price-sort-arrows">
                            <a href="<?php echo buildFilterUrl(['sort' => 'price-low']); ?>" 
                               class="price-arrow <?php echo ($sort_by == 'price-low') ? 'active' : ''; ?>" 
                               title="Sort by Price: Low to High">
                                <i class="fas fa-arrow-up"></i>
                            </a>
                            <a href="<?php echo buildFilterUrl(['sort' => 'price-high']); ?>" 
                               class="price-arrow <?php echo ($sort_by == 'price-high') ? 'active' : ''; ?>" 
                               title="Sort by Price: High to Low">
                                <i class="fas fa-arrow-down"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Reset button -->
                    <a href="shop.php" class="reset-filters-btn">
                        <i class="fas fa-sync-alt"></i> Reset Filters
                    </a>
                </div>
                
                <?php if ($current_page > 1) : ?>
                    <input type="hidden" name="page" value="1">
                <?php endif; ?>
            </form>
        </div>

        <!-- Pagination Info Section -->
        <?php if ($total_products > 0): ?>
            <div class="pagination-info">
                Showing <?php echo min(($current_page - 1) * $items_per_page + 1, $total_products); ?> to 
                <?php echo min($current_page * $items_per_page, $total_products); ?> of 
                <?php echo $total_products; ?> products (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
            </div>
        <?php endif; ?>

        <!-- Products Grid Section -->
        <div class="products-grid" id="products-grid">
            <?php
            if ($result->num_rows > 0) {
                while ($product = $result->fetch_assoc()) :
                    $badge = ($product['tag'] != 'none') ? ucwords(str_replace('_', ' ', $product['tag'])) : '';
                    $image_path = file_exists("admin/uploads/products/" . $product['image']) ? 
                        "admin/uploads/products/" . $product['image'] : 
                        "admin/uploads/products/placeholder.jpg";
                    ?>
                    <div class="product-card" data-product-id="<?php echo $product['id']; ?>" id="product-<?php echo $product['id']; ?>">
                        <?php if (!empty($badge)) : ?>
                            <div class="product-badge"><?php echo $badge; ?></div>
                        <?php endif; ?>
                        
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy">
                        </div>

                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            
                            <div class="star-rating">
                                <?php
                                // Display star rating
                                $rating = floatval($product['average_rating'] ?? 0);
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
                                <span class="rating-number">(<?php echo isset($product['total_ratings']) ? $product['total_ratings'] : 0; ?>)</span>
                            </div>
                            
                            <div class="product-price">
                                <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) : ?>
                                    <span class="original-price">$<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="sale-price">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                <?php else : ?>
                                    $<?php echo number_format($product['price'], 2); ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($product['stock'] > 0) : ?>
                                <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                                <?php if ($product['stock'] < 10) : ?>
                                    <div class="stock-status">Only <?php echo $product['stock']; ?> left!</div>
                                <?php endif; ?>
                            <?php else : ?>
                                <button class="add-to-cart-btn" disabled>
                                    <i class="fas fa-shopping-cart"></i> Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                endwhile;
            } else {
                echo '<div class="no-products-found"><p>No products found matching your criteria.</p></div>';
            }
            ?>
        </div>

        <!-- Pagination Section -->
        <?php if ($total_pages > 1) : ?>
        <div class="pagination-container">
            <ul class="pagination">
                <?php if ($current_page > 1) : ?>
                    <li><a href="<?php echo buildFilterUrl(['page' => $current_page - 1]); ?>">
                        <i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                
                <?php
                $range = 3;
                $start_page = max(1, $current_page - $range);
                $end_page = min($total_pages, $current_page + $range);
                
                for ($i = $start_page; $i <= $end_page; $i++) :
                ?>
                    <li><a href="<?php echo buildFilterUrl(['page' => $i]); ?>" 
                           <?php echo ($i == $current_page) ? 'class="active"' : ''; ?>><?php echo $i; ?></a></li>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages) : ?>
                    <li><a href="<?php echo buildFilterUrl(['page' => $current_page + 1]); ?>">
                        <i class="fas fa-chevron-right"></i></a></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <button id="btnTop">
        <i class="material-icons">arrow_upward</i>
    </button>

    <!-- footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Handle product highlighting and scrolling
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const productId = urlParams.get('product_id');
            
            if (productId) {
                const productElement = document.getElementById('product-' + productId);
                if (productElement) {
                    // Highlight the product
                    productElement.classList.add('highlight-product');
                    
                    // Scroll to the product
                    setTimeout(() => {
                        productElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }, 100);
                    
                    // Remove highlight after a few seconds
                    setTimeout(() => {
                        productElement.classList.remove('highlight-product');
                    }, 3000);
                }
            }
        }); 
        
        document.addEventListener('DOMContentLoaded', function() {
            // Debounced search functionality
            let searchTimeout;
            const searchInput = document.getElementById('search-input');
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 3 || this.value.length === 0) {
                        document.getElementById('filter-form').submit();
                    }
                }, 500);
            });
            
            // Optimized filter change handlers
            const filterSelects = document.querySelectorAll('.filter-select');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    // Reset to page 1 when filters change
                    const pageInput = document.querySelector('input[name="page"]');
                    if (pageInput) pageInput.value = 1;
                    
                    // Add loading state
                    this.classList.add('filter-loading');
                    document.getElementById('filter-form').submit();
                });
            });
            
            // Enhanced AJAX cart functionality with better error handling
            const cartButtons = document.querySelectorAll('.add-to-cart-btn:not([disabled])');
            cartButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const productId = this.dataset.productId;
                    const originalText = this.innerHTML;
                    
                    // Validate product ID
                    if (!productId || isNaN(productId)) {
                        alert('Invalid product ID');
                        return;
                    }
                    
                    // Disable button and show loading
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                    
                    // AJAX request with improved error handling
                    fetch('shop.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `add_to_cart=1&product_id=${encodeURIComponent(productId)}&quantity=1`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.text();
                    })
                    .then(text => {
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (parseError) {
                            console.error('JSON Parse Error:', parseError);
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                        
                        if (data.success) {
                            // Update cart count in header if exists
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount && data.cart_count !== undefined) {
                                cartCount.textContent = data.cart_count;
                                cartCount.style.display = data.cart_count > 0 ? 'inline' : 'none';
                            }
                            
                            // Show success feedback
                            this.innerHTML = '<i class="fas fa-check"></i> Added!';
                            this.style.backgroundColor = '#28a745';
                            this.style.color = 'white';
                            
                            // Highlight product
                            const productCard = this.closest('.product-card');
                            productCard.classList.add('highlight-product');
                            
                            // Show success message if product name is available
                            if (data.product_name) {
                                console.log(`Successfully added ${data.product_name} to cart`);
                            }
                            
                            setTimeout(() => {
                                this.innerHTML = originalText;
                                this.style.backgroundColor = '';
                                this.style.color = '';
                                this.disabled = false;
                                productCard.classList.remove('highlight-product');
                            }, 2000);
                        } else {
                            // Show error message
                            const errorMsg = data.message || 'Error adding to cart';
                            alert(errorMsg);
                            console.error('Cart Error:', errorMsg);
                            this.innerHTML = originalText;
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        let errorMessage = 'Error adding to cart';
                        
                        if (error.message.includes('JSON')) {
                            errorMessage = 'Server response error. Please try again.';
                        } else if (error.message.includes('HTTP')) {
                            errorMessage = 'Network error. Please check your connection.';
                        }
                        
                        alert(errorMessage);
                        this.innerHTML = originalText;
                        this.disabled = false;
                    });
                });
            });
            
            // Intersection Observer for lazy loading
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
            
            // Back to top functionality
            const btnTop = document.getElementById('btnTop');
            if (btnTop) {
                window.addEventListener('scroll', () => {
                    btnTop.style.display = window.pageYOffset > 100 ? 'block' : 'none';
                });
                
                btnTop.addEventListener('click', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        });
    </script>
</body>
</html>