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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    let currentPage = 1;
    let totalPages = 1;
    const itemsPerPage = 10;
    let searchTerm = '';
    let statusFilter = 'all';
    
    // Initial load
    loadCategories();
    
    // Event listeners
    document.getElementById('category-search').addEventListener('input', function() {
        searchTerm = this.value.trim();
        currentPage = 1;
        loadCategories();
    });
    
    document.getElementById('category-filter').addEventListener('change', function() {
        statusFilter = this.value;
        currentPage = 1;
        loadCategories();
    });
    
    document.getElementById('add-category-btn').addEventListener('click', function() {
        window.location.href = 'index.php?page=add-category';
    });
    
    // Pagination
    document.querySelectorAll('.pagination-btn').forEach(button => {
        button.addEventListener('click', function() {
            const direction = this.getAttribute('data-page');
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
                loadCategories();
            } else if (direction === 'next' && currentPage < totalPages) {
                currentPage++;
                loadCategories();
            }
        });
    });
    
    // Modal handling
    const modal = document.getElementById('category-edit-modal');
    
    document.querySelectorAll('.close-modal, .close-modal-btn').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    });
    
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Status toggle update
    document.getElementById('edit-category-status').addEventListener('change', function() {
        document.getElementById('edit-status-label').textContent = this.checked ? 'Active' : 'Inactive';
    });
    
    // Edit form submission
    document.getElementById('edit-category-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous error messages
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
        
        // Hide status messages
        document.querySelectorAll('.status-message').forEach(el => el.style.display = 'none');
        
        // Get form data
        const categoryId = document.getElementById('edit-category-id').value;
        const formData = new FormData();
        formData.append('id', categoryId);
        formData.append('name', document.getElementById('edit-category-name').value);
        formData.append('description', document.getElementById('edit-category-desc').value);
        formData.append('status', document.getElementById('edit-category-status').checked ? 1 : 0);
        formData.append('action', 'update');
        
        // Send AJAX request
        fetch('/sweet_treats/admin/ajax/manage_categories.php', {
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
                document.querySelector('.status-message.success').textContent = data.message;
                document.querySelector('.status-message.success').style.display = 'block';
                
                // Reload categories
                loadCategories();
                
                // Hide success message after 3 seconds
                setTimeout(() => {
                    document.querySelector('.status-message.success').style.display = 'none';
                }, 3000);
            } else {
                // Show error message
                document.querySelector('.status-message.error').textContent = data.message;
                document.querySelector('.status-message.error').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error details:', error);
            document.querySelector('.status-message.error').textContent = 'An unexpected error occurred. Please try again.';
            document.querySelector('.status-message.error').style.display = 'block';
        });
    });
    
    // Load categories function
    function loadCategories() {
        const tableBody = document.querySelector('#categories-table tbody');
        tableBody.innerHTML = '<tr><td colspan="6" class="loading">Loading categories...</td></tr>';
        
        const url = new URL('/sweet_treats/admin/ajax/manage_categories.php', window.location.origin);
        url.searchParams.append('action', 'get');
        url.searchParams.append('page', currentPage);
        url.searchParams.append('limit', itemsPerPage);
        url.searchParams.append('search', searchTerm);
        url.searchParams.append('status', statusFilter);
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update pagination
                currentPage = parseInt(data.current_page);
                totalPages = parseInt(data.total_pages);
                
                document.querySelector('.current-page').textContent = currentPage;
                document.querySelector('.total-pages').textContent = totalPages;
                
                // Enable/disable pagination buttons
                document.querySelector('[data-page="prev"]').disabled = currentPage <= 1;
                document.querySelector('[data-page="next"]').disabled = currentPage >= totalPages;
                
                // Populate table
                tableBody.innerHTML = '';
                
                if (data.categories.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="no-data">No categories found</td></tr>';
                    return;
                }
                
                data.categories.forEach(category => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${category.id}</td>
                        <td>${category.name}</td>
                        <td>${category.description || '-'}</td>
                        <td>${category.product_count || 0}</td>
                        <td>
                            <span class="status-badge ${category.status ? 'active' : 'inactive'}">
                                ${category.status ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-icon edit-category" data-id="${category.id}" title="Edit">✏️</button>
                            <button class="btn-icon delete-category" data-id="${category.id}" title="Delete">🗑️</button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
                
                // Add event listeners to edit buttons
                document.querySelectorAll('.edit-category').forEach(button => {
                    button.addEventListener('click', function() {
                        const categoryId = this.getAttribute('data-id');
                        openEditModal(categoryId);
                    });
                });
                
                // Add event listeners to delete buttons
                document.querySelectorAll('.delete-category').forEach(button => {
                    button.addEventListener('click', function() {
                        const categoryId = this.getAttribute('data-id');
                        deleteCategory(categoryId);
                    });
                });
            } else {
                tableBody.innerHTML = `<tr><td colspan="6" class="error">${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            tableBody.innerHTML = '<tr><td colspan="6" class="error">Error loading categories. Please try again.</td></tr>';
        });
    }
    
    // Open edit modal
    function openEditModal(categoryId) {
        // Clear previous form data
        document.getElementById('edit-category-form').reset();
        
        // Show loading state
        modal.style.display = 'block';
        document.querySelector('.modal-content h3').textContent = 'Loading category...';
        document.getElementById('edit-category-form').style.display = 'none';
        
        // Fetch category data
        fetch(`/sweet_treats/admin/ajax/manage_categories.php?action=get_single&id=${categoryId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update form title
                document.querySelector('.modal-content h3').textContent = 'Edit Category';
                document.getElementById('edit-category-form').style.display = 'block';
                
                // Populate form fields
                document.getElementById('edit-category-id').value = data.category.id;
                document.getElementById('edit-category-name').value = data.category.name;
                document.getElementById('edit-category-desc').value = data.category.description || '';
                
                const statusCheckbox = document.getElementById('edit-category-status');
                statusCheckbox.checked = data.category.status == 1;
                document.getElementById('edit-status-label').textContent = statusCheckbox.checked ? 'Active' : 'Inactive';
            } else {
                modal.style.display = 'none';
                document.querySelector('.status-message.error').textContent = data.message;
                document.querySelector('.status-message.error').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error fetching category:', error);
            modal.style.display = 'none';
            document.querySelector('.status-message.error').textContent = 'Error loading category details. Please try again.';
            document.querySelector('.status-message.error').style.display = 'block';
        });
    }
    
    // Delete category
    function deleteCategory(categoryId) {
        if (!confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('id', categoryId);
        formData.append('action', 'delete');
        
        fetch('/sweet_treats/admin/ajax/manage_categories.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.querySelector('.status-message.success').textContent = data.message;
                document.querySelector('.status-message.success').style.display = 'block';
                
                // Reload categories
                loadCategories();
                
                // Hide success message after 3 seconds
                setTimeout(() => {
                    document.querySelector('.status-message.success').style.display = 'none';
                }, 3000);
            } else {
                document.querySelector('.status-message.error').textContent = data.message;
                document.querySelector('.status-message.error').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error deleting category:', error);
            document.querySelector('.status-message.error').textContent = 'Error deleting category. Please try again.';
            document.querySelector('.status-message.error').style.display = 'block';
        });
    }
});
</script>