<?php require_once './includes/check_admin.php';?>
<!-- Dashboard Cards -->
<div class="dashboard-cards">
    <div class="card">
        <div class="card-icon">📦</div>
        <h3 class="card-title">Products Sold</h3>
        <?php
        require_once './includes/check_admin.php';
        require_once __DIR__ . "/../../includes/db.php";
        if (!$conn) {
            die("Database connection failed: " . mysqli_connect_error());
        }
        
        // Get products sold in the past month
        $products_sold_query = "SELECT SUM(quantity) as total_sold 
                               FROM order_items oi
                               JOIN orders o ON oi.order_id = o.id
                               WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $prev_month_products_sold_query = "SELECT SUM(quantity) as total_sold 
                                         FROM order_items oi
                                         JOIN orders o ON oi.order_id = o.id
                                         WHERE o.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 2 MONTH) AND DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        
        // Added error handling for database queries
        $products_sold_result = mysqli_query($conn, $products_sold_query) or die("Error fetching products sold: " . mysqli_error($conn));
        $prev_month_products_sold_result = mysqli_query($conn, $prev_month_products_sold_query) or die("Error fetching previous month products: " . mysqli_error($conn));
        
        $products_sold = mysqli_fetch_assoc($products_sold_result)['total_sold'] ?? 0;
        $prev_month_products_sold = mysqli_fetch_assoc($prev_month_products_sold_result)['total_sold'] ?? 0;
        
        // Calculate percentage change
        $products_percentage = 0;
        if ($prev_month_products_sold > 0) {
            $products_percentage = (($products_sold - $prev_month_products_sold) / $prev_month_products_sold) * 100;
        }
        
        $products_class = $products_percentage >= 0 ? 'positive' : 'negative';
        $products_arrow = $products_percentage >= 0 ? '↑' : '↓';
        ?>
        <div class="card-value"><?php echo number_format($products_sold); ?></div>
        <div class="card-stat <?php echo $products_class; ?>"><?php echo $products_arrow; ?> <?php echo abs(number_format($products_percentage, 1)); ?>% from last month</div>
    </div>
    
    <div class="card">
        <div class="card-icon">👥</div>
        <h3 class="card-title">New Customers</h3>
        <?php
        // Get new users in the past month
        $new_users_query = "SELECT COUNT(*) as count 
                           FROM users
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $prev_month_users_query = "SELECT COUNT(*) as count 
                                 FROM users
                                 WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 2 MONTH) AND DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        
        // Added error handling
        $new_users_result = mysqli_query($conn, $new_users_query) or die("Error fetching new users: " . mysqli_error($conn));
        $prev_month_users_result = mysqli_query($conn, $prev_month_users_query) or die("Error fetching previous month users: " . mysqli_error($conn));
        
        $new_users = mysqli_fetch_assoc($new_users_result)['count'] ?? 0;
        $prev_month_users = mysqli_fetch_assoc($prev_month_users_result)['count'] ?? 0;
        
        // Calculate percentage change
        $users_percentage = 0;
        if ($prev_month_users > 0) {
            $users_percentage = (($new_users - $prev_month_users) / $prev_month_users) * 100;
        }
        
        $users_class = $users_percentage >= 0 ? 'positive' : 'negative';
        $users_arrow = $users_percentage >= 0 ? '↑' : '↓';
        ?>
        <div class="card-value"><?php echo number_format($new_users); ?></div>
        <div class="card-stat <?php echo $users_class; ?>"><?php echo $users_arrow; ?> <?php echo abs(number_format($users_percentage, 1)); ?>% from last month</div>
    </div>
    
    <div class="card">
        <div class="card-icon">💰</div>
        <h3 class="card-title">Revenue</h3>
        <?php
        // Get revenue for the past month (sum of all completed orders)
        $revenue_query = "SELECT SUM(total_price) as total_revenue 
                        FROM orders
                        WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $prev_month_revenue_query = "SELECT SUM(total_price) as total_revenue 
                                FROM orders
                                WHERE status = 'completed' AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 2 MONTH) AND DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        
        $revenue_result = mysqli_query($conn, $revenue_query) or die("Error fetching revenue: " . mysqli_error($conn));
        $prev_month_revenue_result = mysqli_query($conn, $prev_month_revenue_query) or die("Error fetching previous month revenue: " . mysqli_error($conn));
        
        $revenue = mysqli_fetch_assoc($revenue_result)['total_revenue'] ?? 0;
        $prev_month_revenue = mysqli_fetch_assoc($prev_month_revenue_result)['total_revenue'] ?? 0;
        
        // Calculate percentage change
        $revenue_percentage = 0;
        if ($prev_month_revenue > 0) {
            $revenue_percentage = (($revenue - $prev_month_revenue) / $prev_month_revenue) * 100;
        }
        
        $revenue_class = $revenue_percentage >= 0 ? 'positive' : 'negative';
        $revenue_arrow = $revenue_percentage >= 0 ? '↑' : '↓';
        ?>
        <div class="card-value">$<?php echo number_format($revenue, 2); ?></div>
        <div class="card-stat <?php echo $revenue_class; ?>"><?php echo $revenue_arrow; ?> <?php echo abs(number_format($revenue_percentage, 1)); ?>% from last month</div>
    </div>
    
    <div class="card">
        <div class="card-icon">🛒</div>
        <h3 class="card-title">Number of Orders</h3>
        <?php
        // Get total number of orders for the past month
        $orders_query = "SELECT COUNT(*) as order_count 
                        FROM orders
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $prev_month_orders_query = "SELECT COUNT(*) as order_count 
                                FROM orders
                                WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 2 MONTH) AND DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        
        $orders_result = mysqli_query($conn, $orders_query) or die("Error fetching orders: " . mysqli_error($conn));
        $prev_month_orders_result = mysqli_query($conn, $prev_month_orders_query) or die("Error fetching previous month orders: " . mysqli_error($conn));
        
        $order_count = mysqli_fetch_assoc($orders_result)['order_count'] ?? 0;
        $prev_month_order_count = mysqli_fetch_assoc($prev_month_orders_result)['order_count'] ?? 0;
        
        // Calculate percentage change
        $orders_percentage = 0;
        if ($prev_month_order_count > 0) {
            $orders_percentage = (($order_count - $prev_month_order_count) / $prev_month_order_count) * 100;
        }
        
        $orders_class = $orders_percentage >= 0 ? 'positive' : 'negative';
        $orders_arrow = $orders_percentage >= 0 ? '↑' : '↓';
        ?>
        <div class="card-value"><?php echo number_format($order_count); ?></div>
        <div class="card-stat <?php echo $orders_class; ?>"><?php echo $orders_arrow; ?> <?php echo abs(number_format($orders_percentage, 1)); ?>% from last month</div>
    </div>
</div>
<style>
        /* Enhanced Inventory Summary - Evenly Distributed Grid */
        .inventory-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: clamp(16px, 2.5vw, 24px);
            margin-bottom: 32px;
            padding: 0;
            width: 100%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .inventory-stat {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: clamp(20px, 3.5vw, 28px);
            border-radius: 20px;
            background: linear-gradient(135deg, #ffffff 0%, #fdf2f8 100%);
            box-shadow: 0 8px 32px rgba(236, 72, 153, 0.08);
            border: 1px solid rgba(236, 72, 153, 0.15);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            min-height: clamp(90px, 15vw, 110px);
            width: 100%;
            box-sizing: border-box;
        }

        .inventory-stat:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 48px rgba(236, 72, 153, 0.15);
            background: linear-gradient(135deg, #fefefe 0%, #fce7f3 100%);
        }

        .inventory-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ec4899 0%, #f472b6 100%);
            opacity: 0.8;
        }

        .inventory-icon {
            font-size: clamp(1.8rem, 4vw, 2.4rem);
            margin-right: clamp(16px, 2.5vw, 20px);
            padding: clamp(12px, 2vw, 16px);
            border-radius: 16px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: clamp(56px, 8vw, 64px);
            height: clamp(56px, 8vw, 64px);
            transition: all 0.3s ease;
        }

        .inventory-icon.in-stock {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid rgba(21, 87, 36, 0.2);
        }

        .inventory-icon.low-stock {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 2px solid rgba(133, 100, 4, 0.2);
        }

        .inventory-icon.out-of-stock {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid rgba(114, 28, 36, 0.2);
        }

        .inventory-info {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .inventory-info h4 {
            font-size: clamp(1.1rem, 2.2vw, 1.3rem);
            font-weight: 700;
            color: #2c3e50;
            margin: 0 0 6px 0;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .inventory-info p {
            font-size: clamp(0.9rem, 1.8vw, 1rem);
            color: #6c757d;
            margin: 0;
            font-weight: 600;
            line-height: 1.3;
            word-wrap: break-word;
        }

        /* Responsive breakpoints */
        @media (max-width: 768px) {
            .inventory-summary {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .inventory-stat {
                min-height: 80px;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .inventory-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1025px) {
            .inventory-summary {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Animation for numbers */
        .inventory-info p {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Container styling for demo */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
    </style>

<!-- Charts Container -->
<div class="charts-container">
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Sales Overview</h3>
            <div class="chart-actions">
                <button class="chart-btn active" data-range="weekly">Weekly</button>
                <button class="chart-btn" data-range="monthly">Monthly</button>
                <button class="chart-btn" data-range="yearly">Yearly</button>
            </div>
        </div>
        <div class="chart-body">
            <canvas id="salesChart" style="height: 250px; width: 100%;"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">Top Categories</h3>
        </div>
        <div class="chart-body">
            <canvas id="categoriesChart" style="height: 250px; width: 100%;"></canvas>
        </div>
    </div>
</div>

<!-- Stock Status Section -->
<div class="chart-card">
    <div class="chart-header">
        <h3 class="chart-title">Inventory Status</h3>
        <a href="index.php?page=products" class="view-all">Manage Inventory</a>
    </div>
    <div class="inventory-summary">
        <?php
        // Get inventory status counts
        $out_of_stock_query = "SELECT COUNT(*) as count FROM products WHERE stock = 0";
        $low_stock_query = "SELECT COUNT(*) as count FROM products WHERE stock > 0 AND stock <= 10";
        $in_stock_query = "SELECT COUNT(*) as count FROM products WHERE stock > 10";
        
        // Added error handling
        $out_of_stock_result = mysqli_query($conn, $out_of_stock_query) or die("Error fetching out of stock: " . mysqli_error($conn));
        $low_stock_result = mysqli_query($conn, $low_stock_query) or die("Error fetching low stock: " . mysqli_error($conn));
        $in_stock_result = mysqli_query($conn, $in_stock_query) or die("Error fetching in stock: " . mysqli_error($conn));
        
        $out_of_stock = mysqli_fetch_assoc($out_of_stock_result)['count'];
        $low_stock = mysqli_fetch_assoc($low_stock_result)['count'];
        $in_stock = mysqli_fetch_assoc($in_stock_result)['count'];
        ?>
        
        <div class="inventory-stat">
            <div class="inventory-icon in-stock">✓</div>
            <div class="inventory-info">
                <h4>In Stock</h4>
                <p><?php echo $in_stock; ?> products</p>
            </div>
        </div>
        
        <div class="inventory-stat">
            <div class="inventory-icon low-stock">⚠️</div>
            <div class="inventory-info">
                <h4>Low Stock</h4>
                <p><?php echo $low_stock; ?> products</p>
            </div>
        </div>
        
        <div class="inventory-stat">
            <div class="inventory-icon out-of-stock">✗</div>
            <div class="inventory-info">
                <h4>Out of Stock</h4>
                <p><?php echo $out_of_stock; ?> products</p>
            </div>
        </div>
    </div>
    
    <!-- Inventory Products Display - 3 products for each status -->
    <div class="inventory-products-section">
        <?php
        // Get 3 products for each stock status
        $in_stock_products_query = "SELECT id, name, image, stock, price FROM products WHERE stock > 10 ORDER BY stock DESC LIMIT 3";
        $low_stock_products_query = "SELECT id, name, image, stock, price FROM products WHERE stock > 0 AND stock <= 10 ORDER BY stock ASC LIMIT 3";
        $out_of_stock_products_query = "SELECT id, name, image, stock, price FROM products WHERE stock = 0 ORDER BY name ASC LIMIT 3";
        
        $in_stock_products_result = mysqli_query($conn, $in_stock_products_query) or die("Error fetching in stock products: " . mysqli_error($conn));
        $low_stock_products_result = mysqli_query($conn, $low_stock_products_query) or die("Error fetching low stock products: " . mysqli_error($conn));
        $out_of_stock_products_result = mysqli_query($conn, $out_of_stock_products_query) or die("Error fetching out of stock products: " . mysqli_error($conn));
        
        // Function to display product cards
        function displayProductCards($result, $status_class, $status_text) {
            if (mysqli_num_rows($result) > 0) {
                while ($product = mysqli_fetch_assoc($result)) {
                    ?>
                    <div class="product-card">
                        <div class="product-img">
                            <?php 
                            $image_path = $product['image'];
                            echo '<img src="./uploads/products/' . htmlspecialchars($image_path) . '" alt="' . htmlspecialchars($product['name']) . '" class="product-img" onerror="this.onerror=null; this.src=\'/assets/img/placeholder.png\'; this.classList.add(\'missing-img\');">';
                            ?>
                        </div>
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-meta">
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php 
                                    if ($product['stock'] == 0) {
                                        echo 'Out of Stock';
                                    } else {
                                        echo (int)$product['stock'] . ' left';
                                    }
                                    ?>
                                </span>
                                <span>$<?php echo number_format((float)$product['price'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p class='no-products'>No " . strtolower($status_text) . " products</p>";
            }
        }
        ?>
        
        <!-- In Stock Products -->
        <div class="stock-section">
            <div class="product-list">
                <?php displayProductCards($in_stock_products_result, 'badge-success', 'In Stock'); ?>
            </div>
        </div>
        
        <!-- Low Stock Products -->
        <div class="stock-section">
            <div class="product-list">
                <?php displayProductCards($low_stock_products_result, 'badge-warning', 'Low Stock'); ?>
            </div>
        </div>
        
        <!-- Out of Stock Products -->
        <div class="stock-section">
            <div class="product-list">
                <?php displayProductCards($out_of_stock_products_result, 'badge-danger', 'Out of Stock'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="chart-card">
    <div class="chart-header">
        <h3 class="chart-title">Recent Activity</h3>
    </div>
    <div class="activity-timeline">
        <?php
        // Get recent orders
        $recent_orders_query = "SELECT o.id, u.first_name, u.last_name, o.total_price, o.created_at 
                               FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               ORDER BY o.created_at DESC LIMIT 3";
        $recent_orders_result = mysqli_query($conn, $recent_orders_query) or die("Error fetching recent orders: " . mysqli_error($conn));
        
        // Get recent reviews
        $recent_reviews_query = "SELECT r.id, u.first_name, u.last_name, r.rating, p.name as product_name, r.created_at 
                                FROM ratings r 
                                JOIN users u ON r.user_id = u.id 
                                JOIN products p ON r.product_id = p.id 
                                ORDER BY r.created_at DESC LIMIT 3";
        $recent_reviews_result = mysqli_query($conn, $recent_reviews_query) or die("Error fetching recent reviews: " . mysqli_error($conn));
        
        // Get recent messages
        $recent_messages_query = "SELECT message_id, name, subject, submission_date 
                                 FROM messages 
                                 ORDER BY submission_date DESC LIMIT 3";
        $recent_messages_result = mysqli_query($conn, $recent_messages_query) or die("Error fetching recent messages: " . mysqli_error($conn));
        
        // Combine all activities and sort by date
        $activities = [];
        
        while ($order = mysqli_fetch_assoc($recent_orders_result)) {
            $activities[] = [
                'type' => 'order',
                'data' => $order,
                'date' => strtotime($order['created_at'])
            ];
        }
        
        while ($review = mysqli_fetch_assoc($recent_reviews_result)) {
            $activities[] = [
                'type' => 'review',
                'data' => $review,
                'date' => strtotime($review['created_at'])
            ];
        }
        
        while ($message = mysqli_fetch_assoc($recent_messages_result)) {
            $activities[] = [
                'type' => 'message',
                'data' => $message,
                'date' => strtotime($message['submission_date'])
            ];
        }
        
        // Sort activities by date (newest first)
        usort($activities, function($a, $b) {
            return $b['date'] - $a['date'];
        });
        
        // Display activities
        if (!empty($activities)) {
            foreach ($activities as $activity) {
                $time = date('M d, g:i a', $activity['date']);
                
                switch ($activity['type']) {
                    case 'order':
                        $data = $activity['data'];
                        echo '
                        <div class="activity-item">
                            <div class="activity-icon order">🛒</div>
                            <div class="activity-content">
                                <div class="activity-title">New Order #' . (int)$data['id'] . '</div>
                                <div class="activity-details">
                                    ' . htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) . ' placed an order for $' . number_format((float)$data['total_price'], 2) . '
                                </div>
                                <div class="activity-time">' . $time . '</div>
                            </div>
                        </div>';
                        break;
                    
                    case 'review':
                        $data = $activity['data'];
                        $rating = min(5, max(0, (int)$data['rating'])); // Ensure rating is between 0-5
                        $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                        echo '
                        <div class="activity-item">
                            <div class="activity-icon review">⭐</div>
                            <div class="activity-content">
                                <div class="activity-title">New Review</div>
                                <div class="activity-details">
                                    ' . htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) . ' rated ' . htmlspecialchars($data['product_name']) . '
                                    <div class="stars">' . $stars . '</div>
                                </div>
                                <div class="activity-time">' . $time . '</div>
                            </div>
                        </div>';
                        break;
                    
                    case 'message':
                        $data = $activity['data'];
                        echo '
                        <div class="activity-item">
                            <div class="activity-icon message">✉️</div>
                            <div class="activity-content">
                                <div class="activity-title">New Message</div>
                                <div class="activity-details">
                                    ' . htmlspecialchars($data['name']) . ' sent a message: "' . htmlspecialchars($data['subject']) . '"
                                </div>
                                <div class="activity-time">' . $time . '</div>
                            </div>
                        </div>';
                        break;
                }
            }
        } else {
            echo "<p class='no-activity'>No recent activity</p>";
        }
        ?>
    </div>
</div>

<!-- Get category and sales data for charts -->
<?php
// Get category distribution data
$categories_query = "SELECT c.name, COUNT(p.id) as product_count 
                    FROM categories c 
                    LEFT JOIN products p ON c.id = p.category_id 
                    GROUP BY c.id 
                    ORDER BY product_count DESC 
                    LIMIT 5";
$categories_result = mysqli_query($conn, $categories_query) or die("Error fetching categories: " . mysqli_error($conn));

$category_names = [];
$category_counts = [];

while ($row = mysqli_fetch_assoc($categories_result)) {
    $category_names[] = $row['name'];
    $category_counts[] = (int)$row['product_count'];
}

// Convert to JSON for JS
$category_names_json = json_encode(empty($category_names) ? ['No Data'] : $category_names);
$category_counts_json = json_encode(empty($category_counts) ? [1] : $category_counts);

// Get weekly sales data
$weekly_sales_query = "SELECT 
                      DATE_FORMAT(created_at, '%a') as day,
                      SUM(total_price) as daily_sales
                      FROM orders
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
                      GROUP BY DATE_FORMAT(created_at, '%w')
                      ORDER BY DATE_FORMAT(created_at, '%w')";
$weekly_sales_result = mysqli_query($conn, $weekly_sales_query) or die("Error fetching weekly sales: " . mysqli_error($conn));

$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$weekly_sales = array_fill(0, 7, 0);

while ($row = mysqli_fetch_assoc($weekly_sales_result)) {
    $day_index = array_search($row['day'], $days);
    if ($day_index !== false) {
        $weekly_sales[$day_index] = (float)$row['daily_sales'];
    }
}

$weekly_sales_json = json_encode($weekly_sales);

// Get monthly sales data
$monthly_sales_query = "SELECT 
                       DATE_FORMAT(created_at, '%b') as month,
                       SUM(total_price) as monthly_sales
                       FROM orders
                       WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
                       GROUP BY DATE_FORMAT(created_at, '%m')
                       ORDER BY DATE_FORMAT(created_at, '%m')";
$monthly_sales_result = mysqli_query($conn, $monthly_sales_query) or die("Error fetching monthly sales: " . mysqli_error($conn));

$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthly_sales = array_fill(0, 12, 0);

while ($row = mysqli_fetch_assoc($monthly_sales_result)) {
    $month_index = array_search($row['month'], $months);
    if ($month_index !== false) {
        $monthly_sales[$month_index] = (float)$row['monthly_sales'];
    }
}

$monthly_sales_json = json_encode($monthly_sales);

// Get yearly sales data
$yearly_sales_query = "SELECT 
                      DATE_FORMAT(created_at, '%Y') as year,
                      SUM(total_price) as yearly_sales
                      FROM orders
                      GROUP BY DATE_FORMAT(created_at, '%Y')
                      ORDER BY year ASC
                      LIMIT 5";
$yearly_sales_result = mysqli_query($conn, $yearly_sales_query) or die("Error fetching yearly sales: " . mysqli_error($conn));

$years = [];
$yearly_sales = [];

while ($row = mysqli_fetch_assoc($yearly_sales_result)) {
    $years[] = $row['year'];
    $yearly_sales[] = (float)$row['yearly_sales'];
}

// If we don't have enough years, add placeholders
if (count($years) < 5) {
    $current_year = date('Y');
    for ($i = count($years); $i < 5; $i++) {
        $years[] = (string)($current_year - 5 + $i);
        $yearly_sales[] = 0;
    }
}

$years_json = json_encode($years);
$yearly_sales_json = json_encode($yearly_sales);
?>

<!-- Charts Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Sales',
                data: <?php echo $weekly_sales_json; ?>,
                borderColor: '#FF85B3',
                backgroundColor: 'rgba(255, 133, 179, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Initialize Categories Chart
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    const categoriesChart = new Chart(categoriesCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $category_names_json; ?>,
            datasets: [{
                data: <?php echo $category_counts_json; ?>,
                backgroundColor: [
                    '#FF85B3',
                    '#4A6FDC',
                    '#28c76f',
                    '#ff9f43',
                    '#00cfe8'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15,
                        padding: 15
                    }
                }
            }
        }
    });
    
    // Chart Time Range Buttons
    const chartBtns = document.querySelectorAll('.chart-btn');
    chartBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            chartBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update chart data based on selected time range
            const range = this.getAttribute('data-range');
            
            if (range === 'weekly') {
                salesChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                salesChart.data.datasets[0].data = <?php echo $weekly_sales_json; ?>;
            } else if (range === 'monthly') {
                salesChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                salesChart.data.datasets[0].data = <?php echo $monthly_sales_json; ?>;
            } else if (range === 'yearly') {
                salesChart.data.labels = <?php echo $years_json; ?>;
                salesChart.data.datasets[0].data = <?php echo $yearly_sales_json; ?>;
            }
            
            salesChart.update();
        });
    });
});
</script>