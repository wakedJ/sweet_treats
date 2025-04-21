<?php
// Get current page for title
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$page_title = ucwords(str_replace('-', ' ', $current_page));
?>

<div class="header">
    <div class="header-left">
        <h2 class="page-title" id="page-title"><?php echo $page_title; ?></h2>
    </div>
    <div class="header-right">
        <div class="notification-icon">
            <span>🔔</span>
            <span class="notification-badge">3</span>
        </div>
        <div class="profile-section">
            <a href="login.php" class="profile-icon"><i class="fas fa-user"></i></a>
            <span class="profile-name">Admin User</span>
        </div>
    </div>
</div>