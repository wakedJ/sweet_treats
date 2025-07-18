<?php
/**
 * Rating System Debug Script
 * Place this in your root directory to test your rating system
 */
session_start();

// Include database connection
require_once '../includes/db.php';

// Check if the database connection is established
if (!isset($conn)) {
    die("Database connection not established. Check your db.php file.");
}

// Display session information
echo "<h2>Session Information</h2>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Not Active") . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID in Session: " . ($_SESSION['user_id'] ?? "Not set") . "<br>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red'>USER NOT LOGGED IN - Ratings will fail</p>";
} else {
    echo "<p style='color:green'>User is logged in as ID: " . $_SESSION['user_id'] . "</p>";
    
    // Check if the user has any delivered orders
    $user_id = $_SESSION['user_id'];
    
    $order_sql = "
        SELECT o.id as order_id, o.status, oi.product_id, p.name as product_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE LOWER(o.status) = 'delivered'
        LIMIT 5
    ";
    
    if ($stmt = $conn->prepare($order_sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<h3>Found " . $result->num_rows . " delivered products:</h3>";
            echo "<ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>Order #" . $row['order_id'] . " - Product ID: " . $row['product_id'] . " - " . $row['product_name'] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:red'>NO DELIVERED ORDERS FOUND - Ratings will fail</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color:red'>Error preparing query: " . $conn->error . "</p>";
    }
    
    // Check if ratings table exists
    $table_sql = "SHOW TABLES LIKE 'ratings'";
    $table_result = $conn->query($table_sql);
    
    if ($table_result->num_rows > 0) {
        echo "<p style='color:green'>Ratings table exists</p>";
        
        // Display ratings table structure
        $structure_sql = "DESCRIBE ratings";
        $structure_result = $conn->query($structure_sql);
        
        if ($structure_result) {
            echo "<h3>Ratings Table Structure:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            while ($row = $structure_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . ($row['Default'] ?? "NULL") . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p style='color:red'>Error getting table structure: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>RATINGS TABLE DOES NOT EXIST - This will cause failures</p>";
        
        // Suggest creating the table
        echo "<h3>Suggested SQL to create ratings table:</h3>";
        echo "<pre>
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
        </pre>";
    }
}

// Show the location of important files
echo "<h2>Important Files Location Check</h2>";

$files_to_check = [
    'includes/db.php',
    'partials/rating_handler.php',
    'partials/orders_tab.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p style='color:green'>✓ Found: $file</p>";
    } else {
        echo "<p style='color:red'>✗ Missing: $file</p>";
    }
}

echo "<h2>Testing Rating Handler</h2>";
echo "<p>Click below to test the rating handler with a sample request:</p>";
?>

<script>
function testRatingHandler() {
    // Create form data for the AJAX request
    const formData = new FormData();
    formData.append('product_id', '1'); // Change this to an actual product ID
    formData.append('rating', '5');
    formData.append('action', 'save_rating');
    
    // Show status
    document.getElementById('testResults').innerHTML = 'Testing...';
    
    // Send AJAX request
    fetch('partials/rating_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // Get the raw text first to see if there's any error output
    .then(text => {
        document.getElementById('rawResponse').textContent = text;
        
        try {
            // Try to parse as JSON
            const data = JSON.parse(text);
            document.getElementById('testResults').innerHTML = 
                data.success ? 
                '<span style="color:green">Success: ' + data.message + '</span>' : 
                '<span style="color:red">Error: ' + data.message + '</span>';
            
            document.getElementById('parsedResponse').textContent = JSON.stringify(data, null, 2);
        } catch (e) {
            document.getElementById('testResults').innerHTML = 
                '<span style="color:red">Error: Could not parse response as JSON</span>';
        }
    })
    .catch(error => {
        document.getElementById('testResults').innerHTML = 
            '<span style="color:red">Error: ' + error.message + '</span>';
    });
}
</script>

<button onclick="testRatingHandler()">Test Rating Handler</button>
<div id="testResults"></div>

<h3>Raw Response:</h3>
<pre id="rawResponse" style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow: auto; max-height: 300px;"></pre>

<h3>Parsed JSON Response:</h3>
<pre id="parsedResponse" style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow: auto; max-height: 300px;"></pre>

<hr>

<h2>Recommendations</h2>
<ol>
    <li>Check file paths in your code</li>
    <li>Verify the database connection in includes/db.php is working</li>
    <li>Ensure the ratings table exists with the correct structure</li>
    <li>Check that you're logged in and have delivered orders to rate</li>
    <li>Update the rating_handler.php with the corrected version</li>
</ol>