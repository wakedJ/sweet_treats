<div id="content-wrapper">
    <div class="container-fluid">
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
                <button type="button" class="btn btn-outline" id="cancel-add">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load parent categories when page loads
    loadParentCategories();
    
    // Add form submission handler
    document.getElementById('add-category-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous error messages
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
        
        // Hide status messages
        document.querySelectorAll('.status-message').forEach(el => el.style.display = 'none');
        
        // Get form data
        const formData = new FormData();
        formData.append('name', document.getElementById('category-name').value);
        formData.append('description', document.getElementById('category-desc').value);
        formData.append('parent_category_id', document.getElementById('category-parent').value);
        formData.append('status', document.getElementById('category-status').checked ? 1 : 0);
        
        // Use the dedicated Ajax endpoint
        fetch('/sweet_treats/admin/ajax/add-category.php', {
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
                // Show success message
                document.querySelector('.status-message.success').style.display = 'block';
                
                // Reset form
                document.getElementById('add-category-form').reset();
                document.getElementById('category-status-label').textContent = 'Active';
                
                // Reload parent categories dropdown
                loadParentCategories();
                
                // Reload categories table if applicable
                if (typeof loadCategories === 'function') {
                    loadCategories();
                }
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

    // Handle status toggle label update
    document.getElementById('category-status').addEventListener('change', function() {
        document.getElementById('category-status-label').textContent = this.checked ? 'Active' : 'Inactive';
    });

    // Cancel button handler
    document.getElementById('cancel-add').addEventListener('click', function() {
        document.getElementById('add-category-form').reset();
        document.getElementById('category-status-label').textContent = 'Active';
        // Additional code to close modal or navigate back if needed
    });
});

// Load parent categories for dropdown
function loadParentCategories() {
    fetch('/sweet_treats/admin/ajax/get_categories.php', {
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
            const parentSelect = document.getElementById('category-parent');
            // Keep the first "None" option
            parentSelect.innerHTML = '<option value="">None (Top Level Category)</option>';
            
            data.categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                parentSelect.appendChild(option);
            });
        } else {
            console.error('Error loading categories:', data.message);
        }
    })
    .catch(error => {
        console.error('Error loading parent categories:', error);
    });
}
</script>