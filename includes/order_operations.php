<?php
// Get user's order history
$orders = [];
if ($is_logged_in) {
    try {
        $query = "SELECT o.*, 
                 (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                 FROM orders o 
                 WHERE o.user_id = ? 
                 ORDER BY o.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Also fetch order items for each order
            $order_id = $row['id'];
            $items_query = "SELECT oi.*, p.name, p.image as image_url FROM order_items oi 
                           JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = ?";
            $items_stmt = $conn->prepare($items_query);
            $items_stmt->bind_param("i", $order_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            $items = [];
            while ($item = $items_result->fetch_assoc()) {
                // Get rating if exists
                $rating_query = "SELECT rating FROM ratings WHERE user_id = ? AND product_id = ?";
                $rating_stmt = $conn->prepare($rating_query);
                $rating_stmt->bind_param("ii", $user_id, $item['product_id']);
                $rating_stmt->execute();
                $rating_result = $rating_stmt->get_result();
                
                if ($rating_result->num_rows > 0) {
                    $rating = $rating_result->fetch_assoc();
                    $item['rating'] = $rating['rating'];
                }
                $rating_stmt->close();
                
                $items[] = $item;
            }
            
            $row['items'] = $items;
            $orders[] = $row;
            
            $items_stmt->close();
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error retrieving orders: " . $e->getMessage();
    }
}
?>