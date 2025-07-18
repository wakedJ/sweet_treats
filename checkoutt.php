<?php
// Simple session debugger for checkout issues
// Place this file in the same directory as your checkout.php
// Then access it via: http://yoursite.com/checkout_debug.php

session_start();
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Debug Tool</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #333;
        }
        h1, h2 {
            color: #8e44ad;
        }
        pre {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .actions {
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #8e44ad;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #7d3c98;
        }
        .warn {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Checkout Debug Tool</h1>
        
        <div class="card">
            <h2>Cart Status</h2>
            <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                <p>✅ Cart data exists in session</p>
                <p>Number of items: <strong><?php echo count($_SESSION['cart']); ?></strong></p>
            <?php else: ?>
                <p class="warn">❌ Cart data is empty or not set in session</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Cart Summary Status</h2>
            <?php if (isset($_SESSION['cart_summary']) && !empty($_SESSION['cart_summary'])): ?>
                <p>✅ Cart summary exists in session</p>
                <p>Total: <strong>$<?php echo isset($_SESSION['cart_summary']['total']) ? 
                    number_format($_SESSION['cart_summary']['total'], 2) : 'Not set'; ?></strong></p>
            <?php else: ?>
                <p class="warn">❌ Cart summary is empty or not set in session</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Session Data (Cart)</h2>
            <?php if (isset($_SESSION['cart'])): ?>
                <pre><?php print_r($_SESSION['cart']); ?></pre>
            <?php else: ?>
                <p class="warn">No cart data in session</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Session Data (Cart Summary)</h2>
            <?php if (isset($_SESSION['cart_summary'])): ?>
                <pre><?php print_r($_SESSION['cart_summary']); ?></pre>
            <?php else: ?>
                <p class="warn">No cart summary in session</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>All Session Data</h2>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
        
        <div class="actions">
            <a href="cart.php" class="btn">Go to Cart</a>
            <a href="checkout.php" class="btn">Go to Checkout</a>
            <?php if (isset($_SESSION['cart'])): ?>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="fix_cart" value="1">
                    <button type="submit" class="btn">Fix Cart Structure</button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php
        // Simple fix for cart structure if needed
        if (isset($_POST['fix_cart']) && isset($_SESSION['cart'])) {
            $fixed_cart = [];
            foreach ($_SESSION['cart'] as $key => $item) {
                // Make sure each cart item has the required structure
                if (is_array($item) && isset($item['product_id'])) {
                    $fixed_cart[$key] = $item;
                } else if (is_numeric($key)) {
                    // If the key is the product_id
                    $price = null;
                    $quantity = is_array($item) && isset($item['quantity']) ? $item['quantity'] : 1;
                    
                    if (is_array($item) && isset($item['price'])) {
                        $price = $item['price'];
                    }
                    
                    $fixed_cart[$key] = [
                        'product_id' => $key,
                        'quantity' => $quantity,
                        'price' => $price
                    ];
                }
            }
            
            $_SESSION['cart'] = $fixed_cart;
            echo '<div class="card"><p>Cart structure has been fixed. Refresh to see changes.</p></div>';
        }
        ?>
    </div>
</body>
</html>