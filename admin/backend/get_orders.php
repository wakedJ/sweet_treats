<?php
// get_orders.php - Fetches orders from database with related details and debugging
// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file for debugging
$log_file = __DIR__ . '/order_debug.log';
function log_message($message) {
    global $log_file;
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

log_message("get_orders.php started");

// Check if DB file exists and is readable
$db_file = "../../includes/db.php";
if (!file_exists($db_file)) {
    log_message("DB file not found: $db_file");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection file not found']);
    exit;
}

log_message("DB file exists, attempting to include");

// Require the database connection
require_once $db_file;

// Check if connection was successful
if (!isset($conn) || !$conn) {
    log_message("Database connection failed: " . (isset($conn) ? mysqli_connect_error() : "conn variable not set"));
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

log_message("Database connection successful");

// Get orders with pagination, sorting, and filtering
$orders = [];

try {
    // First, let's check the database structure to determine the correct column names
    // Getting information about the users table
    $users_columns = [];
    $check_users = $conn->query("SHOW COLUMNS FROM users");
    if (!$check_users) {
        log_message("Error checking users table: " . $conn->error);
    } else {
        while ($column = $check_users->fetch_assoc()) {
            $users_columns[] = $column['Field'];
        }
        log_message("Found users columns: " . implode(", ", $users_columns));
    }
    
    // Getting information about the user_addresses table
    $address_columns = [];
    $check_addresses = $conn->query("SHOW COLUMNS FROM user_addresses");
    if (!$check_addresses) {
        log_message("Error checking user_addresses table: " . $conn->error);
    } else {
        while ($column = $check_addresses->fetch_assoc()) {
            $address_columns[] = $column['Field'];
        }
        log_message("Found address columns: " . implode(", ", $address_columns));
    }
    
    // Check product table structure
    $product_columns = [];
    $check_products = $conn->query("SHOW COLUMNS FROM products");
    if (!$check_products) {
        log_message("Error checking products table: " . $conn->error);
    } else {
        while ($column = $check_products->fetch_assoc()) {
            $product_columns[] = $column['Field'];
        }
        log_message("Found product columns: " . implode(", ", $product_columns));
    }
    
    // Build the customer name field based on available columns
    $customer_name_field = '';
    if (in_array('first_name', $users_columns) && in_array('last_name', $users_columns)) {
        // Concatenate first_name and last_name
        $customer_name_field = "CONCAT(IFNULL(u.first_name, ''), ' ', IFNULL(u.last_name, '')) as customer_name";
        log_message("Using concatenated first_name and last_name for customer name");
    } else if (in_array('name', $users_columns)) {
        $customer_name_field = "u.name as customer_name";
        log_message("Using 'name' column for customer name");
    } else if (in_array('full_name', $users_columns)) {
        $customer_name_field = "u.full_name as customer_name";
        log_message("Using 'full_name' column for customer name");
    } else if (in_array('username', $users_columns)) {
        $customer_name_field = "u.username as customer_name";
        log_message("Using 'username' column for customer name");
    } else {
        $customer_name_field = "CONCAT('User #', u.id) as customer_name";
        log_message("No suitable name column found, using 'User #ID' format");
    }
    
    // Base query to get orders with customer info - build dynamically based on available columns
    $query = "SELECT o.*, $customer_name_field";
    
    // Add additional user fields if available
    if (in_array('email', $users_columns)) {
        $query .= ", u.email as customer_email";
    }
    if (in_array('phone_number', $users_columns)) {
        $query .= ", u.phone_number as customer_phone";
    }
    
    // Add address fields if the user_addresses table has the expected columns
    if (!empty($address_columns)) {
        if (in_array('street_address', $address_columns)) {
            $query .= ", a.street_address";
        }
        if (in_array('city', $address_columns)) {
            $query .= ", a.city";
        }
        if (in_array('state', $address_columns)) {
            $query .= ", a.state";
        }
        if (in_array('postal_code', $address_columns)) {
            $query .= ", a.postal_code";
        }
        if (in_array('phone_number', $address_columns)) {
            $query .= ", a.phone_number as address_phone";
        }
    }
    
    // Check if promo_codes table exists
    $check_promo = $conn->query("SHOW TABLES LIKE 'promo_codes'");
    $promo_exists = $check_promo && $check_promo->num_rows > 0;
    
    if ($promo_exists) {
        $query .= ", p.code as promo_code_name";
    }
    
    $query .= " FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id
              LEFT JOIN user_addresses a ON o.delivery_address_id = a.id";
    
    if ($promo_exists) {
        $query .= " LEFT JOIN promo_codes p ON o.promo_id = p.promo_id";
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    log_message("Executing query: " . $query);
    
    $result = $conn->query($query);
    
    if (!$result) {
        log_message("Query error: " . $conn->error);
        throw new Exception("Database query error: " . $conn->error);
    }
    
    log_message("Query successful, found " . $result->num_rows . " orders");
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Initialize order
            $order = $row;
            
            // Clean up the customer_name field (remove extra spaces if concatenated)
            if (isset($order['customer_name'])) {
                $order['customer_name'] = trim($order['customer_name']);
                // If the name is just spaces or empty, use a default
                if (empty($order['customer_name'])) {
                    $order['customer_name'] = 'Customer #' . $order['user_id'];
                }
            }
            
            // Enhanced order items query with complete product details
            $items_query = "SELECT oi.id as order_item_id, oi.order_id, oi.quantity, oi.price as ordered_price, 
                          p.id as product_id, p.name, p.description, p.price as current_price, 
                          p.sale_price, p.category_id, p.stock, p.tag, p.image, p.average_rating,
                          p.updated_at as product_updated_at, p.created_at as product_created_at
                      FROM order_items oi
                      LEFT JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
            
            $stmt = $conn->prepare($items_query);
            if (!$stmt) {
                log_message("Error preparing items query: " . $conn->error);
                continue;
            }
            
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $items_result = $stmt->get_result();
            
            $order['items'] = [];
            if ($items_result && $items_result->num_rows > 0) {
                while ($item = $items_result->fetch_assoc()) {
                    // Structure the product data
                    $product = [
                        'id' => $item['product_id'],
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'price' => $item['current_price'],
                        'sale_price' => $item['sale_price'],
                        'category_id' => $item['category_id'],
                        'stock' => $item['stock'],
                        'tag' => $item['tag'],
                        'image' => $item['image'],
                        'average_rating' => $item['average_rating'],
                        'updated_at' => $item['product_updated_at'],
                        'created_at' => $item['product_created_at']
                    ];
                    
                    // Structure the order item
                    $orderItem = [
                        'id' => $item['order_item_id'],
                        'order_id' => $item['order_id'],
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['ordered_price'],  // Price at time of order
                        'product' => $product  // Include full product details
                    ];
                    
                    $order['items'][] = $orderItem;
                }
            }
            
            // Format address for convenience
            if (isset($row['street_address'])) {
                $order['address'] = [
                    'street_address' => isset($row['street_address']) ? $row['street_address'] : '',
                    'city' => isset($row['city']) ? $row['city'] : '',
                    'state' => isset($row['state']) ? $row['state'] : '',
                    'postal_code' => isset($row['postal_code']) ? $row['postal_code'] : '',
                    'phone_number' => isset($row['address_phone']) ? $row['address_phone'] : 
                                     (isset($row['phone_number']) ? $row['phone_number'] : '')
                ];
            }
            
            // Clean up redundant fields from the main order array
            $fieldsToRemove = ['street_address', 'city', 'state', 'postal_code', 'address_phone'];
            foreach ($fieldsToRemove as $field) {
                unset($order[$field]);
            }
            
            // Handle promo code
            if (isset($row['promo_code_name'])) {
                $order['promo_code'] = $row['promo_code_name'];
                unset($order['promo_code_name']);
            }
            
            $orders[] = $order;
        }
    } else {
        // If no orders found, let's provide some sample data for testing
        log_message("No orders found in database, providing sample data");
        
        $orders = [
            [
                'id' => 1001,
                'user_id' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'status' => 'delivered',
                'order_type' => 'delivery',
                'subtotal' => 42.50,
                'discount_amount' => 5.00,
                'delivery_fee' => 3.99,
                'total_price' => 41.49,
                'customer_name' => 'John Doe',
                'promo_code' => 'WELCOME10',
                'items' => [
                    [
                        'id' => 2001,
                        'order_id' => 1001,
                        'product_id' => 101,
                        'quantity' => 1,
                        'price' => 22.50,
                        'product' => [
                            'id' => 101,
                            'name' => 'Chocolate Cake',
                            'description' => 'Rich chocolate cake with ganache frosting',
                            'price' => 22.50,
                            'sale_price' => null,
                            'category_id' => 1,
                            'stock' => 15,
                            'tag' => 'cake,chocolate,bestseller',
                            'image' => 'chocolate_cake.jpg',
                            'average_rating' => 4.8,
                            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
                            'created_at' => date('Y-m-d H:i:s', strtotime('-3 months'))
                        ]
                    ],
                    [
                        'id' => 2002,
                        'order_id' => 1001,
                        'product_id' => 102,
                        'quantity' => 2,
                        'price' => 10.00,
                        'product' => [
                            'id' => 102,
                            'name' => 'Cupcakes (6)',
                            'description' => 'Six assorted flavored cupcakes',
                            'price' => 12.00,
                            'sale_price' => 10.00,
                            'category_id' => 2,
                            'stock' => 24,
                            'tag' => 'cupcake,assorted',
                            'image' => 'cupcakes.jpg',
                            'average_rating' => 4.6,
                            'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                            'created_at' => date('Y-m-d H:i:s', strtotime('-2 months'))
                        ]
                    ]
                ],
                'address' => [
                    'street_address' => '123 Main St',
                    'city' => 'Anytown',
                    'state' => 'CA',
                    'postal_code' => '12345',
                    'phone_number' => '555-123-4567'
                ]
            ],
            [
                'id' => 1002,
                'user_id' => 2,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'status' => 'processing',
                'order_type' => 'pickup',
                'subtotal' => 35.00,
                'discount_amount' => 0,
                'delivery_fee' => 0,
                'total_price' => 35.00,
                'customer_name' => 'Jane Smith',
                'pickup_time' => date('Y-m-d', strtotime('+1 day')) . ' 15:00:00',
                'pickup_instructions' => 'Call when arrived',
                'items' => [
                    [
                        'id' => 2003,
                        'order_id' => 1002,
                        'product_id' => 103,
                        'quantity' => 1,
                        'price' => 35.00,
                        'product' => [
                            'id' => 103,
                            'name' => 'Birthday Cake',
                            'description' => 'Custom decorated birthday cake',
                            'price' => 35.00,
                            'sale_price' => null,
                            'category_id' => 1,
                            'stock' => 8,
                            'tag' => 'cake,birthday,custom',
                            'image' => 'birthday_cake.jpg',
                            'average_rating' => 4.9,
                            'updated_at' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
                            'created_at' => date('Y-m-d H:i:s', strtotime('-4 months'))
                        ]
                    ]
                ]
            ],
            [
                'id' => 1003,
                'user_id' => 3,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'pending',
                'order_type' => 'delivery',
                'subtotal' => 18.50,
                'discount_amount' => 0,
                'delivery_fee' => 3.99,
                'total_price' => 22.49,
                'customer_name' => 'Robert Johnson',
                'items' => [
                    [
                        'id' => 2004,
                        'order_id' => 1003,
                        'product_id' => 104,
                        'quantity' => 1,
                        'price' => 18.50,
                        'product' => [
                            'id' => 104,
                            'name' => 'Cookie Box',
                            'description' => 'Assorted cookies in a gift box',
                            'price' => 18.50,
                            'sale_price' => null,
                            'category_id' => 3,
                            'stock' => 20,
                            'tag' => 'cookies,gift',
                            'image' => 'cookie_box.jpg',
                            'average_rating' => 4.7,
                            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 month')),
                            'created_at' => date('Y-m-d H:i:s', strtotime('-5 months'))
                        ]
                    ]
                ],
                'address' => [
                    'street_address' => '456 Oak Ave',
                    'city' => 'Sometown',
                    'state' => 'NY',
                    'postal_code' => '67890',
                    'phone_number' => '555-987-6543'
                ]
            ]
        ];
    }

} catch (Exception $e) {
    log_message("Exception: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Return JSON response
log_message("Returning " . count($orders) . " orders");
header('Content-Type: application/json');
echo json_encode($orders);
?>