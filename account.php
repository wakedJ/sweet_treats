<?php
session_start();

include "includes/db.php";
include "includes/auth_check.php";
include "includes/address_operations.php";
include "includes/profile_operations.php";
include "includes/order_operations.php";
// Add at the top of account.php after session_start()
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
    <link rel="stylesheet" href="css/modal.css">
    
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
        
        <?php if (!$is_logged_in): ?>
            <?php include 'partials/not_logged_in.php'; ?>
        <?php else: ?>
             <div class="logged-in-container" id="loggedInContainer" style="display: block !important;">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <h2>Hello, <span id="userName"><?php echo htmlspecialchars($user_data['first_name'] ?? '') . ' ' . htmlspecialchars($user_data['last_name'] ?? ''); ?></span>!</h2>
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
                
                <?php include 'partials/addresses_tab.php'; ?>
                <?php include 'partials/orders_tab.php'; ?>
                <?php include 'partials/settings_tab.php'; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/account.js"></script>
</body>
</html>