<?php
// Ensure database connection is available
require_once './includes/check_admin.php';
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
            <select class="filter-select" id="stock-filter">
                <option value="">All Stock Status</option>
                <option value="in-stock">In Stock</option>
                <option value="low-stock">Low in Stock</option>
                <option value="out-of-stock">Out of Stock</option>
            </select>
        </div>
        <div class="action-buttons">
            <button class="action-btn" onclick="window.location.href='index.php?page=add-product'">➕ Add Product</button>
        </div>
        <!-- Modal for editing products -->
        <div id="product-edit-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">×</span>
                <h3>Edit Product</h3>
                <form id="edit-product-form" enctype="multipart/form-data">
                    <input type="hidden" id="edit-product-id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-product-name">Product Name</label>
                            <input type="text" id="edit-product-name" name="name" class="form-control" placeholder="Enter product name" required>
                            <div class="error-message" id="edit-name-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-product-category">Category</label>
                            <select id="edit-product-category" name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <!-- Will be populated via JavaScript -->
                            </select>
                            <div class="error-message" id="edit-category-error"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-product-price">Price ($)</label>
                            <input type="number" id="edit-product-price" name="price" class="form-control" step="0.01" min="0" placeholder="Enter price" required>
                            <div class="error-message" id="edit-price-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-product-stock">Stock Quantity</label>
                            <input type="number" id="edit-product-stock" name="stock" class="form-control" min="0" placeholder="Enter stock quantity" required>
                            <div class="error-message" id="edit-stock-error"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-product-description">Description</label>
                        <textarea id="edit-product-description" name="description" placeholder="Enter product description" class="form-control" rows="4"></textarea>
                        <div class="error-message" id="edit-description-error"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Image</label>
                            <div class="image-upload-container">
                                <input type="file" id="edit-product-image" name="image" accept="image/*" class="image-input">
                                <div class="dropzone" id="edit-image-dropzone">
                                    <p>Drag & drop your image here, or click to select file</p>
                                </div>
                                <div class="current-image" id="current-product-image">
                                    <!-- Current image will be displayed here -->
                                </div>
                                <div id="edit-image-preview-container" class="image-previews"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Tag</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" id="edit-tag-none" name="tag" value="none" checked>
                                    <label for="edit-tag-none">None</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="edit-tag-hot" name="tag" value="hot">
                                    <label for="edit-tag-hot">Hot</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="edit-tag-new" name="tag" value="new">
                                    <label for="edit-tag-new">New</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="edit-tag-bestseller" name="tag" value="best seller">
                                    <label for="edit-tag-bestseller">Best Seller</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="edit-tag-limited" name="tag" value="limited">
                                    <label for="edit-tag-limited">Limited</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="edit-tag-popular" name="tag" value="popular">
                                    <label for="edit-tag-popular">Popular</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="edit-tag-onsale" name="tag" value="on sale">
                                    <label for="edit-tag-onsale">On Sale</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row" id="edit-sale-price-container" style="display: none;">
                        <div class="form-group">
                            <label for="edit-product-sale-price">Sale Price ($)</label>
                            <input type="number" id="edit-product-sale-price" name="sale_price" class="form-control" placeholder="Enter sale price" min="0" step="0.01">
                            <div class="error-message" id="edit-sale-price-error"></div>
                        </div>
                    </div>
                    
                    <div class="form-row form-buttons">
                        <button type="button" class="btn btn-outline close-modal-btn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add CSS styles for modal and form (you can move this to your stylesheet) -->
        <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }

        .status-message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.85em;
            margin-top: 5px;
        }

        .current-image img {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
        }

        /* Added styles for product images */
        .product-img {
            width: 50px;
            height: 50px; 
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
            border: 1px solid #ddd;
        }

        .product-info {
            display: flex;
            align-items: center;
        }

        /* Add style for missing image */
        .missing-img {
            width: 50px;
            height: 50px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            margin-right: 10px;
            border: 1px solid #ddd;
            color: #999;
            font-size: 10px;
        }
        </style>
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
        <?php
// In your table body section, modify the <tr> tag to include data-category-id:
?>
<tbody>
    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($product = mysqli_fetch_assoc($result)): ?>
            <tr data-category-id="<?php echo $product['category_id']; ?>">
                <td>
                    <div class="product-info">
                       <?php
                            $image_path = $product['image'];
                            echo '<img src="./uploads/products/' . htmlspecialchars($image_path) . '" alt="' . htmlspecialchars($product['name']) . '" class="product-img" onerror="this.onerror=null; this.src=\'/assets/img/placeholder.png\'; this.classList.add(\'missing-img\');">';
                        ?>
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
                            $rating = $product['average_rating'] ?? 0;
                            $stars = '';
                            for ($i = 1; $i <= 5; $i++) {
                                $stars .= ($i <= $rating) ? '★' : '☆';
                            }
                            echo $stars;
                            ?>
                        </div>
                        <span><?php echo number_format($rating, 1); ?></span>
                    </div>
                </td>
                <td>
                    <?php if ($product['stock'] <= 0): ?>
                        <span class="status status-inactive" data-stock-status="out-of-stock">Out Of Stock</span>
                    <?php elseif ($product['stock'] <= 10): ?>
                        <span class="status status-pending" data-stock-status="low-stock">Low In Stock</span>
                    <?php else: ?>
                        <span class="status status-active" data-stock-status="in-stock">In Stock</span>
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
   // Filter by category functionality - FIXED VERSION
const categoryFilter = document.getElementById('category-filter');
if (categoryFilter) {
    categoryFilter.addEventListener('change', function() {
        const selectedCategoryId = this.value;
        const rows = document.querySelectorAll('.table tbody tr[data-category-id]');
        
        if (selectedCategoryId === '') {
            // Show all rows if "All Categories" is selected
            rows.forEach(row => {
                row.style.display = '';
            });
            return;
        }
        
        rows.forEach(row => {
            const rowCategoryId = row.getAttribute('data-category-id');
            if (rowCategoryId === selectedCategoryId) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}
    // Filter by rating functionality
    // Replace the existing rating filter code with this improved version:

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
        
        // Parse rating value more reliably
        let minimumRating = 0;
        if (ratingValue.includes('5 star')) {
            minimumRating = 5;
        } else if (ratingValue.includes('4 star')) {
            minimumRating = 4;
        } else if (ratingValue.includes('3 star')) {
            minimumRating = 3;
        } else if (ratingValue.includes('2 star')) {
            minimumRating = 2;
        } else if (ratingValue.includes('1 star')) {
            minimumRating = 1;
        }
        
        rows.forEach(row => {
            const ratingSpan = row.querySelector('.rating span');
            if (ratingSpan) {
                const ratingText = ratingSpan.textContent.trim();
                const productRating = parseFloat(ratingText);
                
                // For exact star matching, check if rating falls within the star range
                // e.g., "4 Stars" shows ratings from 4.0 to 4.9
                let showRow = false;
                if (minimumRating === 5 && productRating >= 4.5) {
                    showRow = true; // 5-star ratings (4.5 and above round to 5)
                } else if (minimumRating === 4 && productRating >= 3.5 && productRating < 4.5) {
                    showRow = true; // 4-star ratings
                } else if (minimumRating === 3 && productRating >= 2.5 && productRating < 3.5) {
                    showRow = true; // 3-star ratings
                } else if (minimumRating === 2 && productRating >= 1.5 && productRating < 2.5) {
                    showRow = true; // 2-star ratings
                } else if (minimumRating === 1 && productRating >= 0.5 && productRating < 1.5) {
                    showRow = true; // 1-star ratings
                } else if (minimumRating === 0 && productRating < 0.5) {
                    showRow = true; // 0-star ratings
                }
                
                row.style.display = showRow ? '' : 'none';
            } else {
                // If no rating span found, hide the row
                row.style.display = 'none';
            }
        });
    });
}
    
    // Filter by stock status functionality
    const stockFilter = document.getElementById('stock-filter');
    if (stockFilter) {
        stockFilter.addEventListener('change', function() {
            const stockValue = this.value;
            const rows = document.querySelectorAll('.table tbody tr');
            
            if (stockValue === '') {
                // Show all rows if "All Stock Status" is selected
                rows.forEach(row => {
                    row.style.display = '';
                });
                return;
            }
            
            rows.forEach(row => {
                const statusElement = row.querySelector('.status[data-stock-status]');
                if (statusElement) {
                    const stockStatus = statusElement.getAttribute('data-stock-status');
                    if (stockStatus === stockValue) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    }
    
    // Delete product functionality
    const deleteIcons = document.querySelectorAll('.delete-icon');
    deleteIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productName = this.closest('tr').querySelector('.product-info div').textContent;
            
            if (confirm(`Are you sure you want to delete "${productName}"?`)) {
                // AJAX request to delete the product
                fetch('ajax/delete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'id=' + productId
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table
                        icon.closest('tr').remove();
                        alert('Product deleted successfully');
                    } else {
                        alert('Error deleting product: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Request failed. Please try again.');
                });
            }
        });
    });

    // Modal handling
    const modal = document.getElementById('product-edit-modal');
    
    // Close modal when clicking X or Cancel button
    document.querySelectorAll('.close-modal, .close-modal-btn').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    });
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Show/hide sale price field based on tag selection
    document.querySelectorAll('input[name="tag"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'on sale') {
                document.getElementById('edit-sale-price-container').style.display = 'block';
            } else {
                document.getElementById('edit-sale-price-container').style.display = 'none';
            }
        });
    });
    
    // Update edit icon click handlers
    const editIcons = document.querySelectorAll('.edit-icon');
    editIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            openEditModal(productId);
        });
    });
    
    // Image preview handling
    const imageInput = document.getElementById('edit-product-image');
    const imagePreviewContainer = document.getElementById('edit-image-preview-container');
    const dropzone = document.getElementById('edit-image-dropzone');
    
    // Handle file selection
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            handleFileSelect(this.files);
        });
    }
    
    // Handle drag and drop
    if (dropzone) {
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });
        
        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });
        
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
            
            const dt = e.dataTransfer;
            handleFileSelect(dt.files);
        });
        
        // Click on dropzone to select file
        dropzone.addEventListener('click', function() {
            imageInput.click();
        });
    }
    
    // Handle file selection function
    function handleFileSelect(files) {
        if (!files.length) return;
        
        imagePreviewContainer.innerHTML = '';
        
        const file = files[0]; // We only care about the first file
        
        // Check if file is an image
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file.');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.className = 'image-preview';
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Product Preview">
                <button type="button" class="remove-preview">×</button>
            `;
            imagePreviewContainer.appendChild(preview);
            
            // Remove preview when clicking X
            preview.querySelector('.remove-preview').addEventListener('click', function() {
                preview.remove();
                imageInput.value = '';
            });
        };
        
        reader.readAsDataURL(file);
    }
    
    // Edit form submission
    const editForm = document.getElementById('edit-product-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            
            // Get form data
            const productId = document.getElementById('edit-product-id').value;
            const formData = new FormData(this);
            formData.append('id', productId);
            formData.append('action', 'update');
            
            // Make sure the tag value is included correctly
            const selectedTag = document.querySelector('input[name="tag"]:checked').value;
            formData.set('tag', selectedTag);
            
            // Set sale_price to null if not "on sale"
            if (selectedTag !== 'on sale') {
                formData.set('sale_price', '');
            }
            
            // Debug: Log form data to console
            console.log('Submitting form data:');
            for (const pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            // Send AJAX request
            fetch('./ajax/manage_products.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Close modal
                    modal.style.display = 'none';
                    
                    // Show success message
                    alert(data.message || 'Product updated successfully!');
                    
                    // Reload the page to show updated data
                    window.location.reload();
                } else {
                    // Show error message with details
                    console.error('Backend error:', data);
                    if (data.errors) {
                        // Display field-specific errors
                        for (const field in data.errors) {
                            const errorElement = document.getElementById(`edit-${field}-error`);
                            if (errorElement) {
                                errorElement.textContent = data.errors[field];
                            }
                        }
                    } else {
                        // Display general error
                        alert(data.message || 'An error occurred while updating the product.');
                    }
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                alert('An unexpected error occurred. Please try again.');
            });
        });
    }
    
    // Function to open the edit modal and populate with product data
    function openEditModal(productId) {
    // Reset form
    document.getElementById('edit-product-form').reset();
    document.getElementById('edit-image-preview-container').innerHTML = '';
    document.getElementById('current-product-image').innerHTML = '';
    
    // Show loading state
    modal.style.display = 'block';
    document.querySelector('.modal-content h3').textContent = 'Loading product...';
    document.getElementById('edit-product-form').style.display = 'none';
    
    // For debugging - log the product ID
    console.log('Opening edit modal for product ID:', productId);
    
    // Use the full path relative to the web root if needed
    // For example: if your PHP file is in 'admin/ajax/manage_products.php', use that path
    fetch(`./ajax/manage_products.php?action=get_single&id=${productId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // Log the response status for debugging
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // Log the data for debugging
        console.log('Retrieved data:', data);
        if (data.success) {
            // Update form title and show form
            document.querySelector('.modal-content h3').textContent = 'Edit Product';
            document.getElementById('edit-product-form').style.display = 'block';
            
            // Populate form fields
            document.getElementById('edit-product-id').value = data.product.id;
            document.getElementById('edit-product-name').value = data.product.name;
            document.getElementById('edit-product-description').value = data.product.description || '';
            document.getElementById('edit-product-price').value = data.product.price;
            document.getElementById('edit-product-stock').value = data.product.stock;
            
            // Populate category dropdown
            populateCategoryDropdown(data.product.category_id);
            
            // Set product tag
            const tagValue = data.product.tag || 'none';
            const tagRadio = document.querySelector(`input[name="tag"][value="${tagValue}"]`);
            if (tagRadio) {
                tagRadio.checked = true;
            }
            
            // Handle sale price if applicable
            if (tagValue === 'on sale') {
                document.getElementById('edit-sale-price-container').style.display = 'block';
                document.getElementById('edit-product-sale-price').value = data.product.sale_price || '';
            } else {
                document.getElementById('edit-sale-price-container').style.display = 'none';
            }
            
            // Display current image if available
            if (data.product.image) {
                // Ensure proper path for image
                let imagePath = data.product.image;
                if (!imagePath.startsWith('/') && !imagePath.startsWith('http')) {
                    imagePath = './uploads/products/' + imagePath;
                }
                
                document.getElementById('current-product-image').innerHTML = `
                    <p>Current image:</p>
                    <img src="${imagePath}" alt="Product Image" onerror="this.onerror=null; this.src='/assets/img/placeholder.png'; this.classList.add('missing-img');">
                `;
            }
        } else {
            modal.style.display = 'none';
            alert(data.message || 'Error loading product details.');
        }
    })
    .catch(error => {
        console.error('Error fetching product:', error);
        modal.style.display = 'none';
        alert('Error loading product details. Please try again. Error: ' + error.message);
    });
}

    
    // Function to populate category dropdown
    function populateCategoryDropdown(selectedCategoryId) {
    const categorySelect = document.getElementById('edit-product-category');
    
    // Log for debugging
    console.log('Loading categories, selected ID:', selectedCategoryId);
    
    // Use the full path if needed
    fetch('./ajax/get_categories.php', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Categories data:', data);
        if (data.success) {
            // Clear existing options
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            
            // Add categories
            data.categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                option.selected = category.id == selectedCategoryId;
                categorySelect.appendChild(option);
            });
        } else {
            console.error('Error loading categories:', data.message);
        }
    })
    .catch(error => {
        console.error('Error loading categories:', error);
    });
}
});
</script>