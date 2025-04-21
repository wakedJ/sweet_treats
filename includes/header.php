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
if (isset($_SESSION['user_id']) && isset($conn)) {
    // For logged-in users, get count from database
    $user_id = $_SESSION['user_id'];
    $count_query = "SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cart_count = $row['total'] ? intval($row['total']) : 0;
} else if (isset($_SESSION['cart'])) {
    // For guest users, count from session
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
<div class="announcement-bar">
    <i class="fas fa-truck"></i> Free delivery for orders above $50!
</div>

<header id="mainHeader">
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
        <a href="<?php echo isset($_SESSION['user_id']) ? 'account.php' : 'login.php'; ?>" class="profile-icon">
            <i class="fas fa-user"></i>
        </a>
    </div>
</header>
<style>
    .cart-icon {
    position: relative;
}

.cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #ff6b6b;
    color: white;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2px;
}

/* Hide the cart count if it's zero */
.cart-count:empty {
    display: none;
}
</style>