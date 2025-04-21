<?php
// Get current page for active state
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h1>SweetTreats</h1>
        <div class="toggle-btn" id="toggle-btn">≡</div>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item" data-title="Dashboard">
            <a href="index.php?page=dashboard" class="nav-link <?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>
        <li class="nav-item" data-title="Products">
            <a href="index.php?page=products" class="nav-link <?php echo ($current_page === 'products') ? 'active' : ''; ?>">
                <span class="nav-icon">🍬</span>
                <span class="nav-text">Products</span>
            </a>
        </li>
        <li class="nav-item" data-title="Add Product">
            <a href="index.php?page=add-product" class="nav-link <?php echo ($current_page === 'add-product') ? 'active' : ''; ?>">
                <span class="nav-icon">➕</span>
                <span class="nav-text">Add Product</span>
            </a>
        </li>
        <li class="nav-item dropdown" data-title="Categories">
            <a href="#" class="nav-link dropdown-toggle <?php echo (in_array($current_page, ['categories', 'add-category'])) ? 'active' : ''; ?>">
                <span class="nav-icon">🗂️</span>
                <span class="nav-text">Categories</span>
            </a>
            <div class="dropdown-menu <?php echo (in_array($current_page, ['categories', 'add-category'])) ? 'show' : ''; ?>">
                <a href="index.php?page=categories" class="dropdown-item <?php echo ($current_page === 'categories') ? 'active' : ''; ?>" >Manage Categories</a>
                <a href="index.php?page=add-category" class="dropdown-item <?php echo ($current_page === 'add-category') ? 'active' : ''; ?>"  >Add Category</a>
            </div>
        </li>
        <li class="nav-item" data-title="Customers">
            <a href="index.php?page=customers" class="nav-link <?php echo ($current_page === 'customers') ? 'active' : ''; ?>">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Customers</span>
            </a>
        </li>
        <li class="nav-item" data-title="Orders">
            <a href="index.php?page=orders" class="nav-link <?php echo ($current_page === 'orders') ? 'active' : ''; ?>">
                <span class="nav-icon">🛒</span>
                <span class="nav-text">Orders</span>
            </a>
        </li>
        <li class="nav-item" data-title="Reviews">
            <a href="index.php?page=reviews" class="nav-link <?php echo ($current_page === 'reviews') ? 'active' : ''; ?>">
                <span class="nav-icon">⭐</span>
                <span class="nav-text">Reviews</span>
            </a>
        </li>
        <li class="nav-item" data-title="Delivery">
            <a href="index.php?page=delivery" class="nav-link <?php echo ($current_page === 'delivery') ? 'active' : ''; ?>">
                <span class="nav-icon">🚚</span>
                <span class="nav-text">Delivery</span>
            </a>
        </li>
        <li class="nav-item" data-title="Top Banner">
            <a href="index.php?page=top-banner" class="nav-link <?php echo ($current_page === 'top-banner') ? 'active' : ''; ?>">
                <span class="nav-icon">📣</span>
                <span class="nav-text">Top Banner</span>
            </a>
        </li>
        <li class="nav-item" data-title="Logout">
            <a href="logout.php" class="nav-link">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Logout</span>
            </a>
        </li>
    </ul>
</div>

<script>
// JavaScript for sidebar toggle and dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar collapse
    const toggleBtn = document.getElementById('toggle-btn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('main-content').classList.toggle('expanded');
        });
    }
    
    // Dropdown toggle functionality
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            this.nextElementSibling.classList.toggle('show');
        });
    });
});
</script>