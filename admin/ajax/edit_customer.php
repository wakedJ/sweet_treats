<?php
// Include database connection
require_once "../../includes/db.php"; 

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: customers.php");
    exit;
}

$customer_id = intval($_GET['id']);

// Query to get the customer details
$sql = "SELECT id, first_name, last_name, email, phone_number, status
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
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
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-section {
            margin-bottom: 25px;
        }
        .form-row {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            height: 100px;
        }
        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            box-sizing: border-box;
        }
        .button-row {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .submit-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .cancel-button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .submit-button:hover {
            background-color: #218838;
        }
        .cancel-button:hover {
            background-color: #5a6268;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="view_customer.php?id=<?php echo $customer_id; ?>" class="back-link">← Back to Customer Details</a>
        </div>
        
        <h1>Edit Customer</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert <?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
        
        <form action="update_customer.php" method="POST">
            <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
            
            <div class="form-grid">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    
                    <div class="form-row">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" 
                               value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" 
                               value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <label for="phone_number" class="form-label">Phone</label>
                        <input type="text" id="phone_number" name="phone_number" class="form-input" 
                               value="<?php echo htmlspecialchars($customer['phone_number']); ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Address</h3>
                    
                    <div class="form-row">
                        <label for="address" class="form-label">Street Address</label>
                        <input type="text" id="address" name="address" class="form-input" 
                               value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <label for="city" class="form-label">City</label>
                        <input type="text" id="city" name="city" class="form-input" 
                               value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <label for="state" class="form-label">State</label>
                        <input type="text" id="state" name="state" class="form-input" 
                               value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <label for="zip_code" class="form-label">ZIP Code</label>
                        <input type="text" id="zip_code" name="zip_code" class="form-input" 
                               value="<?php echo htmlspecialchars($customer['zip_code'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Account Information</h3>
                
                <div class="form-row">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="active" <?php echo $customer['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $customer['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $customer['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" class="form-textarea"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="button-row">
                <a href="view_customer.php?id=<?php echo $customer_id; ?>" class="cancel-button">Cancel</a>
                <button type="submit" class="submit-button">Save Changes</button>
            </div>
        </form>
    </div>
</body>
</html>

<?php
// Close statement and connection
$stmt->close();
$conn->close();
?>