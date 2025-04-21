<?php
// Connect to database
require_once 'includes/db.php';

// Check if there are any products
$check_query = "SELECT COUNT(*) as count FROM products";
$check_result = mysqli_query($conn, $check_query);
$check_data = mysqli_fetch_assoc($check_result);

if ($check_data['count'] == 0) {
    // No products exist, so add some sample ones
    
    // First make sure we have at least one category
    $cat_query = "SELECT COUNT(*) as count FROM categories";
    $cat_result = mysqli_query($conn, $cat_query);
    $cat_data = mysqli_fetch_assoc($cat_result);
    
    if ($cat_data['count'] == 0) {
        // Add a default category
        $insert_cat = "INSERT INTO categories (name, description, status) VALUES 
                      ('Candy', 'Various candy products', 1),
                      ('Chocolate', 'Delicious chocolate treats', 1),
                      ('Baked Goods', 'Freshly baked treats', 1)";
        mysqli_query($conn, $insert_cat);
    }
    
    // Get categories
    $cat_query = "SELECT id FROM categories LIMIT 3";
    $cat_result = mysqli_query($conn, $cat_query);
    $categories = [];
    while ($row = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $row['id'];
    }
    
    // If no categories, use 1 as default
    if (empty($categories)) {
        $categories = [1, 1, 1];
    }
    
    // Add sample products
    $insert_products = "INSERT INTO products (name, description, price, category_id, stock, tag, image, average_rating) VALUES 
                      ('Chocolate Bar', 'Delicious milk chocolate bar', 2.99, {$categories[0]}, 100, 'popular', 'chocolate-bar.jpg', 4.5),
                      ('Gummy Bears', 'Sweet and chewy gummy bears', 3.49, {$categories[1]}, 150, 'best seller', 'gummy-bears.jpg', 4.2),
                      ('Jelly Beans', 'Assorted flavors of jelly beans', 1.99, {$categories[0]}, 200, 'new', 'jelly-beans.jpg', 3.8),
                      ('Caramel Popcorn', 'Sweet caramel covered popcorn', 4.99, {$categories[2]}, 75, 'limited', 'caramel-popcorn.jpg', 4.7),
                      ('Lollipops', 'Assorted fruit flavored lollipops', 0.99, {$categories[1]}, 300, 'none', 'lollipops.jpg', 3.9),
                      ('Chocolate Chip Cookies', 'Freshly baked chocolate chip cookies', 5.99, {$categories[2]}, 50, 'hot', 'chocolate-cookies.jpg', 4.8),
                      ('Marshmallows', 'Soft and fluffy marshmallows', 2.49, {$categories[1]}, 125, 'none', 'marshmallows.jpg', 3.6),
                      ('Peanut Brittle', 'Crunchy peanut brittle', 3.99, {$categories[0]}, 60, 'on sale', 'peanut-brittle.jpg', 4.0)";
                      
    if (mysqli_query($conn, $insert_products)) {
        echo "<h2>Added sample products successfully!</h2>";
    } else {
        echo "Error adding products: " . mysqli_error($conn);
    }
}

// Redirect back to shop page
echo "<p>Redirecting to shop...</p>";
echo "<script>setTimeout(function(){ window.location = 'shop.php'; }, 2000);</script>";
?>