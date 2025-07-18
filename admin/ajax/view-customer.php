<?php
// Include database connection
require_once "../includes/db.php";

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: customers.php");
    exit;
}

$customer_id = intval($_GET['id']);

// Query to get the customer details
$sql = "SELECT id, first_name, last_name, email, phone_number, created_at, status, 
               address, city, state, zip_code, last_login, notes
        FROM users 
        WHERE id = ? AND role = 'customer'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if customer exists
if ($result->num_rows === 0) {
    header("Location: customers.php");
    exit;
}

$customer = $result->fetch_assoc();

// Get customer orders (if you have an orders table)
$orders_sql = "SELECT id, order_date, total_amount, status 
              FROM orders 
              WHERE customer_id = ? 
              ORDER BY order_date DESC 
              LIMIT 5";

$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $customer_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customer - <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .back-link {
            text-decoration: none;
            color: #666;
            display: flex;
            align-items: center;
        }
        .customer-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
        }
        .info-row {
            margin-bottom: 12px;
        }
        .info-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 3px;
        }
        .info-value {
            font-weight: 500;
        }
        .section-title {
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.2rem;
            color: #333;
        }
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        .status-active {
            background-color: #e6f7e6;
            color: #28a745;
        }
        .status-inactive {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        .status-suspended {
            background-color: #ffecec;
            color: #dc3545;
        }
        .action-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .action-button:hover {
            background-color: #0069d9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="customers.php" class="back-link">← Back to Customers</a>
            <a href="edit_customer.php?id=<?php echo $customer_id; ?>" class="action-button">Edit Customer</a>
        </div>
        
        <h1><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h1>
        
        <div class="customer-info">
            <div class="info-card">
                <h3>Personal Information</h3>
                
                <div class="info-row">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['email']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['phone_number']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Address</div>
                    <div class="info-value">
                        <?php 
                        $address = trim(htmlspecialchars($customer['address'] ?? ''));
                        $city = trim(htmlspecialchars($customer['city'] ?? ''));
                        $state = trim(htmlspecialchars($customer['state'] ?? ''));
                        $zip = trim(htmlspecialchars($customer['zip_code'] ?? ''));
                        
                        $full_address = $address;
                        if ($city && $state) $full_address .= ($full_address ? ', ' : '') . "$city, $state";
                        if ($zip) $full_address .= ($full_address ? ' ' : '') . $zip;
                        
                        echo $full_address ?: 'Not provided';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="info-card">
                <h3>Account Information</h3>
                
                <div class="info-row">
                    <div class="info-label">Customer ID</div>
                    <div class="info-value"><?php echo $customer_id; ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <?php 
                        $status_class = '';
                        switch($customer['status']) {
                            case 'active':
                                $status_class = 'status-active';
                                break;
                            case 'inactive':
                                $status_class = 'status-inactive';
                                break;
                            case 'suspended':
                                $status_class = 'status-suspended';
                                break;
                        }
                        ?>
                        <span class="status <?php echo $status_class; ?>">
                            <?php echo ucfirst($customer['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Customer Since</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($customer['created_at'])); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Last Login</div>
                    <div class="info-value">
                        <?php 
                        echo !empty($customer['last_login']) 
                            ? date('F j, Y \a\t g:i a', strtotime($customer['last_login']))
                            : 'Never logged in';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <h3 class="section-title">Recent Orders</h3>
        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo date('m/d/Y', strtotime($order['order_date'])); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo ucfirst($order['status']); ?></td>
                            <td><a href="view_order.php?id=<?php echo $order['id']; ?>">View</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders found for this customer.</p>
        <?php endif; ?>
        
        <?php if (!empty($customer['notes'])): ?>
        <h3 class="section-title">Notes</h3>
        <div class="info-card">
            <p><?php echo nl2br(htmlspecialchars($customer['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Close statement and connection
$stmt->close();
if (isset($orders_stmt)) $orders_stmt->close();
$conn->close();
?>