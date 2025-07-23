 // Toggle sidebar
 const sidebar = document.getElementById('sidebar');
 const mainContent = document.getElementById('main-content');
 const toggleBtn = document.getElementById('toggle-btn');
 
 toggleBtn.addEventListener('click', () => {
     sidebar.classList.toggle('collapsed');
     mainContent.classList.toggle('expanded');
 });
 
// Navigation functionality
const navLinks = document.querySelectorAll('.nav-link');
const pageTitle = document.getElementById('page-title');
const sections = document.querySelectorAll('section');

// Set initial visibility on page load
document.addEventListener('DOMContentLoaded', function() {
// Hide all sections except dashboard
sections.forEach(section => {
 if (section.id !== 'dashboard-section') {
     section.style.display = 'none';
 }
});
});

navLinks.forEach(link => {
link.addEventListener('click', (e) => {
 e.preventDefault();
 
 // Remove active class from all links
 navLinks.forEach(item => item.classList.remove('active'));
 
 // Add active class to clicked link
 link.classList.add('active');
 
 // Update page title
 const sectionName = link.getAttribute('data-section');
 if (sectionName) {
     pageTitle.textContent = sectionName.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
     
     // Hide all sections
     sections.forEach(section => {
         section.style.display = 'none';
     });
     
     // Show selected section
    // Show selected section
const activeSection = document.getElementById(`${sectionName}-section`);
if (activeSection) {
    activeSection.style.display = 'block';
} else {
    // Try without the "-section" suffix for the new sections you added
    const alternativeSection = document.getElementById(sectionName);
    if (alternativeSection) {
        alternativeSection.style.display = 'block';
    }
}
 }
 
 // Close sidebar on mobile after selection
 if (window.innerWidth <= 768) {
     sidebar.classList.remove('expanded');
     sidebar.classList.add('collapsed');
 }
});
}); 
 // Add product button functionality
 document.getElementById('tag-onsale').addEventListener('change', function() {
    const salePriceContainer = document.getElementById('sale-price-container');
    salePriceContainer.style.display = this.checked ? 'block' : 'none';
});
 const addProductBtn = document.querySelector('.action-btn[data-section="add-product"]');
 if (addProductBtn) {
     addProductBtn.addEventListener('click', () => {
         // Update active nav link
         navLinks.forEach(item => item.classList.remove('active'));
         document.querySelector('.nav-link[data-section="add-product"]').classList.add('active');
         
         // Update page title
         pageTitle.textContent = 'Add Product';
         
         // Hide all sections
         sections.forEach(section => {
             section.style.display = 'none';
         });
         
         // Show add product section
         document.getElementById('add-product-section').style.display = 'block';
     });
 }
 
 // Cancel button functionality
 const cancelBtn = document.querySelector('.btn-cancel');
 if (cancelBtn) {
     cancelBtn.addEventListener('click', () => {
         // Go back to products page
         navLinks.forEach(item => item.classList.remove('active'));
         document.querySelector('.nav-link[data-section="products"]').classList.add('active');
         
         // Update page title
         pageTitle.textContent = 'Products';
         
         // Hide all sections
         sections.forEach(section => {
             section.style.display = 'none';
         });
         
         // Show products section
         document.getElementById('products-section').style.display = 'block';
     });
 }
 //for adding product form
 document.addEventListener('DOMContentLoaded', function() {
// Image upload functionality
const dropzone = document.getElementById('image-dropzone');
const fileInput = document.getElementById('product-images');
const previewContainer = document.getElementById('image-preview-container');

// Prevent default drag behaviors
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
 dropzone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
 e.preventDefault();
 e.stopPropagation();
}

// Highlight dropzone when item is dragged over it
['dragenter', 'dragover'].forEach(eventName => {
 dropzone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
 dropzone.addEventListener(eventName, unhighlight, false);
});

function highlight() {
 dropzone.classList.add('dragover');
}

function unhighlight() {
 dropzone.classList.remove('dragover');
}

// Handle dropped files
dropzone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
 const dt = e.dataTransfer;
 const files = dt.files;
 handleFiles(files);
}

fileInput.addEventListener('change', function() {
 handleFiles(this.files);
});

function handleFiles(files) {
 files = [...files];
 files.forEach(previewFile);
}

function previewFile(file) {
 if (!file.type.match('image.*')) return;
 
 const reader = new FileReader();
 
 reader.onload = function(e) {
     const preview = document.createElement('div');
     preview.className = 'image-preview';
     
     const img = document.createElement('img');
     img.src = e.target.result;
     
     const removeBtn = document.createElement('button');
     removeBtn.className = 'image-remove';
     removeBtn.innerHTML = '×';
     removeBtn.addEventListener('click', function() {
         preview.remove();
     });
     
     preview.appendChild(img);
     preview.appendChild(removeBtn);
     previewContainer.appendChild(preview);
 }
 
 reader.readAsDataURL(file);
}

// Form submission
const form = document.getElementById('product-form');

form.addEventListener('submit', function(e) {
 e.preventDefault();
 
 // Here you would typically collect all form data and send it to your server
 // For this example, we'll just log the data
 
 const productData = {
     name: document.getElementById('product-name').value,
     category: document.getElementById('product-category').value,
     price: document.getElementById('product-price').value,
     stock: document.getElementById('product-stock').value,
     description: document.getElementById('product-description').value,
     // Images would be handled differently in a real application
 };
 
 console.log('Product data to submit:', productData);
 alert('Product added successfully!');
 form.reset();
 previewContainer.innerHTML = '';
});
});
// Get the canvas element
var ctx = document.getElementById('salesChart').getContext('2d');

// Initialize the chart
var salesChart = new Chart(ctx, {
 type: 'line', // Chart type (line chart in this case)
 data: {
     labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], // Labels for the X-axis (days of the week)
     datasets: [{
         label: 'Sales ($)', // Label for the dataset
         data: [1200, 1500, 1800, 1300, 1700, 1600, 2000], // Weekly sales data
         borderColor: 'rgba(75, 192, 192, 1)', // Line color
         backgroundColor: 'rgba(75, 192, 192, 0.2)', // Fill color for the area under the line
         fill: true, // Whether to fill the area under the line
         tension: 0.1 // Line smoothness
     }]
 },
 options: {
     responsive: true, // Make the chart responsive
     plugins: {
         legend: {
             position: 'top' // Display the legend on top
         }
     },
     scales: {
         y: {
             beginAtZero: true // Y-axis starts from 0
         }
     }
 }
});

// Event listeners for the buttons to update the chart
// Select all buttons
const buttons = document.querySelectorAll('.chart-btn');

// Function to update chart based on selected period
function updateChart(period) {
// Change chart data based on the selected period
if (period === 'weekly') {
 salesChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
 salesChart.data.datasets[0].data = [1200, 1500, 1800, 1300, 1700, 1600, 2000]; // Weekly data
} else if (period === 'monthly') {
 salesChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
 salesChart.data.datasets[0].data = [5000, 6000, 5500, 6200, 7000, 6500]; // Monthly data
} else if (period === 'yearly') {
 salesChart.data.labels = ['2021', '2022', '2023'];
 salesChart.data.datasets[0].data = [50000, 60000, 70000]; // Yearly data
}

// Update the chart with new data
salesChart.update();
}

// Add event listeners to each button
buttons.forEach(button => {
button.addEventListener('click', function () {
 // Remove 'active' class from all buttons
 buttons.forEach(b => b.classList.remove('active'));
 
 // Add 'active' class to the clicked button
 this.classList.add('active');

 // Update the chart with the selected period
 const period = this.textContent.toLowerCase();
 updateChart(period);
});
});



// Function to update chart data based on selection
function updateChart(period) {
 // Change chart data based on the selected period
 if (period === 'weekly') {
     salesChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
     salesChart.data.datasets[0].data = [1200, 1500, 1800, 1300, 1700, 1600, 2000]; // Weekly data
 } else if (period === 'monthly') {
     salesChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
     salesChart.data.datasets[0].data = [5000, 6000, 5500, 6200, 7000, 6500]; // Monthly data
 } else if (period === 'yearly') {
     salesChart.data.labels = ['2021', '2022', '2023'];
     salesChart.data.datasets[0].data = [50000, 60000, 70000]; // Yearly data
 }

 salesChart.update(); // Update the chart with new data
}



// Filter functionality
document.querySelectorAll('.filter-select').forEach(select => {
    select.addEventListener('change', function() {
        const selectedRating = document.querySelector('.filter-select:nth-child(1)').value;
        const selectedCategory = document.querySelector('.filter-select:nth-child(2)').value;
        const rows = document.querySelectorAll('.table tbody tr');
        rows.forEach(row => {
            const rating = row.querySelector('.rating span').textContent;
            const category = row.querySelector('td:nth-child(2)').textContent;
            const matchesRating = selectedRating === 'All Ratings' || rating === selectedRating;
            const matchesCategory = selectedCategory === 'All Products' || category === selectedCategory;
            row.style.display = (matchesRating && matchesCategory) ? '' : 'none';
        });
    });
});

// Change status functionality
document.querySelectorAll('.status').forEach(statusElement => {
    statusElement.addEventListener('click', function() {
        const currentStatus = this.textContent.trim();
        const statusMap = {
            'In Stock': 'Low In Stock',
            'Low In Stock': 'Out Of Stock',
            'Out Of Stock': 'In Stock'
        };
        this.textContent = statusMap[currentStatus] || 'In Stock';
        this.className = 'status ' + getStatusClass(this.textContent);
    });
});

function getStatusClass(status) {
    switch(status) {
        case 'In Stock': return 'status-active';
        case 'Low In Stock': return 'status-pending';
        case 'Out Of Stock': return 'status-inactive';
        default: return '';
    }
}
// Top Banner Section Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a page with these sections
    const bannerForm = document.getElementById('banner-form');
    const deliveryForm = document.getElementById('delivery-form');
    
    if (bannerForm) {
        initBannerSection();
    }
    
    if (deliveryForm) {
        initDeliverySection();
    }
});

function initBannerSection() {
    // Toggle switch functionality
    const toggleSwitch = document.getElementById('banner-status');
    const toggleLabel = document.getElementById('banner-status-label');
    
    toggleSwitch.addEventListener('change', function() {
        toggleLabel.textContent = this.checked ? 'Enabled' : 'Disabled';
    });
    
    // Text color picker sync
    const textColorPicker = document.getElementById('banner-text-color');
    const textColorHex = document.getElementById('banner-text-color-hex');
    
    textColorPicker.addEventListener('input', function() {
        textColorHex.value = this.value;
        updateBannerPreview();
    });
    
    textColorHex.addEventListener('input', function() {
        if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
            textColorPicker.value = this.value;
            updateBannerPreview();
        }
    });
    
    // Background color picker sync
    const bgColorPicker = document.getElementById('banner-bg-color');
    const bgColorHex = document.getElementById('banner-bg-color-hex');
    
    bgColorPicker.addEventListener('input', function() {
        bgColorHex.value = this.value;
        updateBannerPreview();
    });
    
    bgColorHex.addEventListener('input', function() {
        if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
            bgColorPicker.value = this.value;
            updateBannerPreview();
        }
    });
    
    // Banner text preview
    const bannerText = document.getElementById('banner-text');
    const discountCode = document.getElementById('discount-code');
    
    [bannerText, discountCode].forEach(input => {
        input.addEventListener('input', updateBannerPreview);
    });
    
    // Banner preview update function
    function updateBannerPreview() {
        const previewBox = document.getElementById('banner-preview-box');
        const text = bannerText.value || 'Free shipping on orders over $50!';
        const code = discountCode.value ? ` Use code: ${discountCode.value}` : '';
        
        previewBox.textContent = text + code;
        previewBox.style.color = textColorPicker.value;
        previewBox.style.backgroundColor = bgColorPicker.value;
    }
    
    // Form validation and submission
    const bannerForm = document.getElementById('banner-form');
    
    bannerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate banner text
        const textError = document.getElementById('banner-text-error');
        if (!bannerText.value.trim()) {
            textError.textContent = 'Banner text is required';
            bannerText.classList.add('error');
            return;
        } else {
            textError.textContent = '';
            bannerText.classList.remove('error');
        }
        
        // Save the form data (in a real app, this would be an API call)
        setTimeout(() => {
            // Simulate successful save
            const successMessage = document.querySelector('#top-banner .status-message.success');
            successMessage.style.display = 'block';
            
            // Add to history log
            addToHistoryLog('top-banner', 'admin', 
                toggleSwitch.checked ? 
                `Banner enabled - "${bannerText.value}"` : 
                'Banner disabled');
                
            // Hide success message after 3 seconds
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3000);
        }, 500);
    });
    
    // Reset button
    const resetBtn = document.getElementById('banner-reset');
    resetBtn.addEventListener('click', function() {
        bannerForm.reset();
        toggleLabel.textContent = 'Disabled';
        updateBannerPreview();
    });
    
    // Initial preview update
    updateBannerPreview();
}

function initDeliverySection() {
    // Form validation and submission
    const deliveryForm = document.getElementById('delivery-form');
    const minOrderAmount = document.getElementById('min-order-amount');
    const deliveryFee = document.getElementById('delivery-fee');
    
    deliveryForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        
        // Validate minimum order amount
        const minOrderError = document.getElementById('min-order-error');
        if (!minOrderAmount.value) {
            minOrderError.textContent = 'Minimum order amount is required';
            minOrderAmount.classList.add('error');
            isValid = false;
        } else if (parseFloat(minOrderAmount.value) < 0) {
            minOrderError.textContent = 'Amount cannot be negative';
            minOrderAmount.classList.add('error');
            isValid = false;
        } else {
            minOrderError.textContent = '';
            minOrderAmount.classList.remove('error');
        }
        
        // Validate delivery fee
        const deliveryFeeError = document.getElementById('delivery-fee-error');
        if (!deliveryFee.value) {
            deliveryFeeError.textContent = 'Delivery fee is required';
            deliveryFee.classList.add('error');
            isValid = false;
        } else if (parseFloat(deliveryFee.value) < 0) {
            deliveryFeeError.textContent = 'Fee cannot be negative';
            deliveryFee.classList.add('error');
            isValid = false;
        } else if (parseFloat(deliveryFee.value) > 50) {
            deliveryFeeError.textContent = 'Fee cannot exceed $50';
            deliveryFee.classList.add('error');
            isValid = false;
        } else {
            deliveryFeeError.textContent = '';
            deliveryFee.classList.remove('error');
        }
        
        if (!isValid) return;
        
        // Save the form data (in a real app, this would be an API call)
        setTimeout(() => {
            // Simulate successful save
            const successMessage = document.querySelector('#delivery .status-message.success');
            successMessage.style.display = 'block';
            
            // Add to history log
            addToHistoryLog('delivery', 'admin', 
                `Updated free shipping threshold to $${minOrderAmount.value}`);
                
            // Hide success message after 3 seconds
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3000);
        }, 500);
    });
    
    // Reset button
    const resetBtn = document.getElementById('delivery-reset');
    resetBtn.addEventListener('click', function() {
        deliveryForm.reset();
        document.getElementById('min-order-error').textContent = '';
        document.getElementById('delivery-fee-error').textContent = '';
        minOrderAmount.classList.remove('error');
        deliveryFee.classList.remove('error');
    });
}

// Function to add entries to history log
function addToHistoryLog(sectionId, username, action) {
    const logContainer = document.querySelector(`#${sectionId} .log-container`);
    
    // Create new log item
    const logItem = document.createElement('div');
    logItem.className = 'log-item';
    
    // Format current date and time
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const timeStr = now.toTimeString().split(' ')[0].substring(0, 5);
    
    // Populate log item
    logItem.innerHTML = `
        <span class="log-date">${dateStr} ${timeStr}</span>
        <span class="log-action">${action}</span>
    `;
    
    // Add to the top of the log
    if (logContainer.firstChild) {
        logContainer.insertBefore(logItem, logContainer.firstChild);
    } else {
        logContainer.appendChild(logItem);
    }
    
    // Limit history to 10 items
    const logItems = logContainer.querySelectorAll('.log-item');
    if (logItems.length > 10) {
        logContainer.removeChild(logItems[logItems.length - 1]);
    }
}

// Dropdown menu functionality
const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

dropdownToggles.forEach(toggle => {
    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Find the parent dropdown item
        const dropdownItem = toggle.closest('.nav-item.dropdown');
        
        // Toggle the .show class on the dropdown menu
        const dropdownMenu = dropdownItem.querySelector('.dropdown-menu');
        dropdownMenu.classList.toggle('show');
        
        // Close other dropdown menus
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            if (menu !== dropdownMenu) {
                menu.classList.remove('show');
            }
        });
    });
});

// Handle clicks on dropdown menu items
document.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Remove active class from all links
        navLinks.forEach(link => link.classList.remove('active'));
        
        // Add active class to parent dropdown toggle
        const parentDropdown = item.closest('.nav-item.dropdown');
        const dropdownToggle = parentDropdown.querySelector('.dropdown-toggle');
        dropdownToggle.classList.add('active');
        
        // Update page title
        const sectionName = item.getAttribute('data-section');
        if (sectionName) {
            pageTitle.textContent = sectionName.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
            
            // Hide all sections
            sections.forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section - first try with section ID directly
            const targetSection = document.getElementById(sectionName);
            if (targetSection) {
                targetSection.style.display = 'block';
            } else {
                // Try with -section suffix as fallback
                const sectionWithSuffix = document.getElementById(`${sectionName}-section`);
                if (sectionWithSuffix) {
                    sectionWithSuffix.style.display = 'block';
                }
            }
        }
        
        // Close the dropdown menu
        const dropdownMenu = parentDropdown.querySelector('.dropdown-menu');
        dropdownMenu.classList.remove('show');
        
        // Close sidebar on mobile after selection
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-item.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

function viewReviewDetails(reviewId) {
    // Implementation for viewing review details
    console.log("Viewing details for review:", reviewId);
    // Open modal or navigate to details page
}

function replyToReview(reviewId) {
    // Implementation for replying to a review
    console.log("Replying to review:", reviewId);
    // Open reply form or modal
}

function toggleFeatureReview(reviewId, element) {
    const isFeatured = element.getAttribute('data-featured') === 'true';
    const newFeatureStatus = !isFeatured;
    
    // Update UI immediately for responsive feel
    if (newFeatureStatus) {
        element.textContent = '⭐'; // Yellow star (featured)
        element.classList.add('featured');
        element.setAttribute('title', 'Remove from Homepage');
    } else {
        element.textContent = '☆'; // Empty star (not featured)
        element.classList.remove('featured');
        element.setAttribute('title', 'Feature on Homepage');
    }
    
    // Add animation
    element.classList.add('pulse');
    setTimeout(() => {
        element.classList.remove('pulse');
    }, 500);
    
    // Update data attribute
    element.setAttribute('data-featured', newFeatureStatus);
    
    // Send AJAX request to server
    updateFeatureStatus(reviewId, newFeatureStatus);
}

function updateFeatureStatus(reviewId, featured) {
    // AJAX request to update feature status in the database
    fetch('/api/reviews/feature', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reviewId: reviewId,
            featured: featured
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success notification
            showNotification(featured ? 
                'Review added to homepage!' : 
                'Review removed from homepage');
        } else {
            // Show error and revert UI if request failed
            showNotification('Failed to update review status', 'error');
            // Revert the UI change if the server update failed
            const element = document.querySelector(`tr[data-review-id="${reviewId}"] .feature-icon`);
            toggleFeatureReview(reviewId, element); // Toggle back
        }
    })
    .catch(error => {
        console.error('Error updating feature status:', error);
        showNotification('Error updating review status', 'error');
    });
}

function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Add to the page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}