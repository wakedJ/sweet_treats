<?php
require_once './includes/check_admin.php';?>
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Order Management</h3>
        <div class="filter-section">
            <select id="status-filter">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select id="date-filter">
                <option value="all">All Dates</option>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
            </select>
            <span id="order-count" class="count-display">3 orders</span>
        </div>
        <div class="search-container">
            <span class="search-icon">🔍</span>
            <input type="text" id="order-search" class="search-input" placeholder="Search orders...">
        </div>
    </div>
    <table class="table" id="orders-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Products</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="orders-table-body">
            <!-- Orders will be loaded here dynamically -->
        </tbody>
    </table>
    <div class="pagination">
        <button id="prev-page">Previous</button>
        <span id="page-info">Page 1 of 1</span>
        <button id="next-page">Next</button>
    </div>
</div>

<!-- Order Details Modal -->
<div id="order-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Order Details</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="order-summary">
                <div class="order-info">
                    <h3>Order <span id="modal-order-id"></span></h3>
                    <p>Date: <span id="modal-order-date"></span></p>
                    <p>Status: <span id="modal-order-status"></span></p>
                    <p>Order Type: <span id="modal-order-type"></span></p>
                    <p>Customer: <span id="modal-customer-name"></span></p>
                </div>
                <div class="order-totals">
                    <p>Subtotal: $<span id="modal-subtotal"></span></p>
                    <p>Discount: $<span id="modal-discount"></span></p>
                    <p>Delivery Fee: $<span id="modal-delivery-fee"></span></p>
                    <p class="total">Total: $<span id="modal-total"></span></p>
                </div>
            </div>
            
            <div class="address-section">
                <h3>Delivery Address</h3>
                <p id="modal-address"></p>
                <p>Phone: <span id="modal-phone"></span></p>
            </div>
            
            <div class="pickup-section">
                <h3>Pickup Information</h3>
                <p>Pickup Time: <span id="modal-pickup-time"></span></p>
                <p>Instructions: <span id="modal-pickup-instructions"></span></p>
            </div>
            
            <div class="promo-section">
                <h3>Promotion</h3>
                <p>Code: <span id="modal-promo-code"></span></p>
            </div>
            
            <h3>Order Items</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="modal-items">
                    <!-- Order items will be inserted here -->
                </tbody>
            </table>
            
            <!-- Updated Status Update Section for Order Modal -->
<div class="status-update">
    <h3>Update Status</h3>
    <div class="status-options">
        <div class="status-option" data-status="pending">
            <div class="status-circle status-pending"></div>
            <span>Pending</span>
        </div>
        <div class="status-option" data-status="processing">
            <div class="status-circle status-processing"></div>
            <span>Processing</span>
        </div>
        <div class="status-option" data-status="shipped">
            <div class="status-circle status-shipped"></div>
            <span>Shipped</span>
        </div>
        <div class="status-option" data-status="delivered">
            <div class="status-circle status-delivered"></div>
            <span>Delivered</span>
        </div>
        <div class="status-option" data-status="completed">
            <div class="status-circle status-completed"></div>
            <span>Completed</span>
        </div>
        <div class="status-option" data-status="cancelled">
            <div class="status-circle status-cancelled"></div>
            <span>Cancelled</span>
        </div>
    </div>
    <button id="update-status-btn" disabled>Update Status</button>
</div>

<style>
.status-update {
    margin-top: 20px;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 5px;
}

.status-options {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 15px 0;
}

.status-option {
    display: flex;
    align-items: center;
    padding: 8px 15px;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
    cursor: pointer;
    transition: all 0.2s;
}

.status-option:hover {
    background-color: #f0f0f0;
}

.status-option.selected {
    background-color: #e8f4ff;
    border-color: #007bff;
}

.status-circle {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-circle.status-pending { background-color: #e6a700; }
.status-circle.status-processing { background-color: #0091ea; }
.status-circle.status-shipped { background-color: #2962ff; }
.status-circle.status-delivered { background-color: #00b8a3; }
.status-circle.status-completed { background-color: #52c41a; }
.status-circle.status-cancelled { background-color: #f5222d; }

#update-status-btn {
    background-color: #ff1493;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
}

#update-status-btn:hover:not(:disabled) {
    background-color: #ffccd5;
}

#update-status-btn:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
}

.status-update-loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
    margin-left: 10px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
        </div>
    </div>
</div>

<script>// Global variables
let setCurrentOrderStatus = null;
let currentPage = 1;
const ordersPerPage = 10;
let allOrders = [];
let filteredOrders = [];

// Main initialization function
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded - initializing order management");
    
    // Initialize the status update UI and get the setter function
    setCurrentOrderStatus = initializeStatusUI();
    
    // Fetch orders and set up event listeners
    fetchOrders();
    setupEventListeners();
    
    // Add CSS for notifications
    addNotificationStyles();
});

// Initialize the status update UI and return the setter function
function initializeStatusUI() {
    console.log("Initializing status update UI");
    
    const statusOptions = document.querySelectorAll('.status-option');
    const updateButton = document.getElementById('update-status-btn');
    let currentOrderId = null;
    let selectedStatus = null;
    
    // Check if elements exist
    if (!statusOptions.length || !updateButton) {
        console.error("Status options or update button not found");
        return null;
    }
    
    console.log(`Found ${statusOptions.length} status options`);
    
    // Initialize status option click events
    statusOptions.forEach(option => {
        option.addEventListener('click', function() {
            console.log(`Status option clicked: ${this.getAttribute('data-status')}`);
            
            // Remove selected class from all options
            statusOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Get selected status
            selectedStatus = this.getAttribute('data-status');
            
            // Enable update button
            updateButton.disabled = false;
        });
    });
    
    // Update button click handler
    updateButton.addEventListener('click', function() {
        if (!currentOrderId || !selectedStatus) {
            console.warn("Missing order ID or status");
            return;
        }
        
        console.log(`Update button clicked for order ${currentOrderId} with status ${selectedStatus}`);
        
        // Show loading state
        const originalText = updateButton.textContent;
        updateButton.innerHTML = 'Updating... <span class="status-update-loading"></span>';
        updateButton.disabled = true;
        
        // Update order status
        updateOrderStatus(currentOrderId, selectedStatus)
            .then(success => {
                if (success) {
                    console.log("Status updated successfully");
                    // Close modal after successful update
                    setTimeout(() => {
                        document.getElementById('order-modal').style.display = 'none';
                    }, 1000);
                } else {
                    console.warn("Status update failed");
                }
            })
            .finally(() => {
                // Reset button state
                updateButton.innerHTML = originalText;
                updateButton.disabled = false;
            });
    });
    
    // Return the function to set current order and highlight current status
    return function(orderId, currentStatus) {
        console.log(`Setting current order status: Order ID ${orderId}, Status ${currentStatus}`);
        currentOrderId = orderId;
        selectedStatus = null;
        
        // Reset selection
        statusOptions.forEach(opt => opt.classList.remove('selected'));
        updateButton.disabled = true;
        
        // Highlight current status if it exists
        if (currentStatus) {
            const currentOption = document.querySelector(`.status-option[data-status="${currentStatus}"]`);
            if (currentOption) {
                currentOption.classList.add('selected');
                selectedStatus = currentStatus;
                updateButton.disabled = false;
            } else {
                console.warn(`No option found for status: ${currentStatus}`);
            }
        }
    };
}

// Fetch orders from database
function fetchOrders() {
    console.log("Fetching orders");
    
    // Display loading indicator
    const tableBody = document.getElementById('orders-table-body');
    tableBody.innerHTML = '<tr><td colspan="8" class="loading">Loading orders...</td></tr>';
    
    fetch('./backend/get_orders.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log(`Orders received: ${data.length}`);
            
            if (!Array.isArray(data)) {
                showNotification('Received invalid data format from server', 'error');
                console.error('Expected array but got:', data);
                return;
            }
            
            allOrders = data;
            filteredOrders = [...allOrders];
            updateOrderCount();
            renderOrders();
        })
        .catch(error => {
            console.error('Error fetching orders:', error);
            showNotification('Failed to load orders. Check console for details.', 'error');
            tableBody.innerHTML = '<tr><td colspan="8" class="error">Error loading orders. Please try again.</td></tr>';
        });
}

// Render orders to the table
function renderOrders() {
    console.log("Rendering orders");
    const tableBody = document.getElementById('orders-table-body');
    tableBody.innerHTML = '';
    
    const startIndex = (currentPage - 1) * ordersPerPage;
    const endIndex = startIndex + ordersPerPage;
    const ordersToShow = filteredOrders.slice(startIndex, endIndex);
    
    if (ordersToShow.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8" class="no-data">No orders found</td></tr>';
        return;
    }
    
    ordersToShow.forEach(order => {
        const row = document.createElement('tr');
        
        // Format products for display
        let productsDisplay = '';
        if (order.items && order.items.length > 0) {
            productsDisplay = order.items[0].product.name;
            if (order.items.length > 1) {
                productsDisplay += ` (+${order.items.length - 1} more)`;
            }
        }
        
        // Format date
        const orderDate = new Date(order.created_at);
        const formattedDate = orderDate.toLocaleDateString();
        
        // Create status class based on order status
        const statusClass = `status-${order.status.toLowerCase()}`;
        
        row.innerHTML = `
            <td>#SWEET-${String(order.id).padStart(4, '0')}</td>
            <td>${order.customer_name}</td>
            <td>${productsDisplay}</td>
            <td>$${parseFloat(order.total_price).toFixed(2)}</td>
            <td>${formattedDate}</td>
            <td>${order.order_type}</td>
            <td><span class="status ${statusClass}">${capitalizeFirstLetter(order.status)}</span></td>
            <td>
                <div class="action-icons">
                    <div class="action-icon view-icon" data-id="${order.id}">👁️</div>
                    <div class="action-icon edit-icon" data-id="${order.id}">✏️</div>
                    <div class="action-icon print-icon" data-id="${order.id}">🖨️</div>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
    
    // Update pagination
    updatePagination();
    
    // Add event listeners to action buttons
    document.querySelectorAll('.view-icon').forEach(btn => {
        btn.addEventListener('click', function() {
            openOrderDetails(this.getAttribute('data-id'));
        });
    });
    
    document.querySelectorAll('.edit-icon').forEach(btn => {
        btn.addEventListener('click', function() {
            openOrderDetails(this.getAttribute('data-id'), true);
        });
    });
    
    document.querySelectorAll('.print-icon').forEach(btn => {
        btn.addEventListener('click', function() {
            printOrder(this.getAttribute('data-id'));
        });
    });
}

// Open order details modal
function openOrderDetails(orderId, editMode = false) {
    console.log(`Opening order details for ID: ${orderId}, Edit mode: ${editMode}`);
    
    const order = allOrders.find(o => o.id == orderId);
    if (!order) {
        console.error(`Order not found with ID: ${orderId}`);
        return;
    }
    
    // Populate modal with order data
    document.getElementById('modal-order-id').textContent = `#ORD-${String(order.id).padStart(4, '0')}`;
    document.getElementById('modal-order-date').textContent = new Date(order.created_at).toLocaleString();
    document.getElementById('modal-order-status').textContent = capitalizeFirstLetter(order.status);
    document.getElementById('modal-order-status').className = `status-${order.status.toLowerCase()}`;
    document.getElementById('modal-order-type').textContent = order.order_type;
    document.getElementById('modal-customer-name').textContent = order.customer_name;
    document.getElementById('modal-subtotal').textContent = parseFloat(order.subtotal).toFixed(2);
    document.getElementById('modal-discount').textContent = parseFloat(order.discount_amount || 0).toFixed(2);
    document.getElementById('modal-delivery-fee').textContent = parseFloat(order.delivery_fee || 0).toFixed(2);
    document.getElementById('modal-total').textContent = parseFloat(order.total_price).toFixed(2);
    
    // Address information
    if (order.address) {
        document.getElementById('modal-address').textContent = `${order.address.street_address}, ${order.address.city}, ${order.address.state} ${order.address.postal_code}`;
        document.getElementById('modal-phone').textContent = order.address.phone_number;
        document.querySelector('.address-section').style.display = 'block';
    } else {
        document.querySelector('.address-section').style.display = 'none';
    }
    
    // Pickup information
    if (order.pickup_time) {
        document.getElementById('modal-pickup-time').textContent = order.pickup_time;
        document.getElementById('modal-pickup-instructions').textContent = order.pickup_instructions || 'None';
        document.querySelector('.pickup-section').style.display = 'block';
    } else {
        document.querySelector('.pickup-section').style.display = 'none';
    }
    
    // Promo code information
    if (order.promo_code) {
        document.getElementById('modal-promo-code').textContent = order.promo_code;
        document.querySelector('.promo-section').style.display = 'block';
    } else {
        document.querySelector('.promo-section').style.display = 'none';
    }
    
    // Populate order items
    const itemsContainer = document.getElementById('modal-items');
    itemsContainer.innerHTML = '';
    
    if (order.items && order.items.length > 0) {
        order.items.forEach(item => {
            const row = document.createElement('tr');
            const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
            
            row.innerHTML = `
                <td>${item.product.name}</td>
                <td>${item.quantity}</td>
                <td>$${parseFloat(item.price).toFixed(2)}</td>
                <td>$${itemTotal.toFixed(2)}</td>
            `;
            itemsContainer.appendChild(row);
        });
    } else {
        itemsContainer.innerHTML = '<tr><td colspan="4">No items found</td></tr>';
    }
    
    // Set current order status in the status update UI
    if (typeof setCurrentOrderStatus === 'function') {
        setCurrentOrderStatus(order.id, order.status);
    } else {
        console.error("setCurrentOrderStatus is not a function. Status UI may not be initialized.");
    }
    
    // Show/Hide edit sections based on mode
    document.querySelector('.status-update').style.display = editMode ? 'block' : 'none';
    
    // Show modal
    document.getElementById('order-modal').style.display = 'block';
}

// Update order status function
    function updateOrderStatus(orderId, newStatus) {
        console.log(`Updating order status: Order ID ${orderId}, New status ${newStatus}`);
        
        // Show a loading notification
        showNotification('Updating order status...', 'info');
        
        // Create form data
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('status', newStatus);
        
        return new Promise((resolve) => {
            fetch('./backend/update_order_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Status update response:', data);
                
                if (data.success) {
                    // Update the order in our local array
                    const orderIndex = allOrders.findIndex(o => o.id == orderId);
                    if (orderIndex >= 0) {
                        allOrders[orderIndex].status = newStatus;
                        
                        // Also update the modal display
                        const statusElement = document.getElementById('modal-order-status');
                        if (statusElement) {
                            statusElement.textContent = capitalizeFirstLetter(newStatus);
                            statusElement.className = `status-${newStatus.toLowerCase()}`;
                        }
                        
                        // Re-filter and re-render
                        applyFilters();
                        
                        // Show success notification
                        showNotification('Order status updated successfully!', 'success');
                        resolve(true);
                    }
                } else {
                    showNotification('Failed to update order status: ' + (data.message || 'Unknown error'), 'error');
                    resolve(false);
                }
            })
            .catch(error => {
                console.error('Error updating order status:', error);
                showNotification('An error occurred while updating status: ' + error.message, 'error');
                resolve(false);
            });
        });
    }

// Set up all event listeners
function setupEventListeners() {
    console.log("Setting up event listeners");
    
    // Close modal when clicking the X
    document.querySelector('.close-modal').addEventListener('click', function() {
        document.getElementById('order-modal').style.display = 'none';
    });
    
    // Close modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('order-modal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Search and filter event listeners
    document.getElementById('order-search').addEventListener('input', applyFilters);
    document.getElementById('status-filter').addEventListener('change', applyFilters);
    document.getElementById('date-filter').addEventListener('change', applyFilters);
    
    // Pagination buttons
    document.getElementById('prev-page').addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            renderOrders();
        }
    });
    
    document.getElementById('next-page').addEventListener('click', function() {
        const totalPages = Math.ceil(filteredOrders.length / ordersPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderOrders();
        }
    });
}

// Apply filters based on search, status and date
function applyFilters() {
    console.log("Applying filters");
    const searchTerm = document.getElementById('order-search').value.toLowerCase();
    const statusFilter = document.getElementById('status-filter').value;
    const dateFilter = document.getElementById('date-filter').value;
    
    filteredOrders = allOrders.filter(order => {
        // Search filter
        const searchMatch = 
            `#ord-${String(order.id).padStart(4, '0')}`.toLowerCase().includes(searchTerm) ||
            (order.customer_name && order.customer_name.toLowerCase().includes(searchTerm)) ||
            (order.items && order.items.some(item => 
                item.product && 
                item.product.name && 
                item.product.name.toLowerCase().includes(searchTerm)
            ));
        
        // Status filter
        const statusMatch = statusFilter === 'all' || (order.status && order.status.toLowerCase() === statusFilter);
        
        // Date filter
        let dateMatch = true;
        if (dateFilter !== 'all' && order.created_at) {
            const orderDate = new Date(order.created_at);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (dateFilter === 'today') {
                const tomorrow = new Date(today);
                tomorrow.setDate(tomorrow.getDate() + 1);
                dateMatch = orderDate >= today && orderDate < tomorrow;
            } else if (dateFilter === 'yesterday') {
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                dateMatch = orderDate >= yesterday && orderDate < today;
            } else if (dateFilter === 'week') {
                const weekStart = new Date(today);
                weekStart.setDate(weekStart.getDate() - weekStart.getDay());
                dateMatch = orderDate >= weekStart;
            } else if (dateFilter === 'month') {
                const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                dateMatch = orderDate >= monthStart;
            }
        }
        
        return searchMatch && statusMatch && dateMatch;
    });
    
    // Reset to first page when filters change
    currentPage = 1;
    
    // Update display
    updateOrderCount();
    renderOrders();
}

// Update pagination info
function updatePagination() {
    const totalPages = Math.ceil(filteredOrders.length / ordersPerPage);
    document.getElementById('page-info').textContent = `Page ${currentPage} of ${totalPages || 1}`;
    
    // Enable/disable pagination buttons
    document.getElementById('prev-page').disabled = currentPage <= 1;
    document.getElementById('next-page').disabled = currentPage >= totalPages;
}

// Update order count display
function updateOrderCount() {
    document.getElementById('order-count').textContent = `${filteredOrders.length} orders`;
}

// Print order
function printOrder(orderId) {
    window.open(`./backend/print_order.php?id=${orderId}`, '_blank');
}

// Helper function to capitalize first letter
function capitalizeFirstLetter(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}

// Helper function to show notifications
// Helper function to show notifications
function showNotification(message, type) {
    console.log(`Notification: ${message} (${type})`);
    
    // Remove any existing notifications first
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(n => {
        if (n.parentNode) {
            n.parentNode.removeChild(n);
        }
    });
    
    const notification = document.createElement('div');
    notification.className = `notification ${type || 'info'}`;
    notification.textContent = message;
    
    // Append to body
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Add notification styles
function addNotificationStyles() {
    // Check if styles are already added
    if (document.getElementById('notification-styles')) {
        return;
    }
    
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
    /* Updated Notification styles for centered appearance */
    .notification {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 25px;
        border-radius: 4px;
        font-weight: 500;
        z-index: 1000;
        animation: slideDown 0.3s ease-out;
        min-width: 250px;
        max-width: 80%;
        word-wrap: break-word;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: opacity 0.3s;
        text-align: center;
    }
    
    .notification.success {
        background-color: #dff0d8;
        border: 1px solid #d6e9c6;
        color: #3c763d;
    }
    
    .notification.error {
        background-color: #f2dede;
        border: 1px solid #ebccd1;
        color: #a94442;
    }
    
    .notification.info {
        background-color: #d9edf7;
        border: 1px solid #bce8f1;
        color: #31708f;
    }
    
    .notification.fade-out {
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    @keyframes slideDown {
        from { 
            transform: translateY(-20px) translateX(-50%);
            opacity: 0;
        }
        to { 
            transform: translateY(0) translateX(-50%);
            opacity: 1;
        }
    }
    
    /* Status message styles */
    .status-message {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
        transition: opacity 0.5s;
    }
    
    .status-message.success {
        background-color: #dff0d8;
        border: 1px solid #d6e9c6;
        color: #3c763d;
    }
    
    .status-message.error {
        background-color: #f2dede;
        border: 1px solid #ebccd1;
        color: #a94442;
    }
    
    .error-message {
        color: #a94442;
        font-size: 12px;
        margin-top: 5px;
    }
    
    /* Loading and error states */
    .loading, .error, .no-data {
        text-align: center;
        padding: 20px;
        color: #666;
        font-style: italic;
    }
    
    .error {
        color: #a94442;
    }
    
    /* Status update loading spinner */
    .status-update-loading {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-left: 5px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    `;
    document.head.appendChild(style);
}
// New helper function for toast notifications
function showToastNotification(message, type) {
    console.log(`Toast notification: ${message} (${type})`);
    
    // Remove existing toast notifications
    const existingToasts = document.querySelectorAll('.sweet-treats-toast');
    existingToasts.forEach(toast => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    });
    
    // Create new toast notification
    const toast = document.createElement('div');
    toast.className = `sweet-treats-toast sweet-treats-toast-${type || 'info'}`;
    toast.textContent = message;
    
    // Create container if it doesn't exist
    let toastContainer = document.getElementById('sweet-treats-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'sweet-treats-toast-container';
        document.body.appendChild(toastContainer);
    }
    
    // Append to container
    toastContainer.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('sweet-treats-toast-fade-out');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Add toast notification styles with !important for critical properties
function addToastNotificationStyles() {
    // Check if styles are already added
    if (document.getElementById('sweet-treats-toast-styles')) {
        return;
    }
    
    const style = document.createElement('style');
    style.id = 'sweet-treats-toast-styles';
    style.textContent = `
    /* Toast container */
    #sweet-treats-toast-container {
        position: fixed !important;
        top: 20px !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        z-index: 9999 !important;
        width: auto !important;
        max-width: 90% !important;
        pointer-events: none !important;
    }
    
    /* Base toast styling */
    .sweet-treats-toast {
        position: relative !important;
        display: block !important;
        margin-bottom: 10px !important;
        padding: 12px 25px !important;
        border-radius: 5px !important;
        font-weight: 500 !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;
        animation: sweet-treats-toast-slide-down 0.3s ease-out !important;
        min-width: 250px !important;
        max-width: 100% !important;
        text-align: center !important;
        pointer-events: auto !important;
        opacity: 1 !important;
        background-color: white !important;
        color: #333 !important;
    }
    
    /* Toast types */
    .sweet-treats-toast-success {
        background-color: #dff0d8 !important;
        border: 1px solid #d6e9c6 !important;
        color: #3c763d !important;
    }
    
    .sweet-treats-toast-error {
        background-color: #f2dede !important;
        border: 1px solid #ebccd1 !important;
        color: #a94442 !important;
    }
    
    .sweet-treats-toast-info {
        background-color: #d9edf7 !important;
        border: 1px solid #bce8f1 !important;
        color: #31708f !important;
    }
    
    .sweet-treats-toast-fade-out {
        opacity: 0 !important;
        transition: opacity 0.3s !important;
    }
    
    @keyframes sweet-treats-toast-slide-down {
        from { 
            transform: translateY(-20px);
            opacity: 0;
        }
        to { 
            transform: translateY(0);
            opacity: 1;
        }
    }
    `;
    document.head.appendChild(style);
}

// Function to modify the existing updateOrderStatus function
function modifyUpdateOrderStatusFunction() {
    // First, check if the original function exists
    if (typeof updateOrderStatus !== 'function') {
        console.error('Could not find updateOrderStatus function to modify');
        return;
    }
    
    // Store the original function
    const originalUpdateOrderStatus = updateOrderStatus;
    
    // Replace with our modified version
    updateOrderStatus = function(orderId, newStatus) {
        console.log(`Updating order status: Order ID ${orderId}, New status ${newStatus}`);
        
        // Use our new toast notification
        showToastNotification('Updating order status...', 'info');
        
        // Initialize toast styles
        addToastNotificationStyles();
        
        // Create form data
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('status', newStatus);
        
        return new Promise((resolve) => {
            fetch('./backend/update_order_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Status update response:', data);
                
                if (data.success) {
                    // Update the order in our local array
                    const orderIndex = allOrders.findIndex(o => o.id == orderId);
                    if (orderIndex >= 0) {
                        allOrders[orderIndex].status = newStatus;
                        
                        // Also update the modal display
                        const statusElement = document.getElementById('modal-order-status');
                        if (statusElement) {
                            statusElement.textContent = capitalizeFirstLetter(newStatus);
                            statusElement.className = `status-${newStatus.toLowerCase()}`;
                        }
                        
                        // Re-filter and re-render
                        applyFilters();
                        
                        // Show success notification with our new toast
                        showToastNotification('Order status updated successfully!', 'success');
                        resolve(true);
                    }
                } else {
                    showToastNotification('Failed to update order status: ' + (data.message || 'Unknown error'), 'error');
                    resolve(false);
                }
            })
            .catch(error => {
                console.error('Error updating order status:', error);
                showToastNotification('An error occurred while updating status: ' + error.message, 'error');
                resolve(false);
            });
        });
    };
}

// Initialize the modified functions when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add our toast notification styles
    addToastNotificationStyles();
    
    // Modify the updateOrderStatus function to use our toast
    modifyUpdateOrderStatusFunction();
});
</script>

<!-- You'll also need to create these backend fileFs: -->
<!-- get_orders.php - Fetches orders from database -->
<!-- update_order_status.php - Updates order status -->
<!-- print_order.php - Generates printable order receipt -->