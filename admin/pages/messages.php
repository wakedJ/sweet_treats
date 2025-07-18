<?php
// Include database connection
require_once './includes/check_admin.php';
require_once('../includes/db.php');

// Get total message count and percentage increase
$total_query = "SELECT COUNT(*) as total FROM messages";
$result = $conn->query($total_query);
$total_messages = $result->fetch_assoc()['total'];

// Get message count from last month for percentage calculation
$last_month_query = "SELECT COUNT(*) as last_month_total FROM messages 
                    WHERE submission_date < NOW() - INTERVAL 1 MONTH";
$result = $conn->query($last_month_query);
$last_month_total = $result->fetch_assoc()['last_month_total'];

// Calculate percentage change
$percent_change = 0;
if ($last_month_total > 0) {
    $percent_change = (($total_messages - $last_month_total) / $last_month_total) * 100;
}
$change_class = ($percent_change >= 0) ? 'positive' : 'negative';
$change_symbol = ($percent_change >= 0) ? '↑' : '↓';

// Process message status filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query based on filters (removed search from server-side processing)
$where_clause = "";
if ($status_filter == 'unread') {
    $where_clause = "WHERE is_read = 0";
} elseif ($status_filter == 'read') {
    $where_clause = "WHERE is_read = 1";
}

// Set order by clause
$order_by = $sort_order == 'newest' ? "ORDER BY submission_date DESC" : "ORDER BY submission_date ASC";

// Modified query to join with users table to get customer information if user_id exists
$query = "SELECT m.*, IFNULL(u.first_name, m.name) as display_name 
          FROM messages m
          LEFT JOIN users u ON m.user_id = u.id
          $where_clause
          $order_by
          LIMIT 50"; // Limit for performance

$messages_result = $conn->query($query);
?>

<!-- Dashboard Card for Total Messages -->
<div class="dashboard-cards">
    <div class="card">
        <div class="card-icon">✉️</div>
        <h3 class="card-title">Total Messages</h3>
        <div class="card-value"><?php echo number_format($total_messages); ?></div>
        <div class="card-stat <?php echo $change_class; ?>">
            <?php echo $change_symbol . ' ' . abs(round($percent_change, 1)) . '% from last month'; ?>
        </div>
    </div>
</div>

<div id="messages-section">
    <!-- Filters -->
    <form method="GET" action="" id="message-filters-form">
        <div class="filter-container">
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Messages</option>
                <option value="unread" <?php echo $status_filter == 'unread' ? 'selected' : ''; ?>>Unread</option>
                <option value="read" <?php echo $status_filter == 'read' ? 'selected' : ''; ?>>Read</option>
            </select>
            <select name="sort" class="filter-select" onchange="this.form.submit()">
                <option value="newest" <?php echo $sort_order == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo $sort_order == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
            </select>
            <input type="hidden" name="page" value="messages">
        </div>
    </form>
    
    <!-- Messages Table -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Customer Messages</h3>
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" class="search-input" placeholder="Search messages...">
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
            <tbody id="messagesTableBody">
                <?php if ($messages_result->num_rows > 0): ?>
                    <?php while ($row = $messages_result->fetch_assoc()): ?>
                        <?php 
                            $status_class = $row['is_read'] ? 'status-read' : '';
                            $date = new DateTime($row['submission_date']);
                            $formatted_date = $date->format('m/d/Y');
                        ?>
                        <tr data-message-id="<?php echo $row['message_id']; ?>" class="message-row <?php echo $status_class; ?>">
                            <td>
                                <div class="customer-info">
                                    <div class="customer-initial"><?php echo strtoupper(substr($row['display_name'], 0, 1)); ?></div>
                                    <div><?php echo htmlspecialchars($row['display_name']); ?></div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td class="message-text"><?php echo htmlspecialchars($row['message']); ?></td>
                            <td><?php echo $formatted_date; ?></td>
                            <td>
                                <span class="status-badge <?php echo $row['is_read'] ? 'read' : 'unread'; ?>">
                                    <?php echo $row['is_read'] ? 'Read' : 'Unread'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-icons">
                                    <div class="action-icon view-icon" title="View Details" 
                                         onclick="viewMessageDetails(<?php echo $row['message_id']; ?>)">👁️</div>
                                    <div class="action-icon reply-icon" title="Reply" 
                                         onclick="replyToMessage(<?php echo $row['message_id']; ?>, '<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>')">💬</div>
                                    <div class="action-icon mark-icon" title="<?php echo $row['is_read'] ? 'Mark as Unread' : 'Mark as Read'; ?>" 
                                         onclick="toggleReadStatus(<?php echo $row['message_id']; ?>, this, <?php echo $row['is_read'] ? 'true' : 'false'; ?>)">
                                        <?php echo $row['is_read'] ? '📖' : '📩'; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-data">No messages found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Message Modal -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Message Details</h2>
        <div id="message-details-content">
            <!-- Content will be loaded here via AJAX -->
        </div>
    </div>
</div>

<!-- Reply Modal -->
<!-- Updated Reply Modal -->
<div id="replyModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Reply to Message</h2>
        <form id="reply-form">
            <input type="hidden" id="reply-message-id" name="message_id">
            <div class="form-group">
                <label for="reply-to">To:</label>
                <input type="email" id="reply-to" name="email" readonly>
            </div>
            <div class="form-group">
                <label for="reply-subject">Subject:</label>
                <input type="text" id="reply-subject" name="subject" value="Re: ">
            </div>
            <div class="form-group">
                <label for="reply-content">Message:</label>
                <textarea id="reply-content" name="content" rows="6"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Send Reply</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Hidden row class for search filtering */
.hidden-row {
    display: none !important;
}
</style>

<script>
// Auto-search functionality for messages
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.message-row');
    let visibleRowCount = 0;

    rows.forEach(function(row) {
        const cells = row.querySelectorAll('td');
        let rowText = '';
        
        // Get text from first 4 columns (customer, subject, message, date)
        for (let i = 0; i < 4; i++) {
            if (cells[i]) {
                rowText += cells[i].textContent.toLowerCase() + ' ';
            }
        }

        if (searchTerm === '' || rowText.includes(searchTerm)) {
            row.classList.remove('hidden-row');
            visibleRowCount++;
        } else {
            row.classList.add('hidden-row');
        }
    });

    // Handle "No messages found" message
    const tbody = document.getElementById('messagesTableBody');
    let noResultsRow = document.getElementById('noResultsRow');
    
    if (visibleRowCount === 0 && searchTerm !== '') {
        if (!noResultsRow) {
            noResultsRow = document.createElement('tr');
            noResultsRow.id = 'noResultsRow';
            noResultsRow.innerHTML = '<td colspan="6" style="text-align: center; color: #999;">No messages found matching your search</td>';
            tbody.appendChild(noResultsRow);
        }
        noResultsRow.style.display = 'table-row';
    } else {
        if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    }
});

// Replace the viewMessageDetails function in your admin panel
function viewMessageDetails(messageId) {
    console.log('Fetching message ID:', messageId);
    
    // Show loading indicator
    document.getElementById('message-details-content').innerHTML = '<div style="text-align:center;">Loading...</div>';
    document.getElementById('messageModal').style.display = 'block';
    
    // Fix: Make sure the path is correct for your server structure
    fetch(`ajax/get_message.php?id=${messageId}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            
            try {
                // Try to parse the JSON
                const data = JSON.parse(text);
                console.log('Parsed response:', data);
                
                if (data.status === 'success') {
                    // Populate modal with message details
                    document.getElementById('message-details-content').innerHTML = `
                        <div class="message-detail-header">
                            <div class="message-sender">
                                <strong>From:</strong> ${data.name} (${data.email})
                            </div>
                            <div class="message-date">
                                <strong>Date:</strong> ${data.formatted_date}
                            </div>
                        </div>
                        <div class="message-detail-subject">
                            <strong>Subject:</strong> ${data.subject}
                        </div>
                        <div class="message-detail-body">
                            ${data.message}
                        </div>
                    `;
                    
                    // Mark message as read if it wasn't already
                    if (!data.is_read) {
                        updateReadStatus(messageId, true);
                    }
                } else {
                    console.error('Error in response:', data.message);
                    document.getElementById('message-details-content').innerHTML = `
                        <div class="error-message">
                            Error loading message details: ${data.message}
                        </div>
                    `;
                }
            } catch (e) {
                console.error('JSON parsing error:', e);
                document.getElementById('message-details-content').innerHTML = `
                    <div style="background-color: #f8f8f8; padding: 10px; margin-bottom: 15px; overflow-x: auto;">
                        <strong>Raw server response:</strong><br>
                        <pre>${text}</pre>
                    </div>
                    <p>Error parsing server response. Please check the console and the response above.</p>
                    <button onclick="retryViewMessage(${messageId})">Retry</button>
                `;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            document.getElementById('message-details-content').innerHTML = `
                <div class="error-message">
                    Network error loading message details: ${error.message}
                </div>
            `;
        });
}

function retryViewMessage(messageId) {
    // Simple function to retry loading the message
    viewMessageDetails(messageId);
}

function updateReadStatus(messageId, markAsRead) {
    // Separate function to update read status
    const newStatus = markAsRead ? 1 : 0;
    
    fetch('ajax/mark_message_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `message_id=${messageId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(result => {
        console.log('Mark as read result:', result);
        if (result.status === 'success') {
            const row = document.querySelector(`tr[data-message-id="${messageId}"]`);
            if (row) {
                if (markAsRead) {
                    row.classList.add('status-read');
                    const statusBadge = row.querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.className = 'status-badge read';
                        statusBadge.textContent = 'Read';
                    }
                    const markIcon = row.querySelector('.mark-icon');
                    if (markIcon) {
                        markIcon.innerHTML = '📖';
                        markIcon.title = 'Mark as Unread';
                        markIcon.setAttribute('onclick', 
                          `toggleReadStatus(${messageId}, this, true)`);
                    }
                } else {
                    // Handle marking as unread if needed
                }
            }
        } else {
            console.error('Error updating status:', result.message);
        }
    })
    .catch(error => console.error('Error updating status:', error));
}

// Updated Reply Handler with better error handling
function replyToMessage(messageId, email) {
    // Set up reply modal
    document.getElementById('reply-message-id').value = messageId;
    document.getElementById('reply-to').value = email;
    
    // Use the correct path to get_message.php
    fetch(`ajax/get_message.php?id=${messageId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Set subject with Re: prefix if not already there
                let subject = data.subject;
                if (!subject.startsWith('Re:')) {
                    subject = 'Re: ' + subject;
                }
                document.getElementById('reply-subject').value = subject;
                
                // Show modal
                document.getElementById('replyModal').style.display = 'block';
            } else {
                // Show modal anyway with generic Re: subject
                document.getElementById('replyModal').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Show modal anyway with generic Re: subject
            document.getElementById('replyModal').style.display = 'block';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    // Form submission handler for reply form
    const replyForm = document.getElementById('reply-form');
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Display loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
            
            // Use relative path to your PHP file from the current directory
            fetch('ajax/send-reply.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Server response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        console.error('Received non-JSON response:', text);
                        throw new Error('Invalid response format');
                    });
                }
            })
            .then(data => {
                console.log('Server response:', data);
                
                // Only proceed if we have valid data with a status
                if (data && data.status) {
                    if (data.status === 'success') {
                        alert('Reply sent successfully');
                        document.getElementById('replyModal').style.display = 'none';
                        replyForm.reset();
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                    }
                } else {
                    throw new Error('Invalid response data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending reply: ' + error.message);
            })
            .finally(() => {
                // Reset button state
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
            });
        });
    }
});

function toggleReadStatus(messageId, element, isCurrentlyRead) {
    // Update read status via AJAX
    const newStatus = isCurrentlyRead ? 0 : 1;
    
    fetch('ajax/mark_message_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `message_id=${messageId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const row = document.querySelector(`tr[data-message-id="${messageId}"]`);
            const statusBadge = row.querySelector('.status-badge');
            
            if (newStatus === 1) {
                // Marked as read
                row.classList.add('status-read');
                element.innerHTML = '📖';
                element.title = 'Mark as Unread';
                element.setAttribute('onclick', `toggleReadStatus(${messageId}, this, true)`);
                if (statusBadge) {
                    statusBadge.className = 'status-badge read';
                    statusBadge.textContent = 'Read';
                }
            } else {
                // Marked as unread
                row.classList.remove('status-read');
                element.innerHTML = '📩';
                element.title = 'Mark as Read';
                element.setAttribute('onclick', `toggleReadStatus(${messageId}, this, false)`);
                if (statusBadge) {
                    statusBadge.className = 'status-badge unread';
                    statusBadge.textContent = 'Unread';
                }
            }
        } else {
            console.error('Error updating message status:', data.message);
            alert('Error updating message status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating message status');
    });
}

// Modal handling
document.addEventListener('DOMContentLoaded', function() {
    // Close modals when clicking on X or outside the modal
    const closeButtons = document.querySelectorAll('.close-modal');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });
    
    // Close when clicking outside modal content
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
});
</script>