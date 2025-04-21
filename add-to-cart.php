<?php
// Start the session to maintain cart state
session_start();

// Include database connection
require_once "includes/db.php";

// Initialize response array
$response = array('success' => false, 'message' => '', 'cart_count' => 0);

// Check if this is an add to cart request
if (isset($_POST['add_to_cart']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // Validate quantity
    if ($quantity <= 0) {
        $quantity = 1;
    }
    
    // Check if product exists and has stock
    $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Check if product is in stock
        if ($product['stock'] >= $quantity) {
            // Check if user is logged in
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                
                // Check if product already in cart
                $check_stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
                $check_stmt->bind_param("ii", $user_id, $product_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Update existing cart item
                    $cart_item = $check_result->fetch_assoc();
                    $new_quantity = $cart_item['quantity'] + $quantity;
                    
                    $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
                    $update_stmt->bind_param("ii", $new_quantity, $cart_item['cart_item_id']);
                    
                    if ($update_stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Cart updated successfully!';
                    } else {
                        $response['message'] = 'Failed to update cart.';
                    }
                } else {
                    // Add new cart item
                    $insert_stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
                    $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                    
                    if ($insert_stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Product added to cart!';
                    } else {
                        $response['message'] = 'Failed to add product to cart.';
                    }
                }
                
                // Get updated cart count
                $count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
                $count_stmt->bind_param("i", $user_id);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $count_row = $count_result->fetch_assoc();
                $response['cart_count'] = $count_row['total'] ? intval($count_row['total']) : 0;
                
            } else {
                // For guest users, use session cart
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = array();
                }
                
                if (isset($_SESSION['cart'][$product_id])) {
                    // Update quantity if product already in cart
                    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                } else {
                    // Add new product to cart
                    $_SESSION['cart'][$product_id] = array(
                        'product_id' => $product_id,
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'quantity' => $quantity
                    );
                }
                
                $response['success'] = true;
                $response['message'] = 'Product added to cart!';
                
                // Calculate cart count for session cart
                $cart_count = 0;
                foreach ($_SESSION['cart'] as $item) {
                    $cart_count += $item['quantity'];
                }
                $response['cart_count'] = $cart_count;
            }
        } else {
            $response['message'] = 'Sorry, this product is out of stock.';
        }
    } else {
        $response['message'] = 'Product not found.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>