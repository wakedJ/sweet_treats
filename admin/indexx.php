
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SweetTreats Admin Panel</title>
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
    
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>SweetTreats</h1>
            
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
            <li class="nav-item dropdown" data-title="Categories">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <span class="nav-icon">🗂️</span>
                    <span class="nav-text">Categories</span>
                </a>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item" data-section="categories">Manage Categories</a>
                    <a href="#" class="dropdown-item" data-section="add-category">Add Category</a>
                </div>
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
            <li class="nav-item" data-title="Reviews">
                <a href="#" class="nav-link" data-section="reviews">
                    <span class="nav-icon">⭐</span>
                    <span class="nav-text">Reviews</span>
                </a>
            </li>
           <!-- Delivery Section Link -->
            <li class="nav-item" data-title="Delivery">
                <a href="#" class="nav-link" data-section="delivery">
                    <span class="nav-icon">🚚</span>
                    <span class="nav-text">Delivery</span>
                </a>
            </li>

            <!-- Top Banner Section Link -->
            <li class="nav-item" data-title="Top Banner">
                <a href="#" class="nav-link" data-section="top-banner">
                    <span class="nav-icon">📣</span>
                    <span class="nav-text">Top Banner</span>
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
                    <a href="login.php" class="profile-icon"><i class="fas fa-user"></i></a>
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
                            <!-- Chart will be inserted here with a JS library -->
                            <canvas id="salesChart" style="height: 250px; width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                            
            </section>
            
            <!-- Products Section -->
            <section id="products-section">
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Product Management</h3>
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" class="search-input" placeholder="Search products...">
            </div>
            <div class="filter-container">
                <select class="filter-select">
                    <option>All Ratings</option>
                    <option>5 Stars</option>
                    <option>4 Stars</option>
                    <option>3 Stars</option>
                    <option>2 Stars</option>
                    <option>1 Star</option>
                </select>
                <select class="filter-select">
                    <option>All Products</option>
                    <option>Cooking</option>
                    <option>Korean</option>
                    <option>Cleaning</option>
                    <option>Drinks</option>
                    <option>Ice-Cream</option>
                </select>
            </div>
            <div class="action-buttons">
                <button class="action-btn" data-section="add-product">➕ Add Product</button>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Product Row 1 -->
                <tr>
                    <td>
                        <div class="product-info">
                            <img src="../images/products/785_8419.jpg" alt="Product" class="product-img">
                            <div>Cracotte Chocolate</div>
                        </div>
                    </td>
                    <td>Chocolates</td>
                    <td>$12.99</td>
                    <td>10</td>
                    <td>
                        <div class="rating">
                            <div class="stars">★★★★★</div>
                            <span>5.0</span>
                        </div>
                    </td>
                    <td><span class="status status-pending">Low In Stock</span></td>
                    <td>
                        <div class="action-icons">
                            <div class="action-icon edit-icon">✏️</div>
                            <div class="action-icon delete-icon">🗑️</div>
                        </div>
                    </td>
                </tr>
                <!-- Product Row 2 -->
                <tr>
                    <td>
                        <div class="product-info">
                            <img src="../images/products/616W6kCbybS._SX679_.jpg" alt="Product" class="product-img">
                            <div>Lemon Pepper Seasoning</div>
                        </div>
                    </td>
                    <td>Spices</td>
                    <td>$8.49</td>
                    <td>30</td>
                    <td>
                        <div class="rating">
                            <div class="stars">★★★★☆</div>
                            <span>4.3</span>
                        </div>
                    </td>
                    <td><span class="status status-active">In Stock</span></td>
                    <td>
                        <div class="action-icons">
                            <div class="action-icon edit-icon">✏️</div>
                            <div class="action-icon delete-icon">🗑️</div>
                        </div>
                    </td>
                </tr>
                <!-- Product Row 3 -->
                <tr>
                    <td>
                        <div class="product-info">
                            <img src="../images/products/Kiddylicious-apple-soft-biscotti-600x.webp" alt="Product" class="product-img">
                            <div>Kiddylicious apple soft biscotti</div>
                        </div>
                    </td>
                    <td>Candy</td>
                    <td>$24.99</td>
                    <td>0</td>
                    <td>
                        <div class="rating">
                            <div class="stars">★★★★★</div>
                            <span>4.8</span>
                        </div>
                    </td>
                    <td><span class="status status-inactive">Out Of Stock</span></td>
                    <td>
                        <div class="action-icons">
                            <div class="action-icon edit-icon">✏️</div>
                            <div class="action-icon delete-icon">🗑️</div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
            <!-- Add Product Section -->
            <section id="add-product-section">
    <div class="form-section">
        <div class="form-header">
            <h3>Add New Product</h3>
        </div>
        <form id="product-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="product-name">Product Name</label>
                    <input type="text" id="product-name" class="form-control" placeholder="Enter product name" required>
                </div>
                <div class="form-group">
                    <label for="product-category">Category</label>
                    <select id="product-category" class="form-control" required>
                        <option value="" disabled selected>Select a category</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="cooking">Cooking</option>
                        <option value="candy">Candy</option>
                        <option value="beverages">Drinks</option>
                        <option value="household">Icecream</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="product-price">Price ($)</label>
                    <input type="number" id="product-price" class="form-control" placeholder="Enter price" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="product-stock">Stock Quantity</label>
                    <input type="number" id="product-stock" class="form-control" placeholder="Enter stock quantity" min="0" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="product-description">Description</label>
                    <textarea id="product-description" class="form-control" placeholder="Enter product description" required></textarea>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Product Images</label>
                    <div class="image-upload-container">
                        <input type="file" id="product-images" accept="image/*" multiple class="image-input">
                        <div class="dropzone" id="image-dropzone">
                            <p>Drag & drop your images here, or click to select files</p>
                        </div>
                        <div id="image-preview-container" class="image-previews"></div>
                    </div>
                </div>
            </div>
            <div class="form-row">
    <div class="form-group">
        <label>Product Tag</label>
        <div class="radio-group">
            <div class="radio-item">
                <input type="radio" id="tag-none" name="product-tag" value="none" checked>
                <label for="tag-none">None</label>
            </div>
            <div class="radio-item">
                <input type="radio" id="tag-featured" name="product-tag" value="featured">
                <label for="tag-featured">Featured</label>
            </div>
            <div class="radio-item">
                <input type="radio" id="tag-new" name="product-tag" value="new">
                <label for="tag-new">New</label>
            </div>
            <div class="radio-item">
                <input type="radio" id="tag-bestseller" name="product-tag" value="bestseller">
                <label for="tag-bestseller">Bestseller</label>
            </div>
            <div class="radio-item">
                <input type="radio" id="tag-limited" name="product-tag" value="limited">
                <label for="tag-limited">Limited</label>
            </div>
            <div class="radio-item">
                <input type="radio" id="tag-popular" name="product-tag" value="popular">
                <label for="tag-popular">Popular</label>
            </div>
            <div class="radio-item">
                <input type="radio" id="tag-onsale" name="product-tag" value="onsale">
                <label for="tag-onsale">On Sale</label>
            </div>
        </div>
    </div>
</div>

            <div class="form-row" id="sale-price-container" style="display: none;">
                <div class="form-group">
                    <label for="product-sale-price">Sale Price ($)</label>
                    <input type="number" id="product-sale-price" class="form-control" placeholder="Enter sale price" min="0" step="0.01">
                </div>
            </div>
            <div class="submit-section">
                <button type="button" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-submit">Add Product</button>
            </div>
        </form>
    </div>
</section>

<!-- Categories Management Section -->
<section id="categories" style="display: none;">
    <h3>Categories Management</h3>
    <div class="status-message success" style="display: none;">Category updated successfully!</div>
    <div class="status-message error" style="display: none;">Error updating category. Please try again.</div>
    
    <div class="search-filter-bar">
        <input type="text" id="category-search" placeholder="Search categories..." class="search-input">
        <select id="category-filter" class="filter-select">
            <option value="all">All Categories</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <button type="button" id="add-category-btn" class="btn btn-primary">Add New Category</button>
    </div>
    
    <div class="table-responsive">
        <table class="data-table" id="categories-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Table will be populated via JavaScript -->
            </tbody>
        </table>
    </div>
    
    <div class="pagination" id="categories-pagination">
        <button class="pagination-btn" data-page="prev">← Previous</button>
        <div class="page-numbers">
            <span class="current-page">1</span> of <span class="total-pages">1</span>
        </div>
        <button class="pagination-btn" data-page="next">Next →</button>
    </div>
</section>

<!-- Category Edit Modal -->
<div id="category-edit-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">×</span>
        <h3>Edit Category</h3>
        <form id="edit-category-form">
            <input type="hidden" id="edit-category-id">
            
            <div class="form-row">
                <label for="edit-category-name">Category Name</label>
                <input type="text" id="edit-category-name" placeholder="Enter category name" required>
                <div class="error-message" id="edit-name-error"></div>
            </div>
            
            <div class="form-row">
                <label for="edit-category-desc">Description</label>
                <textarea id="edit-category-desc" placeholder="Enter category description" rows="3"></textarea>
                <div class="error-message" id="edit-desc-error"></div>
            </div>
            
            <div class="form-row">
                <div class="form-toggle">
                    <label for="edit-category-status">Status</label>
                    <label class="switch">
                        <input type="checkbox" id="edit-category-status">
                        <span class="slider round"></span>
                    </label>
                    <span class="toggle-label" id="edit-status-label">Active</span>
                </div>
            </div>
            
            <div class="form-row form-buttons">
                <button type="button" class="btn btn-outline close-modal-btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

            <!-- Add Category Section -->
            <section id="add-category" style="display: none;">
                <h3>Add New Category</h3>
                <div class="status-message success" style="display: none;">Category added successfully!</div>
                <div class="status-message error" style="display: none;">Error adding category. Please try again.</div>
                
                <form id="add-category-form">
                    <div class="form-row">
                        <label for="category-name">Category Name</label>
                        <input type="text" id="category-name" placeholder="Enter category name" required>
                        <div class="error-message" id="name-error"></div>
                    </div>
                    
                    <div class="form-row">
                        <label for="category-desc">Description</label>
                        <textarea id="category-desc" placeholder="Enter category description" rows="3"></textarea>
                        <div class="error-message" id="desc-error"></div>
                    </div>
                    
                    <div class="form-row">
                        <label for="category-parent">Parent Category (Optional)</label>
                        <select id="category-parent">
                            <option value="">None (Top Level Category)</option>
                            <!-- Options will be populated via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-toggle">
                            <label for="category-status">Status</label>
                            <label class="switch">
                                <input type="checkbox" id="category-status" checked>
                                <span class="slider round"></span>
                            </label>
                            <span class="toggle-label" id="category-status-label">Active</span>
                        </div>
                    </div>
                    
                    <div class="form-row form-buttons">
                        <button type="reset" class="btn btn-outline">Reset</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </section>
            
            <!-- Customers Section -->
            <section id="customers-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Customer Management</h3>
                        <div class="search-container">
                            <span class="search-icon">🔍</span>
                            <input type="text" class="search-input" placeholder="Search customers...">
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Join Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Jane Smith</td>
                                <td>jane.smith@example.com</td>
                                <td>(555) 123-4567</td>
                                <td>8</td>
                                <td>$245.87</td>
                                <td>01/15/2025</td>
                                <td><span class="status status-active">Active</span></td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon">👁️</div>
                                        <div class="action-icon edit-icon">✏️</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Mike Johnson</td>
                                <td>mike.j@example.com</td>
                                <td>(555) 987-6543</td>
                                <td>3</td>
                                <td>$97.25</td>
                                <td>02/28/2025</td>
                                <td><span class="status status-active">Active</span></td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon">👁️</div>
                                        <div class="action-icon edit-icon">✏️</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Sarah Williams</td>
                                <td>sarah.w@example.com</td>
                                <td>(555) 456-7890</td>
                                <td>12</td>
                                <td>$378.50</td>
                                <td>12/10/2024</td>
                                <td><span class="status status-active">Active</span></td>
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
            
            <!-- Orders Section -->
            <section id="orders-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Order Management</h3>
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
                                <th>Payment</th>
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
                                <td>Credit Card</td>
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
                                <td>PayPal</td>
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
                                <td>Credit Card</td>
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

            <!-- Reviews Section -->
            <section id="reviews-section">
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-icon">⭐</div>
                        <h3 class="card-title">Total Reviews</h3>
                        <div class="card-value">1,245</div>
                        <div class="card-stat positive">↑ 9.3% from last month</div>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Recent Reviews</h3>
                        <div class="search-container">
                            <span class="search-icon">🔍</span>
                            <input type="text" class="search-input" placeholder="Search reviews...">
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-review-id="1">
                                <td>
                                    <div class="customer-info">
                                        <div>Emma Watson</div>
                                    </div>
                                </td>
                                <td class="message-text">Good flavor but they arrived broken. Packaging could be improved.</td>
                                <td>03/09/2025</td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon" title="View Details" onclick="viewReviewDetails(1)">👁️</div>
                                        <div class="action-icon reply-icon" title="Reply" onclick="replyToReview(1)">💬</div>
                                        <div class="action-icon feature-icon featured" title="Remove from Homepage" 
                                            onclick="toggleFeatureReview(1, this)" data-featured="true">⭐</div>
                                    </div>
                                </td>
                            </tr>
                            <tr data-review-id="2">
                                <td>
                                    <div class="customer-info">
                                        <div>David Thompson</div>
                                    </div>
                                </td>
                                <td class="message-text">Can I pay on delivery?</td>
                                <td>03/08/2025</td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon" title="View Details" onclick="viewReviewDetails(2)">👁️</div>
                                        <div class="action-icon reply-icon" title="Reply" onclick="replyToReview(2)">💬</div>
                                        <div class="action-icon feature-icon" title="Feature on Homepage" 
                                            onclick="toggleFeatureReview(2, this)" data-featured="false">☆</div>
                                    </div>
                                </td>
                            </tr>
                            <tr data-review-id="3">
                                <td>
                                    <div class="customer-info">
                                        <div>Sophia Rodriguez</div>
                                    </div>
                                </td>
                                <td class="message-text">My order hasn't arrived yet!</td>
                                <td>03/07/2025</td>
                                <td>
                                    <div class="action-icons">
                                        <div class="action-icon view-icon" title="View Details" onclick="viewReviewDetails(3)">👁️</div>
                                        <div class="action-icon reply-icon" title="Reply" onclick="replyToReview(3)">💬</div>
                                        <div class="action-icon feature-icon" title="Feature on Homepage" 
                                            onclick="toggleFeatureReview(3, this)" data-featured="false">☆</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Top Banner Section -->
            <section id="top-banner">
                <h3>Top Banner Management</h3>
                <div class="status-message success" style="display: none;">Changes saved successfully!</div>
                <div class="status-message error" style="display: none;">Error saving changes. Please try again.</div>
                
                <form id="banner-form">
                    <div class="form-row">
                        <div class="form-toggle">
                            <label for="banner-status">Banner Status</label>
                            <label class="switch">
                                <input type="checkbox" id="banner-status">
                                <span class="slider round"></span>
                            </label>
                            <span class="toggle-label" id="banner-status-label">Disabled</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label for="banner-text">Top Banner Text</label>
                        <input type="text" id="banner-text" placeholder="Enter banner text (e.g., Free for orders above $50)" required>
                        <div class="error-message" id="banner-text-error"></div>
                    </div>
                    
                    <div class="form-row">
                        <label for="discount-code">Discount Code</label>
                        <input type="text" id="discount-code" placeholder="Enter discount code">
                    </div>
                    
                    <div class="form-row">
                        <label for="banner-text-color">Text Color</label>
                        <div class="color-picker-container">
                            <input type="color" id="banner-text-color" value="#ffffff">
                            <input type="text" id="banner-text-color-hex" value="#ffffff" maxlength="7">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label for="banner-bg-color">Background Color</label>
                        <div class="color-picker-container">
                            <input type="color" id="banner-bg-color" value="#000000">
                            <input type="text" id="banner-bg-color-hex" value="#000000" maxlength="7">
                        </div>
                    </div>
                    
                    <div class="banner-preview">
                        <label>Banner Preview</label>
                        <div id="banner-preview-box">Free shipping on orders over $50! Use code: SHIP50</div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn-primary">Save Banner</button>
                        <button type="button" class="btn-secondary" id="banner-reset">Reset</button>
                    </div>
                </form>
                
                <div class="history-log">
                    <h4>Change History</h4>
                    <div class="log-container">
                        <div class="log-item">
                            <span class="log-date">2025-04-03 10:23</span>
                            <span class="log-action">Banner enabled - "Spring Sale! 20% off with code SPRING20"</span>
                        </div>
                        <div class="log-item">
                            <span class="log-date">2025-04-01 14:45</span>
                            <span class="log-action">Banner disabled</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Delivery Rules Section -->
            <section id="delivery">
                <h3>Delivery Rules</h3>
                <div class="status-message success" style="display: none;">Delivery rules updated successfully!</div>
                <div class="status-message error" style="display: none;">Error updating delivery rules. Please try again.</div>
                
                <form id="delivery-form">
                    <div class="form-row">
                        <label for="min-order-amount">Minimum Order for Free Delivery ($)</label>
                        <input type="number" id="min-order-amount" placeholder="Enter amount for free delivery (e.g., 50)" required min="0" step="0.01">
                        <div class="error-message" id="min-order-error"></div>
                    </div>
                    
                    <div class="form-row">
                        <label for="delivery-fee">Standard Delivery Fee ($)</label>
                        <input type="number" id="delivery-fee" placeholder="Enter delivery fee" required min="0" max="50" step="0.01">
                        <div class="error-message" id="delivery-fee-error"></div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Bulk Order Shipping Discounts</h4>
                        <div class="form-row">
                            <label for="bulk-threshold-1">Order Value Threshold 1 ($)</label>
                            <input type="number" id="bulk-threshold-1" placeholder="e.g., 100" min="0" step="0.01">
                            <label for="bulk-discount-1">Shipping Discount (%)</label>
                            <input type="number" id="bulk-discount-1" placeholder="e.g., 50" min="0" max="100">
                        </div>
                        
                        <div class="form-row">
                            <label for="bulk-threshold-2">Order Value Threshold 2 ($)</label>
                            <input type="number" id="bulk-threshold-2" placeholder="e.g., 200" min="0" step="0.01">
                            <label for="bulk-discount-2">Shipping Discount (%)</label>
                            <input type="number" id="bulk-discount-2" placeholder="e.g., 100" min="0" max="100">
                        </div>
                        
                        <div class="info-box">
                            <p>Example: 50% off shipping for orders over $100, free shipping for orders over $200</p>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn-primary">Save Rules</button>
                        <button type="button" class="btn-secondary" id="delivery-reset">Reset</button>
                    </div>
                </form>
                
                <div class="history-log">
                    <h4>Change History</h4>
                    <div class="log-container">
                        <div class="log-item">
                            <span class="log-date">2025-04-02 09:15</span>
                            <span class="log-action">Updated free shipping threshold to $75</span>
                        </div>
                        <div class="log-item">
                            <span class="log-date">2025-03-28 16:30</span>
                            <span class="log-action">Added bulk discount: 50% off shipping for orders over $100</span>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
    
    <!-- JavaScript for functionality -->
    <script src="../js/script.js">
       
    </script>
</body>
</html>