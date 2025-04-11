// Sidebar component and functionality
function loadSidebar() {
    const sidebarContainer = document.getElementById('sidebar-container');
    
    sidebarContainer.innerHTML = `
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>SweetTreats Admin</h1>
                <div class="logo-small">🍭</div>
                <div class="toggle-btn" id="toggle-btn">≡</div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item" data-title="Dashboard">
                    <a href="index.html" class="nav-link" id="dashboard-link" data-section="dashboard">
                        <span class="nav-icon">📊</span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item" data-title="Products">
                    <a href="pages/products.html" class="nav-link" id="products-link" data-section="products">
                        <span class="nav-icon">🍬</span>
                        <span class="nav-text">Products</span>
                    </a>
                </li>
                <!-- Rest of your navigation items -->
            </ul>
        </div>
    `;
    
    // Toggle sidebar functionality
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const toggleBtn = document.getElementById('toggle-btn');
    
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    });
    
    // Handle mobile sidebar
    function adjustSidebar() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        } else {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
        }
    }
    
    window.addEventListener('resize', adjustSidebar);
    // Initial call
    adjustSidebar();
    
    // Set active link based on current page
    const currentPage = window.location.pathname.split('/').pop().split('.')[0];
    const activeLink = document.getElementById(`${currentPage}-link`) || document.getElementById('dashboard-link');
    
    if (activeLink) {
        // Remove active class from all links
        document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
        // Add active class to current page link
        activeLink.classList.add('active');
    }
}

// Load sidebar when the DOM is ready
document.addEventListener('DOMContentLoaded', loadSidebar);