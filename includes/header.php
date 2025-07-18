<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection if not already included
if (!isset($conn) && file_exists('includes/db.php')) {
    require_once 'includes/db.php';
}

// Initialize cart count
$cart_count = 0;

// Get cart count based on user status
if (isset($_SESSION['user_id'])) {
    // User is logged in, get count from database
    $user_id = $_SESSION['user_id'];
    $cart_query = "SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    $cart_row = $cart_result->fetch_assoc();
    $cart_count = $cart_row['total'] ?: 0;
} else if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    // User is a guest, get count from session
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
} else {
    // Either no items in cart or use stored cart count
    $cart_count = isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0;
}
?>
<div class="announcement-bar">
    <i class="fas fa-truck"></i> Free delivery for orders above $50!
</div>

<header id="mainHeader">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <div class="logo">
        <a href="index.php"><h1>Sweet Treats</h1></a>
    </div>
    <nav>
        <ul>
            <li><a href="shop.php">Shop</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="contact.php">Contact Us</a></li>
        </ul>
    </nav>
    <div class="icons">
        <a href="cart.php" class="cart-icon">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-count"><?php echo $cart_count; ?></span>
        </a>
        <a href="account.php" class="profile-icon">
    <i class="fas fa-user"></i>
</a>

    </div>
</header>