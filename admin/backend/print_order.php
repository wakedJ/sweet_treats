<?php
// print_order.php - Generates a printable receipt for an order
require_once "../../includes/db.php";  // Fixed path to db.php

// Get order ID from request
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    die("Invalid order ID");
}

// First, let's check the structure of the users table to find the correct column names
$check_users_query = "SHOW COLUMNS FROM users";
$check_result = $conn->query($check_users_query);
$user_columns = [];

if ($check_result) {
    while ($row = $check_result->fetch_assoc()) {
        $user_columns[] = $row['Field'];
    }
}

// Replace the existing dynamic column detection section with this:
$name_column = "CONCAT(first_name, ' ', last_name)";
$email_column = "email";

// Update the main query to use the concatenated name:
$query = "SELECT o.*, 
          $name_column as customer_name, 
          u.$email_column as customer_email,
          a.street_address, a.city, a.state, a.postal_code, a.phone_number
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id
          LEFT JOIN user_addresses a ON o.delivery_address_id = a.id
          WHERE o.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("Order not found");
}

$order = $result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, p.name as product_name
               FROM order_items oi
               LEFT JOIN products p ON oi.product_id = p.id
               WHERE oi.order_id = ?";

$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

$items = [];
if ($items_result && $items_result->num_rows > 0) {
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }
}

// Get promotion details if applicable
$promo_code = '';
$promo_discount = '';
if (isset($order['promo_id']) && $order['promo_id']) {
   $promo_query = "SELECT code, discount_type, discount_value 
               FROM promo_codes 
               WHERE promo_id = ?";  // Changed back to promo_id
    $stmt = $conn->prepare($promo_query);
    $stmt->bind_param("i", $order['promo_id']);
    $stmt->execute();
    $promo_result = $stmt->get_result();
    
    if ($promo_result && $promo_result->num_rows > 0) {
        $promo = $promo_result->fetch_assoc();
        $promo_code = $promo['code'];
        
        if ($promo['discount_type'] == 'percentage') {
            $promo_discount = $promo['discount_value'] . '%';
        } else {
            $promo_discount = '$' . number_format($promo['discount_value'], 2);
        }
    }
}

// Format date
$order_date = isset($order['created_at']) ? date('F j, Y, g:i a', strtotime($order['created_at'])) : 'N/A';

// Output HTML for printing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .order-info {
            margin-bottom: 20px;
        }
        .customer-info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .totals {
            text-align: right;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.9em;
            color: #666;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Receipt</h1>
        <p>Sweet Treats Shop</p>
    </div>
    
    <div class="order-info">
        <h2>Order #<?php echo str_pad($order_id, 4, '0', STR_PAD_LEFT); ?></h2>
        <p><strong>Date:</strong> <?php echo $order_date; ?></p>
        <p><strong>Order Type:</strong> <?php echo isset($order['order_type']) ? $order['order_type'] : 'N/A'; ?></p>
    </div>
    
    <div class="customer-info">
        <h2>Customer Information</h2>
        <p><strong>Name:</strong> <?php echo isset($order['customer_name']) ? htmlspecialchars($order['customer_name']) : 'N/A'; ?></p>
        <?php if (isset($order['customer_email']) && $order['customer_email']): ?>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
        <?php endif; ?>
        
        <?php if (isset($order['street_address']) && $order['street_address']): ?>
        <h3>Delivery Address</h3>
        <p>
            <?php echo htmlspecialchars($order['street_address']); ?><br>
            <?php echo isset($order['city']) ? htmlspecialchars($order['city']) : ''; ?>, 
            <?php echo isset($order['state']) ? htmlspecialchars($order['state']) : ''; ?> 
            <?php echo isset($order['postal_code']) ? htmlspecialchars($order['postal_code']) : ''; ?><br>
            Phone: <?php echo isset($order['phone_number']) ? htmlspecialchars($order['phone_number']) : 'N/A'; ?>
        </p>
        <?php endif; ?>
        
        <?php if (isset($order['pickup_time']) && $order['pickup_time']): ?>
        <h3>Pickup Information</h3>
        <p><strong>Pickup Time:</strong> <?php echo htmlspecialchars($order['pickup_time']); ?></p>
        <?php if (isset($order['pickup_instructions']) && $order['pickup_instructions']): ?>
        <p><strong>Instructions:</strong> <?php echo htmlspecialchars($order['pickup_instructions']); ?></p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <h2>Order Items</h2>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $items_total = 0;
            foreach ($items as $item): 
                $item_total = $item['quantity'] * $item['price'];
                $items_total += $item_total;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>$<?php echo number_format($item['price'], 2); ?></td>
                <td>$<?php echo number_format($item_total, 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <tr>
                <td colspan="4">No items found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="totals">
        <p><strong>Subtotal:</strong> $<?php echo isset($order['subtotal']) ? number_format($order['subtotal'], 2) : '0.00'; ?></p>
        
        <?php if (isset($order['discount_amount']) && floatval($order['discount_amount']) > 0): ?>
        <p>
            <strong>Discount<?php echo $promo_code ? ' (' . htmlspecialchars($promo_code) . ')' : ''; ?>:</strong> 
            -$<?php echo number_format($order['discount_amount'], 2); ?>
        </p>
        <?php endif; ?>
        
        <?php if (isset($order['delivery_fee']) && floatval($order['delivery_fee']) > 0): ?>
        <p><strong>Delivery Fee:</strong> $<?php echo number_format($order['delivery_fee'], 2); ?></p>
        <?php endif; ?>
        
        <p style="font-size: 1.2em;"><strong>Total:</strong> $<?php echo isset($order['total_price']) ? number_format($order['total_price'], 2) : '0.00'; ?></p>
    </div>
    
    <div class="footer">
        <p>Thank you for your order!</p>
        <p>For questions or assistance, please contact us at support@sweettreats.com</p>
    </div>
    
    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()">Print Receipt</button>
        <button onclick="window.close()">Close</button>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>