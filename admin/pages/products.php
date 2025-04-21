<?php
// Ensure database connection is available
require_once "../includes/db.php";

// Fetch categories for filter dropdown
$categories_query = "SELECT id, name FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// Build the query
$query = "SELECT p.*, c.name as category_name 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          ORDER BY p.updated_at DESC";

// Execute query
$result = mysqli_query($conn, $query);
?>

<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Product Management</h3>
        <div class="search-container">
            <span class="search-icon">🔍</span>
            <input type="text" class="search-input" placeholder="Search products...">
        </div>
        <div class="filter-container">
            <select class="filter-select" id="rating-filter">
                <option>All Ratings</option>
                <option>5 Stars</option>
                <option>4 Stars</option>
                <option>3 Stars</option>
                <option>2 Stars</option>
                <option>1 Star</option>
            </select>
            <select class="filter-select" id="category-filter">
                <option value="">All Categories</option>
                <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                    <option value="<?php echo $category['id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="action-buttons">
            <button class="action-btn" onclick="window.location.href='index.php?page=add-product'">➕ Add Product</button>
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
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                                <div><?php echo htmlspecialchars($product['name']); ?></div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                        <td>
                            <?php if (!empty($product['sale_price'])): ?>
                                <span class="original-price">$<?php echo number_format($product['price'], 2); ?></span>
                                <span class="sale-price">$<?php echo number_format($product['sale_price'], 2); ?></span>
                            <?php else: ?>
                                $<?php echo number_format($product['price'], 2); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>
                            <div class="rating">
                                <div class="stars">
                                    <?php 
                                    $rating = $product['average_rating'];
                                    $stars = '';
                                    for ($i = 1; $i <= 5; $i++) {
                                        $stars .= ($i <= $rating) ? '★' : '☆';
                                    }
                                    echo $stars;
                                    ?>
                                </div>
                                <span><?php echo number_format($product['average_rating'], 1); ?></span>
                            </div>
                        </td>
                        <td>
                            <?php if ($product['stock'] <= 0): ?>
                                <span class="status status-inactive">Out Of Stock</span>
                            <?php elseif ($product['stock'] <= 10): ?>
                                <span class="status status-pending">Low In Stock</span>
                            <?php else: ?>
                                <span class="status status-active">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-icons">
                                <div class="action-icon edit-icon" data-id="<?php echo $product['id']; ?>">✏️</div>
                                <div class="action-icon delete-icon" data-id="<?php echo $product['id']; ?>">🗑️</div>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">No products found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Product search functionality
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('.table tbody tr');
            
            rows.forEach(row => {
                const productName = row.querySelector('.product-info div').textContent.toLowerCase();
                const category = row.querySelectorAll('td')[1].textContent.toLowerCase();
                
                if (productName.includes(searchValue) || category.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Filter by category functionality
    const categoryFilter = document.getElementById('category-filter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const categoryValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('.table tbody tr');
            
            if (categoryValue === '') {
                // Show all rows if "All Categories" is selected
                rows.forEach(row => {
                    row.style.display = '';
                });
                return;
            }
            
            rows.forEach(row => {
                const categoryId = row.querySelector('.action-icon').getAttribute('data-id');
                const categoryMatch = categoryValue === '' || categoryId === categoryValue;
                
                if (categoryMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Filter by rating functionality
    const ratingFilter = document.getElementById('rating-filter');
    if (ratingFilter) {
        ratingFilter.addEventListener('change', function() {
            const ratingValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('.table tbody tr');
            
            if (ratingValue === 'all ratings') {
                // Show all rows if "All Ratings" is selected
                rows.forEach(row => {
                    row.style.display = '';
                });
                return;
            }
            
            // Parse rating value (e.g., "4 Stars" -> 4)
            const minimumRating = parseInt(ratingValue);
            
            rows.forEach(row => {
                const ratingText = row.querySelector('.rating span').textContent;
                const productRating = parseFloat(ratingText);
                
                if (productRating >= minimumRating) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Edit product functionality
    const editIcons = document.querySelectorAll('.edit-icon');
    editIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            window.location.href = `index.php?page=edit-product&id=${productId}`;
        });
    });
    
    // Delete product functionality
    const deleteIcons = document.querySelectorAll('.delete-icon');
    deleteIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productName = this.closest('tr').querySelector('.product-info div').textContent;
            
            if (confirm(`Are you sure you want to delete "${productName}"?`)) {
                // AJAX request to delete the product
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajax/delete-product.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Remove the row from the table
                            icon.closest('tr').remove();
                            alert('Product deleted successfully');
                        } else {
                            alert('Error deleting product: ' + response.message);
                        }
                    } else {
                        alert('Request failed. Status: ' + xhr.status);
                    }
                };
                xhr.send('id=' + productId);
            }
        });
    });
});
</script>