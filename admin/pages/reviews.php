<?php 
    require_once './includes/check_admin.php'; 
    require_once '../includes/db.php';

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Count total reviews
    $totalReviewsStmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews");
    $totalReviewsStmt->execute();
    $totalReviewRow = $totalReviewsStmt->get_result()->fetch_assoc();
    $totalReviews = $totalReviewRow['total'];

    // Get previous month's review count for comparison
    $lastMonthStmt = $conn->prepare("
        SELECT COUNT(*) as last_month_total 
        FROM reviews 
        WHERE submitted_at BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') 
        AND DATE_FORMAT(NOW(), '%Y-%m-01')
    ");
    $lastMonthStmt->execute();
    $lastMonthRow = $lastMonthStmt->get_result()->fetch_assoc();
    $lastMonthTotal = $lastMonthRow['last_month_total'];

    // Get current month's review count
    $currentMonthStmt = $conn->prepare("
        SELECT COUNT(*) as current_month_total 
        FROM reviews 
        WHERE submitted_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
    ");
    $currentMonthStmt->execute();
    $currentMonthRow = $currentMonthStmt->get_result()->fetch_assoc();
    $currentMonthTotal = $currentMonthRow['current_month_total'];

    // Calculate percentage change
    $percentageChange = 0;
    if ($lastMonthTotal > 0) {
        $percentageChange = (($currentMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100;
    }
    $changeClass = $percentageChange >= 0 ? 'positive' : 'negative';
    $changeSymbol = $percentageChange >= 0 ? '↑' : '↓';

    ?>

    <div class="container">
        <h2>Reviews</h2>
        
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-icon">⭐</div>
                <h3 class="card-title">Total Reviews</h3>
                <div class="card-value"><?php echo number_format($totalReviews); ?></div>
                <div class="card-stat <?php echo $changeClass; ?>">
                    <?php echo $changeSymbol . ' ' . abs(round($percentageChange, 1)) . '% from last month'; ?>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">Recent Reviews</h3>
                <div class="search-container">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-input" placeholder="Search reviews..." id="reviewSearch">
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch recent reviews with user information
                    $reviewsStmt = $conn->prepare("
                        SELECT r.id, r.subject, r.message, r.submitted_at, r.approval_status,
                            COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Anonymous') as customer_name
                        FROM reviews r
                        LEFT JOIN users u ON r.user_id = u.id
                        ORDER BY r.submitted_at DESC
                        LIMIT 50
                    ");
                    $reviewsStmt->execute();
                    $reviewsResult = $reviewsStmt->get_result();
                    
                    if ($reviewsResult->num_rows > 0) {
                        while ($review = $reviewsResult->fetch_assoc()) {
                            $reviewId = (int)$review['id'];
                            $customerName = htmlspecialchars($review['customer_name']);
                            $subject = htmlspecialchars($review['subject'] ?? 'No Subject');
                            $message = htmlspecialchars($review['message']);
                            $truncatedMessage = strlen($message) > 100 ? substr($message, 0, 100) . '...' : $message;
                            $date = date('m/d/Y', strtotime($review['submitted_at']));
                            $status = $review['approval_status'];
                            $statusClass = $status === 'approved' ? 'status-approved' : 'status-hidden';
                    ?>
                    <tr data-review-id="<?php echo $reviewId; ?>">
                        <td>
                            <div class="customer-info">
                                <div><?php echo $customerName; ?></div>
                            </div>
                        </td>
                        <td class="subject-text"><?php echo $subject; ?></td>
                        <td class="message-text" title="<?php echo $message; ?>"><?php echo $truncatedMessage; ?></td>
                        <td><?php echo $date; ?></td>
                        <td>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-icons">
                                <div class="action-icon view-icon" title="View Details" onclick="viewReviewDetails(<?php echo $reviewId; ?>)">👁️</div>
                                <div class="action-icon <?php echo $status === 'approved' ? 'approve-icon active' : 'approve-icon'; ?>" 
                                    title="<?php echo $status === 'approved' ? 'Hide Review' : 'Approve Review'; ?>" 
                                    onclick="toggleReviewStatus(<?php echo $reviewId; ?>, '<?php echo $status; ?>')" 
                                    data-status="<?php echo $status; ?>"
                                    data-review-id="<?php echo $reviewId; ?>"
                                    id="toggle-<?php echo $reviewId; ?>">
                                    <?php echo $status === 'approved' ? '✓' : '○'; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo '<tr><td colspan="6" class="no-data">No reviews found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Review Details Modal -->
    <div id="reviewModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Review Details</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Review details will be loaded here -->
            </div>
        </div>
    </div>

    <style>
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .status-approved {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-hidden {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .action-icons {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .action-icon {
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        min-height: 30px;
    }
    
    .view-icon {
        background-color: #007bff;
        color: white;
    }
    
    .view-icon:hover {
        background-color: #0056b3;
        transform: scale(1.1);
    }
    
    .approve-icon {
        background-color: #6c757d;
        color: white;
        border: 2px solid #6c757d;
    }
    
    .approve-icon.active {
        background-color: #28a745;
        border-color: #28a745;
    }
    
    .approve-icon:hover {
        transform: scale(1.1);
        opacity: 0.8;
    }
    
    .no-data {
        text-align: center;
        color: #6c757d;
        font-style: italic;
        padding: 20px;
    }
    
    /* Modal Styles */
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 0;
        border: none;
        border-radius: 8px;
        width: 80%;
        max-width: 600px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .modal-header {
        padding: 20px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
        color: #333;
    }
    
    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
    }
    
    .close:hover {
        color: #333;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .toast-message {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        z-index: 1001;
        font-weight: bold;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        max-width: 300px;
        word-wrap: break-word;
    }
    
    .toast-message.success {
        background-color: #28a745;
    }
    
    .toast-message.error {
        background-color: #dc3545;
    }
    
    .toast-message.info {
        background-color: #007bff;
    }
    </style>

    <script>
    // JavaScript for search functionality
    document.getElementById('reviewSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.table tbody tr');
        
        rows.forEach(row => {
            if (row.querySelector('.no-data')) return; // Skip "no data" row
            
            const customerName = row.querySelector('.customer-info div')?.textContent.toLowerCase() || '';
            const subjectText = row.querySelector('.subject-text')?.textContent.toLowerCase() || '';
            const messageText = row.querySelector('.message-text')?.textContent.toLowerCase() || '';
            
            if (customerName.includes(searchTerm) || 
                subjectText.includes(searchTerm) || 
                messageText.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Function to view review details in modal
    function viewReviewDetails(reviewId) {
        const modal = document.getElementById('reviewModal');
        const modalBody = document.getElementById('modalBody');
        
        // Show loading state
        modalBody.innerHTML = '<div style="text-align: center; padding: 20px;">Loading...</div>';
        modal.style.display = 'block';
        
        // Try multiple possible API paths
        const possiblePaths = [
            'api/get_review_details.php',
            './api/get_review_details.php',
            '../api/get_review_details.php',
            'get_review_details.php'
        ];
        
        // Function to try each path
        const tryPath = async (path) => {
            try {
                const response = await fetch(`${path}?id=${reviewId}`);
                if (response.ok) {
                    return await response.json();
                }
                throw new Error(`HTTP ${response.status}`);
            } catch (error) {
                console.log(`Failed to fetch from ${path}:`, error.message);
                return null;
            }
        };
        
        // Try each path until one works
        const tryAllPaths = async () => {
            for (const path of possiblePaths) {
                const result = await tryPath(path);
                if (result) {
                    if (result.success) {
                        const review = result.review;
                        modalBody.innerHTML = `
                            <div class="review-details">
                                <p><strong>Customer:</strong> ${escapeHtml(review.customer_name || 'Anonymous')}</p>
                                <p><strong>Subject:</strong> ${escapeHtml(review.subject || 'No Subject')}</p>
                                <p><strong>Date:</strong> ${new Date(review.submitted_at).toLocaleDateString()}</p>
                                <p><strong>Status:</strong> 
                                    <span class="status-badge ${review.approval_status === 'approved' ? 'status-approved' : 'status-hidden'}">
                                        ${review.approval_status.charAt(0).toUpperCase() + review.approval_status.slice(1)}
                                    </span>
                                </p>
                                <div style="margin-top: 20px;">
                                    <strong>Message:</strong>
                                    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px; white-space: pre-wrap;">${escapeHtml(review.message)}</div>
                                </div>
                            </div>
                        `;
                        return;
                    } else {
                        modalBody.innerHTML = `<div style="color: red;">Error: ${result.message}</div>`;
                        return;
                    }
                }
            }
            
            // If no path worked, show fallback content with the data we already have
            const row = document.querySelector(`tr[data-review-id="${reviewId}"]`);
            if (row) {
                const customerName = row.querySelector('.customer-info div').textContent;
                const subject = row.querySelector('.subject-text').textContent;
                const message = row.querySelector('.message-text').getAttribute('title') || row.querySelector('.message-text').textContent;
                const date = row.cells[3].textContent;
                const status = row.querySelector('.status-badge').textContent.toLowerCase();
                
                modalBody.innerHTML = `
                    <div class="review-details">
                        <p><strong>Customer:</strong> ${escapeHtml(customerName)}</p>
                        <p><strong>Subject:</strong> ${escapeHtml(subject)}</p>
                        <p><strong>Date:</strong> ${date}</p>
                        <p><strong>Status:</strong> 
                            <span class="status-badge ${status === 'approved' ? 'status-approved' : 'status-hidden'}">
                                ${status.charAt(0).toUpperCase() + status.slice(1)}
                            </span>
                        </p>
                        <div style="margin-top: 20px;">
                            <strong>Message:</strong>
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px; white-space: pre-wrap;">${escapeHtml(message)}</div>
                        </div>

                    </div>
                `;
            } else {
                modalBody.innerHTML = `<div style="color: red;">Error: Could not load review details and no fallback data available.</div>`;
            }
        };
        
        tryAllPaths();
    }

    // Function to close modal
    function closeModal() {
        document.getElementById('reviewModal').style.display = 'none';
    }

    // Function to toggle review approval status
    function toggleReviewStatus(reviewId, currentStatus) {
        const toggleElement = document.getElementById('toggle-' + reviewId);
        if (!toggleElement) {
            showMessage('Error: Toggle element not found', 'error');
            return;
        }
        
        const newStatus = currentStatus === 'approved' ? 'hidden' : 'approved';
        
        // Store original content and state
        const originalContent = toggleElement.innerHTML;
        const originalTitle = toggleElement.title;
        
        // Show loading state
        toggleElement.innerHTML = '⏳';
        toggleElement.title = 'Updating...';
        toggleElement.disabled = true;
        
        const requestData = {
            review_id: parseInt(reviewId),
            status: newStatus
        };
        
        fetch('pages/api/toggle_approval_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateToggleElement(toggleElement, newStatus);
                updateStatusBadge(reviewId, newStatus);
                showMessage(data.message || 'Review status updated successfully!', 'success');
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error updating review status:', error);
            showMessage('Error: ' + error.message, 'error');
            // Restore original state
            toggleElement.innerHTML = originalContent;
            toggleElement.title = originalTitle;
        })
        .finally(() => {
            toggleElement.disabled = false;
        });
    }

    // Function to update the toggle element appearance
    function updateToggleElement(element, newStatus) {
        element.setAttribute('data-status', newStatus);
        
        const reviewId = element.getAttribute('data-review-id');
        element.setAttribute('onclick', `toggleReviewStatus(${reviewId}, '${newStatus}')`);
        
        // Update classes and content
        element.classList.remove('active');
        
        if (newStatus === 'approved') {
            element.classList.add('active');
            element.innerHTML = '✓';
            element.title = 'Hide Review';
        } else {
            element.innerHTML = '○';
            element.title = 'Approve Review';
        }
    }

    // Function to update status badge
    function updateStatusBadge(reviewId, newStatus) {
        const row = document.querySelector(`tr[data-review-id="${reviewId}"]`);
        if (row) {
            const statusBadge = row.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = `status-badge ${newStatus === 'approved' ? 'status-approved' : 'status-hidden'}`;
                statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            }
        }
    }

    // Function to show toast messages
    function showMessage(message, type = 'info') {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.toast-message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = `toast-message ${type}`;
        messageDiv.textContent = message;
        
        document.body.appendChild(messageDiv);
        
        // Auto-remove message after 4 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 4000);
    }

    // Utility function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('reviewModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Reviews page loaded successfully');
        
        // Add any initialization code here
        const toggleButtons = document.querySelectorAll('[id^="toggle-"]');
        console.log(`Initialized ${toggleButtons.length} toggle buttons`);
    });
    </script>