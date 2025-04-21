<?php
// Start session
session_start();

// Include database connection
require_once 'includes/db.php';

// Initialize variables
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$is_logged_in = isset($_SESSION['user_id']);
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 8; // Keep original pagination count

// Get categories for filter dropdown
$category_query = "SELECT * FROM categories WHERE status = 1 ORDER BY name";
$category_result = mysqli_query($conn, $category_query);

// Initialize filter variables
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Handle the price range filter - convert from the second file's min/max to the first file's ranges
$price_min = isset($_GET['price_min']) ? $_GET['price_min'] : '';
$price_max = isset($_GET['price_max']) ? $_GET['price_max'] : '';
$price_range = isset($_GET['price']) ? $_GET['price'] : '';

// If no price_range but has price_min/max, convert to appropriate range
if (empty($price_range) && (!empty($price_min) || !empty($price_max))) {
    if (!empty($price_min) && !empty($price_max)) {
        if ($price_min < 5 && $price_max <= 5) {
            $price_range = 'under-5';
        } elseif ($price_min >= 5 && $price_max <= 10) {
            $price_range = '5-10';
        } elseif ($price_min >= 10 && $price_max <= 20) {
            $price_range = '10-20';
        } elseif ($price_min >= 20) {
            $price_range = 'over-20';
        }
    } elseif (!empty($price_min)) {
        if ($price_min < 5) {
            $price_range = 'under-5';
        } elseif ($price_min >= 5 && $price_min < 10) {
            $price_range = '5-10';
        } elseif ($price_min >= 10 && $price_min < 20) {
            $price_range = '10-20';
        } else {
            $price_range = 'over-20';
        }
    } elseif (!empty($price_max)) {
        if ($price_max <= 5) {
            $price_range = 'under-5';
        } elseif ($price_max <= 10) {
            $price_range = '5-10';
        } elseif ($price_max <= 20) {
            $price_range = '10-20';
        } else {
            $price_range = 'over-20';
        }
    }
}

// Prepare the base query
$base_query = "SELECT p.*, COUNT(r.id) as review_count 
                FROM products p 
                LEFT JOIN reviews r ON p.id = r.product_id ";

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

// Group by to handle the COUNT aggregate
$query .= " GROUP BY p.id";

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

// Add to cart functionality - moved from template to PHP
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Validate quantity
    $stock_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stock_stmt->bind_param("i", $product_id);
    $stock_stmt->execute();
    $stock_result = $stock_stmt->get_result();
    $product = $stock_result->fetch_assoc();
    
    if ($quantity > $product['stock']) {
        $error_message = "Not enough stock available";
    } else {
        if ($is_logged_in) {
            // Check if product already in cart
            $cart_stmt = $conn->prepare("SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?");
            $cart_stmt->bind_param("ii", $user_id, $product_id);
            $cart_stmt->execute();
            $cart_result = $cart_stmt->get_result();
            
            if ($cart_result->num_rows > 0) {
                // Update quantity
                $cart_item = $cart_result->fetch_assoc();
                $new_quantity = $cart_item['quantity'] + $quantity;
                
                if ($new_quantity > $product['stock']) {
                    $error_message = "Cannot add more of this item (stock limit)";
                } else {
                    $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
                    $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
                    $update_stmt->execute();
                    $success_message = "Cart updated successfully!";
                }
            } else {
                // Insert new item
                $insert_stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                $insert_stmt->execute();
                $success_message = "Item added to cart!";
            }
        } else {
            // Use session cart
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            if (isset($_SESSION['cart'][$product_id])) {
                // Update quantity
                $new_quantity = $_SESSION['cart'][$product_id]['quantity'] + $quantity;
                
                if ($new_quantity > $product['stock']) {
                    $error_message = "Cannot add more of this item (stock limit)";
                } else {
                    $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
                    $success_message = "Cart updated successfully!";
                }
            } else {
                // Add new item
                $_SESSION['cart'][$product_id] = [
                    'quantity' => $quantity
                ];
                $success_message = "Item added to cart!";
            }
        }
        
        // Get updated cart count for display
        $cart_count = 0;
        if ($is_logged_in) {
            $count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
            $count_stmt->bind_param("i", $user_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $cart_count = $count_result->fetch_assoc()['total'] ?: 0;
        } else if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $cart_count += $item['quantity'];
            }
        }
        
        // If this is an AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $success_message, 'cart_count' => $cart_count]);
            exit;
        }
    }
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
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    
        <!-- Top Search and Filter Bar -->
        <div class="top-filters-bar">
            <form method="GET" action="shop.php" id="filter-form">
                <div class="search-row">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search for yummy treats..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="search-button">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <span class="filter-label">Category:</span>
                        <select name="category" class="filter-select" onchange="this.form.submit()">
                            <option value="0">All Categories</option>
                            <?php 
                            // Reset the pointer to the start of category result set
                            mysqli_data_seek($category_result, 0);
                            while ($category = mysqli_fetch_assoc($category_result)) : 
                            ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <span class="filter-label">Sort by:</span>
                        <select name="sort" class="filter-select" onchange="this.form.submit()">
                            <option value="featured" <?php echo ($sort_by == 'featured') ? 'selected' : ''; ?>>Featured</option>
                            <option value="price-low" <?php echo ($sort_by == 'price-low') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price-high" <?php echo ($sort_by == 'price-high') ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo ($sort_by == 'rating') ? 'selected' : ''; ?>>Highest Rated</option>
                            <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest</option>
                        </select>
                    </div>
                    
                    <div class="filter-group price-filter-group">
                        <span class="filter-label">Price:</span>
                        <select name="price" class="filter-select" onchange="this.form.submit()">
                            <option value="" <?php echo ($price_range == '') ? 'selected' : ''; ?>>All Prices</option>
                            <option value="under-5" <?php echo ($price_range == 'under-5') ? 'selected' : ''; ?>>Under $5</option>
                            <option value="5-10" <?php echo ($price_range == '5-10') ? 'selected' : ''; ?>>$5 - $10</option>
                            <option value="10-20" <?php echo ($price_range == '10-20') ? 'selected' : ''; ?>>$10 - $20</option>
                            <option value="over-20" <?php echo ($price_range == 'over-20') ? 'selected' : ''; ?>>$20+</option>
                        </select>
                        <div class="price-sort-arrows">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price-low'])); ?>" 
                               class="price-arrow <?php echo ($sort_by == 'price-low') ? 'active' : ''; ?>" 
                               title="Sort by Price: Low to High">
                                <i class="fas fa-arrow-up"></i>
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price-high'])); ?>" 
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
                    <input type="hidden" name="page" value="<?php echo $current_page; ?>">
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
        <div class="products-grid">
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($product = mysqli_fetch_assoc($result)) :
                    // Convert tag value to a display name if needed
                    $badge = '';
                    if ($product['tag'] != 'none') {
                        $badge = ucwords(str_replace('_', ' ', $product['tag']));
                    }
                    
                    // Determine image path - check if file exists or use default path
                    if (!empty($product['image'])) {
                        $image_path = "images/products/" . $product['image'];
                        // Check if the file exists, if not use a placeholder
                        if (!file_exists($image_path)) {
                            $image_path = "images/products/placeholder.jpg";
                        }
                    } else {
                        $image_path = "images/products/placeholder.jpg";
                    }
            ?>
                <div class="product-card" data-category="<?php echo $product['category_id']; ?>">
                    <?php if (!empty($badge)) : ?>
                        <div class="product-badge"><?php echo $badge; ?></div>
                    <?php endif; ?>
                    
                    <div class="product-image">
                        <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <!-- Debug info to check image path - can be enabled for troubleshooting -->
                        <div class="image-debug"><?php echo basename($image_path); ?></div>
                    </div>
                    
                    <div class="product-details">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        
                        <div class="star-rating">
                            <?php
                            // Display star rating
                            $rating = floatval($product['average_rating']);
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
                            <span class="rating-number">(<?php echo $product['review_count']; ?>)</span>
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
                    <li><a href="?page=<?php echo ($current_page - 1); ?><?php 
                        echo (!empty($search_term)) ? '&search=' . urlencode($search_term) : ''; 
                        echo ($category_filter > 0) ? '&category=' . $category_filter : ''; 
                        echo (!empty($sort_by)) ? '&sort=' . $sort_by : '';
                        echo (!empty($price_range)) ? '&price=' . $price_range : '';
                    ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                
                <?php
                // Determine range of page numbers to show
                $range = 3;
                $start_page = max(1, $current_page - $range);
                $end_page = min($total_pages, $current_page + $range);
                
                for ($i = $start_page; $i <= $end_page; $i++) :
                ?>
                    <li><a href="?page=<?php echo $i; ?><?php 
                        echo (!empty($search_term)) ? '&search=' . urlencode($search_term) : ''; 
                        echo ($category_filter > 0) ? '&category=' . $category_filter : ''; 
                        echo (!empty($sort_by)) ? '&sort=' . $sort_by : '';
                        echo (!empty($price_range)) ? '&price=' . $price_range : '';
                    ?>" <?php echo ($i == $current_page) ? 'class="active"' : ''; ?>><?php echo $i; ?></a></li>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages) : ?>
                    <li><a href="?page=<?php echo ($current_page + 1); ?><?php 
                        echo (!empty($search_term)) ? '&search=' . urlencode($search_term) : ''; 
                        echo ($category_filter > 0) ? '&category=' . $category_filter : ''; 
                        echo (!empty($sort_by)) ? '&sort=' . $sort_by : '';
                        echo (!empty($price_range)) ? '&price=' . $price_range : '';
                    ?>"><i class="fas fa-chevron-right"></i></a></li>
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

    <!-- JavaScript for cart functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (this.disabled) return;
                
                const productId = this.getAttribute('data-product-id');
                const originalText = this.innerHTML;
                const thisButton = this;
                
                // Immediately change button to "Added!"
                thisButton.innerHTML = '<i class="fas fa-check"></i> Added!';
                thisButton.classList.add('added');
                thisButton.disabled = true;
                
                // Create form data
                let formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', 1);
                formData.append('add_to_cart', true);
                
                fetch('shop.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    try {
                        // Try to parse as JSON first
                        const jsonData = JSON.parse(data);
                        if (jsonData.success) {
                            // Update cart count if needed
                            if (document.querySelector('.cart-count')) {
                                document.querySelector('.cart-count').textContent = jsonData.cart_count;
                            }
                            
                            // Show success message
                            const successMessage = document.createElement('div');
                            successMessage.className = 'alert alert-success fade-out';
                            successMessage.textContent = jsonData.message;
                            document.querySelector('.container').insertBefore(successMessage, document.querySelector('.top-filters-bar'));
                            
                            // Auto-hide the message after 3 seconds
                            setTimeout(() => {
                                successMessage.style.opacity = '0';
                                setTimeout(() => {
                                    successMessage.remove();
                                }, 500);
                            }, 3000);
                        } else {
                            console.error('Error:', jsonData.message);
                        }
                    } catch (e) {
                        // If not JSON, it might be a simple message or HTML redirect
                        console.log('Response was not JSON:', data);
                    }
                    
                    // After 1 second, change button back to original text
                    setTimeout(function() {
                        thisButton.innerHTML = originalText;
                        thisButton.classList.remove('added');
                        thisButton.disabled = false;
                    }, 1000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Reset button after error
                    setTimeout(function() {
                        thisButton.innerHTML = originalText;
                        thisButton.classList.remove('added');
                        thisButton.disabled = false;
                    }, 1000);
                });
            });
        });
        
        // Back to top button
        const btnTop = document.getElementById('btnTop');
        
        window.onscroll = function() {
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                btnTop.style.display = "block";
            } else {
                btnTop.style.display = "none";
            }
        };
        
        btnTop.addEventListener('click', function() {
            document.body.scrollTop = 0; // For Safari
            document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
        });
    });
    </script>
</body> 
</html>
                    