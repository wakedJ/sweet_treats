
<?php
// Make sure there's no whitespace or output before this point

require_once './includes/check_admin.php';
// Rest of your admin page code


// Store messages in session instead of using redirects
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [];
}

// Include database connection
require_once "../includes/db.php";

// Check if we're editing an existing product
$edit_mode = isset($_GET['id']);
$product_id = $edit_mode ? intval($_GET['id']) : 0;

// Flag to track if form was submitted successfully
$form_success = false;
$form_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category']; // Assuming category is the category_id
    $tag = $_POST['product-tag'];
    $sale_price = isset($_POST['sale-price']) && !empty($_POST['sale-price']) ? $_POST['sale-price'] : null;
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['product-images']) && $_FILES['product-images']['error'] === 0) {
        $target_dir = "uploads/products/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $temp_name = $_FILES["product-images"]["tmp_name"];
        $image_name = basename($_FILES["product-images"]["name"]);
        $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        
        // Generate unique filename
        $new_image_name = uniqid() . '.' . $image_ext;
        $target_file = $target_dir . $new_image_name;
        
        // Check if file is an actual image
        $check = getimagesize($temp_name);
        if ($check !== false) {
            // Upload file
            if (move_uploaded_file($temp_name, $target_file)) {
                $image = $new_image_name;
            }
        }
    }
    
    // Prepare database query
    if ($edit_mode) {
        // Update existing product
        $query = "UPDATE products SET 
                  name = ?, 
                  description = ?, 
                  price = ?, 
                  stock = ?, 
                  category_id = ?, 
                  tag = ?";
        
        $params = [$name, $description, $price, $stock, $category_id, $tag];
        $types = "ssdiis"; // string, string, double, integer, integer, string
        
        // Add sale price if provided
        if ($sale_price !== null) {
            $query .= ", sale_price = ?";
            $params[] = $sale_price;
            $types .= "d"; // double
        }
        
        // Add image if uploaded
        if (!empty($image)) {
            $query .= ", image = ?";
            $params[] = $image;
            $types .= "s"; // string
        }
        
        $query .= " WHERE id = ?";
        $params[] = $product_id;
        $types .= "i"; // integer
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        
        if ($result) {
            $_SESSION['messages'][] = ['type' => 'success', 'text' => 'Product updated successfully!'];
            $form_success = true;
            $form_message = 'Product updated successfully!';
        } else {
            $_SESSION['messages'][] = ['type' => 'error', 'text' => 'Error updating product: ' . $conn->error];
        }
    } else {
        // Insert new product
        $query = "INSERT INTO products (name, description, price, stock, category_id, tag";
        
        // Add sale_price and image to the query if they exist
        if ($sale_price !== null) {
            $query .= ", sale_price";
        }
        
        if (!empty($image)) {
            $query .= ", image";
        }
        
        $query .= ") VALUES (?, ?, ?, ?, ?, ?";
        
        $params = [$name, $description, $price, $stock, $category_id, $tag];
        $types = "ssdiis"; // string, string, double, integer, integer, string
        
        if ($sale_price !== null) {
            $query .= ", ?";
            $params[] = $sale_price;
            $types .= "d"; // double
        }
        
        if (!empty($image)) {
            $query .= ", ?";
            $params[] = $image;
            $types .= "s"; // string
        }
        
        $query .= ")";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        
        if ($result) {
            // Get the ID of the newly inserted product
            $new_product_id = $conn->insert_id;
            $_SESSION['messages'][] = ['type' => 'success', 'text' => 'Product added successfully!'];
            $form_success = true;
            $form_message = 'Product added successfully!';
        } else {
            $_SESSION['messages'][] = ['type' => 'error', 'text' => 'Error adding product: ' . $conn->error];
        }
    }
    
    // Use JavaScript redirect instead of header() if the form is successful
    if ($form_success) {
        // We'll handle the redirect with JavaScript at the end of the page
    }
}

// Fetch product data for editing
$product = null;
if ($edit_mode) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    // If product not found, store error message
    if (!$product) {
        $_SESSION['messages'][] = ['type' => 'error', 'text' => 'Product not found!'];
    }
}

// Fetch categories for dropdown
$categories = [];
$categories_query = $conn->query("SELECT id, name FROM categories ORDER BY name");
if ($categories_query) {
    while ($category = $categories_query->fetch_assoc()) {
        $categories[] = $category;
    }
}
?>

    <div class="form-section">
        <div class="form-header">
            <h3 class="form-title"><?php echo $edit_mode ? 'Edit Product' : 'Add New Product'; ?></h3>
        </div>
        
        <?php if (!empty($form_message)): ?>
        <div class="alert <?php echo $form_success ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo $form_message; ?>
        </div>
        <?php endif; ?>
        
        <form id="product-form" class="form" method="post" enctype="multipart/form-data">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="product-name">Product Name</label>
                    <input type="text" id="product-name" name="name" class="form-control" 
                        value="<?php echo $edit_mode ? htmlspecialchars($product['name']) : ''; ?>" placeholder="Enter product name" required>
                </div>
                
                <div class="form-group">
                    <label for="product-category">Category</label>
                    <select id="product-category" name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($edit_mode && $product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="product-price">Price ($)</label>
                    <input type="number" id="product-price" name="price" class="form-control" step="0.01" min="0" 
                        value="<?php echo $edit_mode ? htmlspecialchars($product['price']) : ''; ?>" placeholder="Enter price" required>
                </div>
                
                <div class="form-group">
                <label for="product-stock">Stock Quantity</label>
                <input type="number" id="product-stock" name="stock" class="form-control" min="0" 
                       value="<?php echo $edit_mode ? htmlspecialchars($product['stock']) : ''; ?>" placeholder="Enter stock quantity" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="product-description">Description</label>
            <textarea id="product-description" name="description" placeholder="Enter product description" class="form-control" rows="4"><?php echo $edit_mode ? htmlspecialchars($product['description']) : ''; ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Product Images</label>
                <div class="image-upload-container">
                    <input type="file" id="product-images" name="product-images" accept="image/*" class="image-input">
                    <div class="dropzone" id="image-dropzone">
                        <p>Drag & drop your image here, or click to select file</p>
                    </div>
                    <?php if ($edit_mode && !empty($product['image'])): ?>
                        <div class="current-image">
                            <p>Current image:</p>
                            <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" style="max-width: 200px;">
                        </div>
                    <?php endif; ?>
                    <div id="image-preview-container" class="image-previews"></div>
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Product Tag</label>
                <div class="radio-group">
                    <div class="radio-item">
                        <input type="radio" id="tag-none" name="product-tag" value="none" <?php echo ($edit_mode && $product['tag'] === 'none') ? 'checked' : ''; ?> checked>
                        <label for="tag-none">None</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" id="tag-hot" name="product-tag" value="hot" <?php echo ($edit_mode && $product['tag'] === 'hot') ? 'checked' : ''; ?>>
                        <label for="tag-hot">Hot</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" id="tag-new" name="product-tag" value="new" <?php echo ($edit_mode && $product['tag'] === 'new') ? 'checked' : ''; ?>>
                        <label for="tag-new">New</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" id="tag-bestseller" name="product-tag" value="best seller" <?php echo ($edit_mode && $product['tag'] === 'best seller') ? 'checked' : ''; ?>>
                        <label for="tag-bestseller">Best Seller</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" id="tag-limited" name="product-tag" value="limited" <?php echo ($edit_mode && $product['tag'] === 'limited') ? 'checked' : ''; ?>>
                        <label for="tag-limited">Limited</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" id="tag-popular" name="product-tag" value="popular" <?php echo ($edit_mode && $product['tag'] === 'popular') ? 'checked' : ''; ?>>
                        <label for="tag-popular">Popular</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" id="tag-onsale" name="product-tag" value="on sale" <?php echo ($edit_mode && $product['tag'] === 'on sale') ? 'checked' : ''; ?>>
                        <label for="tag-onsale">On Sale</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-row" id="sale-price-container" style="display: <?php echo ($edit_mode && $product['tag'] === 'on sale') ? 'block' : 'none'; ?>">
            <div class="form-group">
                <label for="product-sale-price">Sale Price ($)</label>
                <input type="number" id="product-sale-price" name="sale-price" class="form-control" placeholder="Enter sale price" min="0" step="0.01" 
                       value="<?php echo ($edit_mode && isset($product['sale_price'])) ? htmlspecialchars($product['sale_price']) : ''; ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?page=products'">Cancel</button>
            <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Update Product' : 'Add Product'; ?></button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image preview functionality
    const imageInput = document.getElementById('product-images');
    const imagePreview = document.getElementById('image-preview-container');
    const dropzone = document.getElementById('image-dropzone');
    
    // Handle file selection
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            handleFilePreview(this.files);
        });
    }
    
    // Handle drag and drop
    if (dropzone) {
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
        
        dropzone.addEventListener('dragleave', function() {
            dropzone.classList.remove('dragover');
        });
        
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            handleFilePreview(e.dataTransfer.files);
        });
        
        // Click to select files
        dropzone.addEventListener('click', function() {
            imageInput.click();
        });
    }
    
    function handleFilePreview(files) {
        imagePreview.innerHTML = '';
        
        if (files && files[0]) {
            const file = files[0]; // Taking only the first file since we don't handle multiple
            
            // Check if the file is an image
            if (!file.type.match('image.*')) {
                alert('Please select an image file');
                return;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('preview-img');
                imagePreview.appendChild(img);
            }
            
            reader.readAsDataURL(file);
        }
    }
    
    // Show/hide sale price field based on the selected tag
    const salePriceContainer = document.getElementById('sale-price-container');
    const tagOptions = document.querySelectorAll('input[name="product-tag"]');
    
    tagOptions.forEach(option => {
        option.addEventListener('change', function() {
            if (this.value === 'on sale') {
                salePriceContainer.style.display = 'block';
            } else {
                salePriceContainer.style.display = 'none';
            }
        });
    });
    
    <?php if ($form_success): ?>
    // JavaScript redirect after form submission
    setTimeout(function() {
        window.location.href = 'index.php?page=products';
    }, 1500); // Redirect after 1.5 seconds so user can see success message
    <?php endif; ?>
});
</script>