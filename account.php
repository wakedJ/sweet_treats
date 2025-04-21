<?php
session_start(); // Start the session to access login information
include "includes/db.php"; // Include database connection

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_id = $is_logged_in ? $_SESSION['user_id'] : '';

// If user is logged in, fetch their profile information
$user_data = [];
$user_address = [];
if ($is_logged_in) {
    try {
        // Fetch user data
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
        }
        $stmt->close();
        
        // Fetch user address data
        $query = "SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user_address = $result->fetch_assoc();
        } else {
            // If no default address found, get any address
            $query = "SELECT * FROM user_addresses WHERE user_id = ? LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user_address = $result->fetch_assoc();
            }
        }
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error retrieving user data: " . $e->getMessage();
    }
}

// Process address update form if submitted
if (isset($_POST['update_address'])) {
    try {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $street_address = $_POST['street_address'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $postal_code = $_POST['postal_code'];
        $country = $_POST['country'];
        $phone_number = $_POST['phone_number'];
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // If address exists, update it
        if (!empty($user_address)) {
            $query = "UPDATE user_addresses SET 
                      first_name = ?, 
                      last_name = ?, 
                      street_address = ?, 
                      city = ?, 
                      state = ?, 
                      postal_code = ?, 
                      country = ?, 
                      phone_number = ?, 
                      is_default = ? 
                      WHERE address_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssssii", $first_name, $last_name, $street_address, $city, $state, $postal_code, $country, $phone_number, $is_default, $user_address['address_id']);
            $stmt->execute();
        } else {
            // Otherwise, insert new address
            $query = "INSERT INTO user_addresses (user_id, first_name, last_name, street_address, city, state, postal_code, country, phone_number, is_default) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issssssssi", $user_id, $first_name, $last_name, $street_address, $city, $state, $postal_code, $country, $phone_number, $is_default);
            $stmt->execute();
        }
        
        // If marked as default, update all other addresses to not be default
        if ($is_default) {
            $address_id = !empty($user_address) ? $user_address['address_id'] : $conn->insert_id;
            $query = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND address_id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $user_id, $address_id);
            $stmt->execute();
        }
        
        $_SESSION['message'] = "Address updated successfully!";
        header("Location: account.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating address: " . $e->getMessage();
    }
}

// Process profile update form if submitted
if (isset($_POST['update_profile'])) {
    try {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone_number = $_POST['phone_number'];
        
        // Update user information
        $query = "UPDATE users SET full_name = ?, email = ?, phone_number = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $full_name, $email, $phone_number, $user_id);
        $stmt->execute();
        
        // Update session data
        $_SESSION['user_name'] = $full_name;
        
        $_SESSION['message'] = "Profile updated successfully!";
        header("Location: account.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
    }
}

// Process password update form if submitted
if (isset($_POST['update_password'])) {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify passwords match
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New passwords do not match.";
            header("Location: account.php");
            exit();
        }
        
        // Verify current password
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (!password_verify($current_password, $user['password'])) {
                $_SESSION['error'] = "Current password is incorrect.";
                header("Location: account.php");
                exit();
            }
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
        
        $_SESSION['message'] = "Password updated successfully!";
        header("Location: account.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating password: " . $e->getMessage();
    }
}

// Get user's order history
$orders = [];
if ($is_logged_in) {
    try {
        $query = "SELECT o.*, 
                 (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                 FROM orders o 
                 WHERE o.user_id = ? 
                 ORDER BY o.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Also fetch order items for each order
            $order_id = $row['id'];
            $items_query = "SELECT oi.*, p.name, p.image_url FROM order_items oi 
                           JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = ?";
            $items_stmt = $conn->prepare($items_query);
            $items_stmt->bind_param("i", $order_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            $items = [];
            while ($item = $items_result->fetch_assoc()) {
                $items[] = $item;
            }
            
            $row['items'] = $items;
            $orders[] = $row;
            
            $items_stmt->close();
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error retrieving orders: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Sweet Treats</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/account.css">
    <style>
        /* Base styles */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fff8fa;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.828-1.415 1.415L51.8 0h2.827zM5.373 0l-.83.828L5.96 2.243 8.2 0H5.374zM48.97 0l3.657 3.657-1.414 1.414L46.143 0h2.828zM11.03 0L7.372 3.657 8.787 5.07 13.857 0H11.03zm32.284 0L49.8 6.485 48.384 7.9l-7.9-7.9h2.83zM16.686 0L10.2 6.485 11.616 7.9l7.9-7.9h-2.83zm20.97 0l9.315 9.314-1.414 1.414L34.828 0h2.83zM22.344 0L13.03 9.314l1.414 1.414L25.172 0h-2.83zM32 0l12.142 12.142-1.414 1.414L30 2.828 17.272 15.556l-1.414-1.414L28 2.828 17.272 14.142 15.858 12.73 28 .587l3.415 3.414L40.143 0H32zM0 0l28 28-1.414 1.414L0 2.828V0zm0 5.657l28 28L26.586 35.07 0 8.485v-2.83zm0 5.657l28 28-1.414 1.414L0 14.142v-2.83zm0 5.657l28 28L26.586 46.4 0 19.8v-2.83zm0 5.657l28 28-1.414 1.414L0 25.456v-2.83zm0 5.657l28 28-1.414 1.414L0 31.113v-2.83zM0 40l28 28-1.414 1.414L0 43.24v-3.24zm0 5.656l28 28L26.586 75.07 0 48.485v-2.83zm0 5.656l28 28-1.414 1.414L0 54.142v-2.83zm0 5.657l28 28-1.414 1.414L0 59.8v-2.83zm54.627 8.657L28 28 29.414 26.586 60 57.172v2.83zM54.627 60L28 33.373 29.414 31.96 60 62.544v-2.83zm-5.657 0L28 39.03l1.414-1.414L54.627 60h-5.657zm-5.657 0L28 44.686l1.414-1.414L48.97 60h-5.657zm-5.657 0L28 50.343l1.414-1.414L43.314 60h-5.657zm-5.657 0L28 56l1.414-1.414L37.657 60h-5.657z' fill='%23ff69b4' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            margin: 0;
            padding: 0;
        }
        
        h1, h2, h3 {
            font-family: 'Fredoka One', cursive;
        }
        
        /* Order History Styles from order_history.html */
        .order-history {
            margin-bottom: 60px;
        }
        
        .order-card {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255,105,180,0.15);
        }
        
        .order-header {
            background: linear-gradient(90deg, #fff0f5, #ffeaeb);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed #ffd1dc;
        }
        
        .order-number {
            color: #ff1493;
            margin: 0;
            font-size: 1.1rem;
        }
        
        .order-date {
            color: #8a2be2;
            font-weight: 600;
        }
        
        .order-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-delivered {
            background-color: #e6ffea;
            color: #32cd32;
            border: 1px solid #b3ffb3;
        }
        
        .status-processing {
            background-color: #fff8e6;
            color: #ffa500;
            border: 1px solid #ffe6b3;
        }
        
        .status-shipped {
            background-color: #e6f2ff;
            color: #0080ff;
            border: 1px solid #b3d9ff;
        }
        
        .order-items {
            padding: 0;
            list-style: none;
            margin: 0;
        }
        
        .order-item {
            display: flex;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s;
        }
        
        .order-item:hover {
            background-color: #fff8fa;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
            margin-right: 15px;
            background: #f8f0ff;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-size: 1rem;
            margin: 0 0 5px;
            color: #333;
        }
        
        .item-price {
            font-weight: 600;
            color: #8a2be2;
            margin: 0 0 8px;
        }
        
        .item-quantity {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }
        
        .item-rating {
            display: flex;
            align-items: center;
            margin-top: 8px;
        }
        
        .stars {
            display: inline-flex;
            position: relative;
        }
        
        .stars input {
            display: none;
        }
        
        .stars label {
            cursor: pointer;
            color: #ddd;
            font-size: 20px;
            padding: 0 2px;
            transition: color 0.2s;
        }
        
        .stars input:checked ~ label {
            color: #ffcc00;
        }
        
        .stars:not(:checked) > label:hover,
        .stars:not(:checked) > label:hover ~ label {
            color: #ffcc00;
        }
        
        .rating-text {
            margin-left: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .has-rated .stars label {
            pointer-events: none;
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            background-color: #fafafa;
        }
        
        .order-total {
            font-weight: 600;
            color: #ff1493;
        }
        
        .order-actions a {
            text-decoration: none;
            color: #8a2be2;
            font-weight: 600;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .order-actions a:hover {
            color: #ff1493;
        }
        
        .order-actions a + a {
            margin-left: 15px;
        }
        
        .ratings-note {
            text-align: center;
            margin: 30px 0;
            padding: 15px;
            background-color: #fff0f5;
            border-radius: 10px;
            border: 2px dashed #ffd1dc;
        }
        
        .ratings-note p {
            margin: 0;
            color: #ff1493;
            font-weight: 600;
        }
        
        /* Sweet-themed hero section for order history tab */
        .history-hero {
            background: linear-gradient(135deg, #ffccd5 0%, #ffd1dc 100%);
            padding: 40px 0;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 0 0 50% 50% / 20px;
            position: relative;
            overflow: hidden;
        }
        
        .history-hero::before {
            content: "";
            position: absolute;
            top: -20px;
            left: -20px;
            right: -20px;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='52' height='26' viewBox='0 0 52 26' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.2'%3E%3Cpath d='M10 10c0-2.21-1.79-4-4-4-3.314 0-6-2.686-6-6h2c0 2.21 1.79 4 4 4 3.314 0 6 2.686 6 6 0 2.21 1.79 4 4 4 3.314 0 6 2.686 6 6 0 2.21 1.79 4 4 4v2c-3.314 0-6-2.686-6-6 0-2.21-1.79-4-4-4-3.314 0-6-2.686-6-6zm25.464-1.95l8.486 8.486-1.414 1.414-8.486-8.486 1.414-1.414z' /%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: 0;
        }
        
        .history-hero h1 {
            color: #ff1493;
            margin-bottom: 15px;
            font-size: 2.5rem;
            text-shadow: 3px 3px 0px rgba(255,255,255,0.5);
            position: relative;
            z-index: 1;
        }
        
        .history-hero p {
            color: #8a2be2;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Decorative candy icons */
        .candy-icon {
            position: absolute;
            opacity: 0.6;
            z-index: 0;
            animation: float 6s ease-in-out infinite;
        }
        
        .candy-1 {
            top: 20%;
            left: 5%;
            font-size: 2rem;
            color: #8a2be2;
            animation-delay: 0s;
        }
        
        .candy-2 {
            top: 60%;
            left: 15%;
            font-size: 1.5rem;
            color: #ff1493;
            animation-delay: 1s;
        }
        
        .candy-3 {
            top: 30%;
            right: 10%;
            font-size: 2.2rem;
            color: #00bfff;
            animation-delay: 2s;
        }
        
        .candy-4 {
            top: 70%;
            right: 5%;
            font-size: 1.8rem;
            color: #32cd32;
            animation-delay: 1.5s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-15px) rotate(5deg);
            }
            100% {
                transform: translateY(0) rotate(0deg);
            }
        }
        
        /* Responsive styling */
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-date {
                margin-top: 5px;
            }
            
            .order-item {
                flex-direction: column;
            }
            
            .item-image {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
        
        /* Ensure consistent appearance across sections */
        .tab-content h3 {
            color: #ff1493;
            margin-bottom: 20px;
        }
        
        .account-btn {
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .account-btn:hover {
            background: linear-gradient(135deg, #ff1493, #ff69b4);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 105, 180, 0.3);
            color: white;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px 0;
            background-color: #fff0f5;
            border-radius: 16px;
        }
        
        .no-orders p {
            margin-bottom: 20px;
            color: #8a2be2;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="account-container">
        <!-- Display messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']); 
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']); 
                ?>
            </div>
        <?php endif; ?>
        
        <!-- This section shows when user is not logged in -->
        <div class="not-logged-in" style="<?php echo $is_logged_in ? 'display: none;' : ''; ?>">
            <div class="account-header">
                <h1>My Account</h1>
                <p>Sign in to manage your shipping address and receive special offers on our sweet treats!</p>
            </div>
            
            <div class="account-options">
                <div class="account-card">
                    <div class="card-header">
                        <i class="fas fa-sign-in-alt"></i>
                        <h2>Existing Customers</h2>
                        <p>Welcome back! Sign in to your account</p>
                    </div>
                    <div class="card-content">
                        <ul class="features-list">
                            <li><i class="fas fa-check-circle"></i> Save your shipping address</li>
                            <li><i class="fas fa-check-circle"></i> Faster checkout experience</li>
                            <li><i class="fas fa-check-circle"></i> Receive special offers & promotions</li>
                        </ul>
                        <a href="login.php" class="account-btn">Sign In</a>
                    </div>
                </div>
                
                <div class="account-card">
                    <div class="card-header">
                        <i class="fas fa-user-plus"></i>
                        <h2>New Customers</h2>
                        <p>Create an account for a sweeter experience</p>
                    </div>
                    <div class="card-content">
                        <a href="register.php" class="account-btn">Create Account</a>
                        <a href="shop.php" class="account-btn secondary">Continue as Guest</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- This section will show when user is logged in -->
        <div class="logged-in-container" id="loggedInContainer" style="<?php echo $is_logged_in ? 'display: block;' : 'display: none;'; ?>">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h2>Hello, <span id="userName"><?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?></span>!</h2>
                    <p id="userEmail"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
                </div>
                <div class="logout-button">
                    <a href="logout.php" class="account-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="account-tabs">
                <div class="account-tab active" data-tab="addresses">My Addresses</div>
                <div class="account-tab" data-tab="orders">Order History</div>
                <div class="account-tab" data-tab="settings">Account Settings</div>
            </div>
            
            <!-- Address Tab Content -->
            <div class="tab-content active" id="addressesTab">
                <h3>My Addresses</h3>
                
                <div class="address-form-container">
                    <h4>Update Address</h4>
                    <form method="post" action="account.php" class="address-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_address['first_name'] ?? ''); ?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_address['last_name'] ?? ''); ?>" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="street_address">Street Address</label>
                            <input type="text" id="street_address" name="street_address" value="<?php echo htmlspecialchars($user_address['street_address'] ?? ''); ?>" class="form-control" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user_address['city'] ?? ''); ?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                <label for="state">State/Province</label>
                                    <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($user_address['state'] ?? ''); ?>" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="postal_code">Postal/ZIP Code</label>
                                    <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user_address['postal_code'] ?? ''); ?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user_address['country'] ?? ''); ?>" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_address['phone_number'] ?? ''); ?>" class="form-control" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" id="is_default" name="is_default" class="form-check-input" <?php echo (isset($user_address['is_default']) && $user_address['is_default'] == 1) ? 'checked' : ''; ?>>
                            <label for="is_default" class="form-check-label">Set as default address</label>
                        </div>
                        
                        <button type="submit" name="update_address" class="account-btn">Save Address</button>
                    </form>
                </div>
            </div>
            
            <!-- Order History Tab Content -->
            <div class="tab-content" id="ordersTab">
                <div class="history-hero">
                    <i class="fas fa-candy-cane candy-icon candy-1"></i>
                    <i class="fas fa-cookie candy-icon candy-2"></i>
                    <i class="fas fa-ice-cream candy-icon candy-3"></i>
                    <i class="fas fa-birthday-cake candy-icon candy-4"></i>
                    <h1>Your Sweet Orders</h1>
                    <p>Track your past treats and relive the sweetness!</p>
                </div>
                
                <div class="order-history">
                    <?php if (empty($orders)): ?>
                        <div class="no-orders">
                            <i class="fas fa-shopping-bag" style="font-size: 3rem; color: #ffd1dc; margin-bottom: 20px;"></i>
                            <p>You haven't placed any orders yet.</p>
                            <a href="shop.php" class="account-btn">Shop Now</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <h4 class="order-number">Order #<?php echo htmlspecialchars($order['id']); ?></h4>
                                    <span class="order-date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                                    <span class="order-status status-<?php echo strtolower($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                                </div>
                                
                                <ul class="order-items">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li class="order-item">
                                            <div class="item-image">
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            </div>
                                            <div class="item-details">
                                                <h5 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                <p class="item-price">$<?php echo number_format($item['price'], 2); ?></p>
                                                <p class="item-quantity">Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                                                
                                                <?php if ($order['status'] == 'Delivered'): ?>
                                                    <div class="item-rating <?php echo (isset($item['rating']) && $item['rating'] > 0) ? 'has-rated' : ''; ?>">
                                                        <form class="stars">
                                                            <input type="radio" id="star5-<?php echo $item['id']; ?>" name="rating-<?php echo $item['id']; ?>" value="5" <?php echo (isset($item['rating']) && $item['rating'] == 5) ? 'checked' : ''; ?>>
                                                            <label for="star5-<?php echo $item['id']; ?>">★</label>
                                                            <input type="radio" id="star4-<?php echo $item['id']; ?>" name="rating-<?php echo $item['id']; ?>" value="4" <?php echo (isset($item['rating']) && $item['rating'] == 4) ? 'checked' : ''; ?>>
                                                            <label for="star4-<?php echo $item['id']; ?>">★</label>
                                                            <input type="radio" id="star3-<?php echo $item['id']; ?>" name="rating-<?php echo $item['id']; ?>" value="3" <?php echo (isset($item['rating']) && $item['rating'] == 3) ? 'checked' : ''; ?>>
                                                            <label for="star3-<?php echo $item['id']; ?>">★</label>
                                                            <input type="radio" id="star2-<?php echo $item['id']; ?>" name="rating-<?php echo $item['id']; ?>" value="2" <?php echo (isset($item['rating']) && $item['rating'] == 2) ? 'checked' : ''; ?>>
                                                            <label for="star2-<?php echo $item['id']; ?>">★</label>
                                                            <input type="radio" id="star1-<?php echo $item['id']; ?>" name="rating-<?php echo $item['id']; ?>" value="1" <?php echo (isset($item['rating']) && $item['rating'] == 1) ? 'checked' : ''; ?>>
                                                            <label for="star1-<?php echo $item['id']; ?>">★</label>
                                                        </form>
                                                        <span class="rating-text"><?php echo isset($item['rating']) ? 'Thanks for rating!' : 'Rate this product'; ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <div class="order-footer">
                                    <div class="order-total">
                                        Total: $<?php echo number_format($order['total_amount'], 2); ?>
                                    </div>
                                    <div class="order-actions">
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>">View Details</a>
                                        <?php if ($order['status'] == 'Delivered'): ?>
                                            <a href="reorder.php?id=<?php echo $order['id']; ?>">Reorder</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="ratings-note">
                            <p>Love our treats? Let us know by rating your purchases!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Account Settings Tab Content -->
            <div class="tab-content" id="settingsTab">
                <h3>Account Settings</h3>
                
                <div class="settings-section">
                    <h4>Profile Information</h4>
                    <form method="post" action="account.php" class="settings-form">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>" class="form-control">
                        </div>
                        
                        <button type="submit" name="update_profile" class="account-btn">Update Profile</button>
                    </form>
                </div>
                
                <div class="settings-section">
                    <h4>Change Password</h4>
                    <form method="post" action="account.php" class="settings-form">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="update_password" class="account-btn">Update Password</button>
                    </form>
                </div>
                
                <div class="settings-section">
                    <h4>Communication Preferences</h4>
                    <form method="post" action="update_preferences.php" class="settings-form">
                        <div class="form-check mb-3">
                            <input type="checkbox" id="email_promotions" name="email_promotions" class="form-check-input" <?php echo (isset($user_data['email_promotions']) && $user_data['email_promotions'] == 1) ? 'checked' : ''; ?>>
                            <label for="email_promotions" class="form-check-label">Email me about promotions and new treats</label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" id="sms_notifications" name="sms_notifications" class="form-check-input" <?php echo (isset($user_data['sms_notifications']) && $user_data['sms_notifications'] == 1) ? 'checked' : ''; ?>>
                            <label for="sms_notifications" class="form-check-label">Send me SMS order updates</label>
                        </div>
                        
                        <button type="submit" name="update_preferences" class="account-btn">Save Preferences</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab functionality
        const tabs = document.querySelectorAll('.account-tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked tab
                tab.classList.add('active');
                
                // Hide all tab contents
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Show selected tab content
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId + 'Tab').classList.add('active');
            });
        });
        
        // Product rating functionality
        const ratings = document.querySelectorAll('.stars input');
        ratings.forEach(rating => {
            rating.addEventListener('change', function() {
                const productId = this.name.split('-')[1];
                const ratingValue = this.value;
                
                // Here you would typically make an AJAX call to save the rating
                console.log(`Rating product ${productId} with ${ratingValue} stars`);
                
                // For demo purposes, we'll just update the UI
                const ratingContainer = this.closest('.item-rating');
                ratingContainer.classList.add('has-rated');
                ratingContainer.querySelector('.rating-text').textContent = 'Thanks for rating!';
                
                // You could use fetch to send the rating to the server:
                /*
                fetch('save_rating.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&rating=${ratingValue}`
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Rating saved:', data);
                })
                .catch(error => {
                    console.error('Error saving rating:', error);
                });
                */
            });
        });
    </script>
</body>
</html>