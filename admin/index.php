<?php
// Define the default page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Validate the page parameter to prevent directory traversal
$allowed_pages = ['dashboard', 'products', 'add-product', 'categories', 
                 'add-category', 'customers', 'orders', 'reviews', 
                 'delivery', 'top-banner'];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard'; // Default to dashboard if page is not valid
}

// Get the page title for the header
function getPageTitle($page) {
    // Convert hyphenated page name to title case
    return ucwords(str_replace('-', ' ', $page));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SweetTreats Admin Panel - <?php echo getPageTitle($page); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>    
    <!-- Candy Floaters -->
    <div class="candy-floater candy-1">🍭</div>
    <div class="candy-floater candy-2">🍬</div>
    <div class="candy-floater candy-3">🍪</div>
    <div class="candy-floater candy-4">🧁</div>
    
    <!-- Include Sidebar Navigation -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="main-content" id="main-content">
        <!-- Include Header -->
        <?php include 'components/header.php'; ?>
        
        <!-- Content Area - Load the requested page -->
        <div class="content">
            <?php include "pages/{$page}.php"; ?>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html> 