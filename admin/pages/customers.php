<?php
// Database connection parameters
require_once './includes/check_admin.php';
require_once "../includes/db.php";

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle status update
if (isset($_POST['update_status'])) {
    $customer_id = (int)$_POST['customer_id'];
    $new_status = $_POST['status'];
    
    $update_sql = "UPDATE users SET status = ? WHERE id = ? AND role = 'customer'";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $customer_id);
    
    if ($stmt->execute()) {
        echo "<div class='status-message success'>Status updated successfully!</div>";
    } else {
        echo "<div class='status-message error'>❌ Error updating status</div>";
    }
    $stmt->close();
}

// Query to get all customers (users with role = 'customer')
$sql = "SELECT id, first_name, last_name, email, phone_number, created_at, status FROM users WHERE role = 'customer'";
$result = $conn->query($sql);
?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>
    <style>
        /* Root variables */
        :root {
            --primary: #ff1493;
            --secondary: #8a2be2;
            --tertiary: #ff69b4;
            --background: #fff8fa;
            --light-pink: #ffccd5;
            --white: #ffffff;
            --text: #333333;
            --shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        /* Table container styles */
        .table-container {
            width: 100%;
            margin: 20px auto;
            background-color: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 2px dashed var(--light-pink);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: var(--background);
            border-bottom: 1px solid var(--light-pink);
        }

        .table-title {
            margin: 0;
            font-size: 1.2rem;
            color: var(--secondary);
        }

        /* Search container */
        .search-container {
            display: flex;
            align-items: center;
            background-color: var(--white);
            border: 2px solid var(--light-pink);
            border-radius: 50px;
            padding: 8px 15px;
            width: 250px;
            transition: all 0.3s ease;
        }

        .search-container:focus-within {
            border-color: var(--tertiary);
            box-shadow: 0 0 10px rgba(255,105,180,0.2);
        }

        .search-icon {
            margin-right: 10px;
            color: var(--tertiary);
        }

        .search-input {
            border: none;
            background: transparent;
            width: 100%;
            outline: none;
            color: var(--text);
        }

        /* Table styles */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background-color: var(--light-pink);
            padding: 12px 15px;
            text-align: left;
            color: var(--secondary);
            font-weight: 600;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-pink);
            color: var(--text);
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background-color: var(--background);
        }

        /* Status select dropdown - Fixed styling */
        .status-select {
            padding: 8px 12px;
            border-radius: 20px;
            border: 2px solid var(--light-pink);
            background-color: var(--white);
            font-size: 0.85rem;
            color: var(--text);
            font-weight: 500;
            outline: none;
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 100px;
        }

        .status-select:focus {
            border-color: var(--tertiary);
            box-shadow: 0 0 10px rgba(255,105,180,0.2);
        }

        /* Fix for dropdown options visibility */
        .status-select option {
            background-color: var(--white);
            color: var(--text);
            padding: 8px 12px;
            font-weight: 500;
        }

        .status-select option:hover {
            background-color: var(--background);
        }

        /* Status messages */
        .status-message {
            padding: 12px 15px;
            border-radius: 50px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideIn 0.5s ease-out, fadeOut 0.5s ease-in 2.5s forwards;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        }

        .status-message.success {
            background-color: white;
            color: var(--tertiary);
            border: 1px solid var(--light-pink);
        }

        .status-message.error {
            background-color: rgba(255, 69, 0, 0.2);
            color: #ff4500;
            border: 1px solid #ff4500;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Button styles */
        .update-btn {
            background: linear-gradient(135deg, var(--tertiary), var(--primary));
            color: var(--white);
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255,20,147,0.3);
        }

        /* Hidden row class for search filtering */
        .hidden-row {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Customer Management</h3>
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" class="search-input" id="searchInput" placeholder="Search customers...">
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Join Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="customerTableBody">
                <?php
                if ($result->num_rows > 0) {
                    // Output data of each row
                    while($row = $result->fetch_assoc()) {
                        // Format the date
                        $join_date = date('m/d/Y', strtotime($row['created_at']));
                        
                        echo "<tr class='customer-row'>
                                <td>" . htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['last_name']) . "</td>
                                <td>" . htmlspecialchars($row['email']) . "</td>
                                <td>" . htmlspecialchars($row['phone_number']) . "</td>
                                <td>" . $join_date . "</td>
                                <td>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='customer_id' value='" . $row['id'] . "'>
                                        <select name='status' class='status-select'>
                                            <option value='active'" . ($row['status'] == 'active' ? ' selected' : '') . ">Active</option>
                                            <option value='suspended'" . ($row['status'] == 'suspended' ? ' selected' : '') . ">Suspended</option>
                                        </select>
                                </td>
                                <td>
                                        <button type='submit' name='update_status' class='update-btn'>Update</button>
                                    </form>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align: center;'>No customers found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        // Auto-search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.customer-row');
            let visibleRowCount = 0;

            rows.forEach(function(row) {
                const cells = row.querySelectorAll('td');
                let rowText = '';
                
                // Get text from first 4 columns (name, email, phone, date)
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

            // Handle "No customers found" message
            const tbody = document.getElementById('customerTableBody');
            let noResultsRow = document.getElementById('noResultsRow');
            
            if (visibleRowCount === 0 && searchTerm !== '') {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'noResultsRow';
                    noResultsRow.innerHTML = '<td colspan="6" style="text-align: center; color: #999;">No customers found matching your search</td>';
                    tbody.appendChild(noResultsRow);
                }
                noResultsRow.style.display = 'table-row';
            } else {
                if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>