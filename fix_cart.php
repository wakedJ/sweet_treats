<?php
session_start();
require_once 'includes/db.php';

// Recreate cart from database if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get cart items from database - without the price column
    $sql = "SELECT ci.product_id, ci.quantity, p.price 
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        echo "Error preparing statement: " . mysqli_error($conn);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $result = false;
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
    } else {
        echo "Error executing statement: " . mysqli_stmt_error($stmt);
        exit;
    }
    
    // Initialize cart
    $_SESSION['cart'] = [];
    $subtotal = 0;
    
    // If we have results, process them
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['cart'][$row['product_id']] = [
                'product_id' => $row['product_id'],
                'quantity' => $row['quantity'],
                'price' => $row['price']
            ];
            
            // Calculate subtotal
            $subtotal += $row['price'] * $row['quantity'];
        }
        
        // Update cart count
        $_SESSION['cart_count'] = count($_SESSION['cart']);
        
        // Update cart summary
        if (isset($_SESSION['cart_summary'])) {
            $_SESSION['cart_summary']['subtotal'] = $subtotal;
            $_SESSION['cart_summary']['total'] = $subtotal + $_SESSION['cart_summary']['delivery_fee'] - $_SESSION['cart_summary']['promo_discount'];
        } else {
            $_SESSION['cart_summary'] = [
                'subtotal' => $subtotal,
                'promo_discount' => 0,
                'delivery_fee' => 0,
                'total' => $subtotal,
                'delivery_type' => 'pickup',
                'applied_promo' => '',
                'promo_id' => ''
            ];
        }
        
        echo "<h2>Cart fixed!</h2>";
        echo "<p>You now have " . count($_SESSION['cart']) . " items.</p>";
        echo "<p>Subtotal: $" . number_format($subtotal, 2) . "</p>";
        echo "<p><a href='cart.php'>Go to cart</a> | <a href='checkout.php'>Go to checkout</a></p>";
    } else {
        echo "<h2>Your cart is empty</h2>";
        echo "<p>No items found in your cart.</p>";
        echo "<p><a href='shop.php'>Go to shop</a></p>";
    }
} else {
    echo "<h2>Please log in first</h2>";
    echo "<p><a href='login.php'>Log in</a></p>";
}
?>