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
        <li class="nav-item" data-title="Messages">
            <a href="index.php?page=messages" class="nav-link <?php echo ($current_page === 'messages') ? 'active' : ''; ?>">
                <span class="nav-icon">✉️</span>
                <span class="nav-text">Messages</span>
            </a>
        </li>
        <li class="nav-item" data-title="Reviews">
            <a href="index.php?page=reviews" class="nav-link <?php echo ($current_page === 'reviews') ? 'active' : ''; ?>">
                <span class="nav-icon">⭐</span>
                <span class="nav-text">Reviews</span>
            </a>
        </li>
        <li class="nav-item" data-title="Promo">
            <a href="index.php?page=promo" class="nav-link <?php echo ($current_page === 'promo') ? 'active' : ''; ?>">
                <span class="nav-icon">📢</span>
                <span class="nav-text">Promo</span>
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
            <a href="index.php?page=logout" class="nav-link">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Logout</span>
            </a>
        </li>
    </ul>
</div>

<script>
// JavaScript for responsive sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const toggleBtn = document.getElementById('toggle-btn');
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    // Create mobile menu button
    const mobileMenuBtn = document.createElement('button');
    mobileMenuBtn.className = 'mobile-menu-btn';
    mobileMenuBtn.innerHTML = '☰';
    mobileMenuBtn.id = 'mobile-menu-btn';
    
    // Create overlay for mobile
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    overlay.id = 'sidebar-overlay';
    
    // Insert mobile menu button into header
    const header = document.querySelector('.header');
    const headerLeft = document.querySelector('.header-left');
    if (header && headerLeft) {
        headerLeft.insertBefore(mobileMenuBtn, headerLeft.firstChild);
    }
    
    // Insert overlay into body
    document.body.appendChild(overlay);
    
    // Check if we're on mobile
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Toggle sidebar collapse (desktop)
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            if (!isMobile()) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                // Save state to localStorage (if needed)
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            }
        });
    }
    
    // Mobile menu toggle
    mobileMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (isMobile()) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        }
    });
    
    // Close mobile menu when clicking overlay
    overlay.addEventListener('click', function() {
        if (isMobile()) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
    
    // Close mobile menu when clicking a nav link
    const navLinks = document.querySelectorAll('.nav-link:not(.dropdown-toggle)');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (isMobile()) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Dropdown toggle functionality
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdownMenu = this.nextElementSibling;
            
            // Close other dropdowns
            dropdownToggles.forEach(otherToggle => {
                if (otherToggle !== this) {
                    const otherMenu = otherToggle.nextElementSibling;
                    if (otherMenu) {
                        otherMenu.classList.remove('show');
                    }
                }
            });
            
            // Toggle current dropdown
            if (dropdownMenu) {
                dropdownMenu.classList.toggle('show');
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const mobile = isMobile();
        
        if (!mobile) {
            // Desktop mode
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            
            // Restore collapsed state if it was saved
            const wasCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (wasCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        } else {
            // Mobile mode - ensure sidebar is hidden
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            if (!sidebar.classList.contains('show')) {
                document.body.style.overflow = '';
            }
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            dropdownToggles.forEach(toggle => {
                const dropdownMenu = toggle.nextElementSibling;
                if (dropdownMenu) {
                    dropdownMenu.classList.remove('show');
                }
            });
        }
    });
    
    // Initialize sidebar state based on screen size
    if (isMobile()) {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('expanded');
    } else {
        // Restore saved state on desktop
        const wasCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (wasCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    }
    
    // Prevent body scroll when mobile menu is open
    sidebar.addEventListener('touchmove', function(e) {
        if (isMobile() && sidebar.classList.contains('show')) {
            e.stopPropagation();
        }
    });
});
</script>