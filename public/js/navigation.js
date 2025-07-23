// Header loading and page navigation functionality
function loadHeader() {
    const headerContainer = document.getElementById('header-container');
    
    // Determine current page title
    const currentPage = window.location.pathname.split('/').pop().split('.')[0];
    let pageTitle = currentPage.charAt(0).toUpperCase() + currentPage.slice(1);
    if (pageTitle === '' || pageTitle === 'Index') pageTitle = 'Dashboard';
    
    headerContainer.innerHTML = `
        <div class="header">
            <div class="header-left">
                <h2 class="page-title" id="page-title">${pageTitle}</h2>
            </div>
            <div class="header-right">
                <div class="notification-icon">
                    <span>🔔</span>
                    <span class="notification-badge">3</span>
                </div>
                <div class="profile-section">
                    <img src="/api/placeholder/40/40" alt="Admin Profile" class="profile-img">
                    <span class="profile-name">Admin User</span>
                </div>
            </div>
        </div>
    `;
}

// Load header when the DOM is ready
document.addEventListener('DOMContentLoaded', loadHeader);