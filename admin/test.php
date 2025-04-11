<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SweetTreats Admin</title>
    <!-- CSS Files -->
    <link rel="stylesheet" href="../css/admin.css">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Candy Floaters -->
    <div class="candy-floater candy-1">🍭</div>
    <div class="candy-floater candy-2">🍬</div>
    <div class="candy-floater candy-3">🍪</div>
    <div class="candy-floater candy-4">🧁</div>
    
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>SweetTreats Admin</h1>
            <div class="logo-small">🍭</div>
            <div class="toggle-btn" id="toggle-btn">≡</div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item" data-title="Dashboard">
                <a href="#" class="nav-link active" data-section="dashboard">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item" data-title="Products">
                <a href="#" class="nav-link" data-section="products">
                    <span class="nav-icon">🍬</span>
                    <span class="nav-text">Products</span>
                </a>
            </li>
            <li class="nav-item" data-title="Add Product">
                <a href="#" class="nav-link" data-section="add-product">
                    <span class="nav-icon">➕</span>
                    <span class="nav-text">Add Product</span>
                </a>
            </li>
            <li class="nav-item" data-title="Customers">
                <a href="#" class="nav-link" data-section="customers">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Customers</span>
                </a>
            </li>
            <li class="nav-item" data-title="Orders">
                <a href="#" class="nav-link" data-section="orders">
                    <span class="nav-icon">🛒</span>
                    <span class="nav-text">Orders</span>
                </a>
            </li>
            <li class="nav-item" data-title="Promotions">
                <a href="#" class="nav-link">
                    <span class="nav-icon">🎁</span>
                    <span class="nav-text">Promotions</span>
                </a>
            </li>
            <li class="nav-item" data-title="Reviews">
                <a href="#" class="nav-link">
                    <span class="nav-icon">⭐</span>
                    <span class="nav-text">Reviews</span>
                </a>
            </li>
            <li class="nav-item" data-title="Settings">
                <a href="#" class="nav-link">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
            <li class="nav-item" data-title="Logout">
                <a href="#" class="nav-link">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content Area -->
    <div class="main-content" id="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h2 class="page-title" id="page-title">Dashboard</h2>
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
        
        <!-- Content Area -->
        <div class="content">
            <!-- Dashboard Section -->
            <section id="dashboard-section">
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-icon">💰</div>
                        <h3 class="card-title">Total Revenue</h3>
                        <div class="card-value">$24,582</div>
                        <div class="card-stat positive">↑ 12.5% from last month</div>
                    </div>
                    <div class="card">
                        <div class="card-icon">📦</div>
                        <h3 class="card-title">Products Sold</h3>
                        <div class="card-value">1,283</div>
                        <div class="card-stat positive">↑ 8.3% from last month</div>
                    </div>
                    <div class="card">
                        <div class="card-icon">👥</div>
                        <h3 class="card-title">New Customers</h3>
                        <div class="card-value">347</div>
                        <div class="card-stat positive">↑ 15.2% from last month</div>
                    </div>
                    <div class="card">
                        <div class="card-icon">⭐</div>
                        <h3 class="card-title">Average Rating</h3>
                        <div class="card-value">4.8</div>
                        <div class="card-stat positive">↑ 0.2 from last month</div>
                    </div>
                </div>
                
                <div class="charts-container">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3 class="chart-title">Sales Overview</h3>
                            <div class="chart-actions">
                                <button class="chart-btn active">Weekly</button>
                                <button class="chart-btn">Monthly</button>
                                <button class="chart-btn">Yearly</button>
                            </div>
                        </div>
                        <div class="chart-body">
                            <!-- Chart would be inserted here with a JS library -->
                            <div style="height: 250px; display: flex; align-items: center; justify-content: center; color: #999;">
                                Sales Chart Visualization
                            </div>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3 class="chart-title">Top Products</h3>
                            <div class="chart-actions">
                                <button class="chart-btn active">This Week</button>
                                <button class="chart-btn">This Month</button>
                            </div>
                        </div>
                        <div class="chart-body">
                            <!-- Chart would be inserted here with a JS library -->
                            <div style="height: 250px; display: flex; align-items: center; justify-content: center; color: #999;">
                                Products Chart Visualization
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Recent Orders</h3>
                        <div class="search-container">
                            <span class="search-icon">🔍</span>
                            <input type="text" class="search-input" placeholder="Search orders...">
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Products</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#ORD-0123</td>
                                <td>Jane Smith</td>
                                <td>Chocolate Truffle Box</td>
                                <td>$42.99</td>
                                <td>03/08/2025</td>
                                <td><span class="status status-completed">Completed</span></td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon">👁️</div>
                                        <div class="action-icon edit-icon">✏️</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-0122</td>
                                <td>Mike Johnson</td>
                                <td>Candy Gift Basket</td>
                                <td>$65.50</td>
                                <td>03/07/2025</td>
                                <td><span class="status status-active">Shipped</span></td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon">👁️</div>
                                        <div class="action-icon edit-icon">✏️</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-0121</td>
                                <td>Sarah Williams</td>
                                <td>Assorted Cupcakes</td>
                                <td>$28.75</td>
                                <td>03/07/2025</td>
                                <td><span class="status status-pending">Processing</span></td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon">👁️</div>
                                        <div class="action-icon edit-icon">✏️</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <script src="js/admin.js"></script>
</body>
</html>