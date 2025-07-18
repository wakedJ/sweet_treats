    <?php
    /**
     * Cart Management Functions
     * 
     * These functions handle transferring a guest cart to a user's database cart
     * when they log in or register.
     */

    /**
     * Transfer cart items from session to database
     * 
     * @param mysqli $conn Database connection
     * @param int $user_id User ID to associate cart items with
     * @return bool Success or failure
     */
    function transfer_session_cart_to_db($conn, $user_id) {
        // If there are no items in the session cart, nothing to transfer
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return true;
        }
        
        try {
            // Start a transaction to ensure data integrity
            $conn->begin_transaction();
            
            // For each product in the session cart
            foreach ($_SESSION['cart'] as $product_id => $item) {
                $quantity = $item['quantity'];
                
                // Check if this product already exists in the user's cart
                $check_query = "SELECT id, quantity FROM cart_items 
                            WHERE user_id = ? AND product_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("ii", $user_id, $product_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Product exists in user cart, update quantity
                    $row = $result->fetch_assoc();
                    $new_quantity = $row['quantity'] + $quantity;
                    
                    $update_query = "UPDATE cart_items SET quantity = ? 
                                WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("ii", $new_quantity, $row['id']);
                    $update_stmt->execute();
                } else {
                    // Product doesn't exist in user cart, insert new item
                    $insert_query = "INSERT INTO cart_items (user_id, product_id, quantity) 
                                VALUES (?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                    $insert_stmt->execute();
                }
            }
            
            // Commit the transaction
            $conn->commit();
            
            // Save a copy of the cart before clearing it
            $temp_cart = $_SESSION['cart'];
            
            // Clear the session cart after successful transfer
            unset($_SESSION['cart']);
            
            // Store the cart count in the session for immediate display after login
            $_SESSION['cart_count'] = 0;
            foreach ($temp_cart as $item) {
                $_SESSION['cart_count'] += $item['quantity'];
            }
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Cart transfer error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Merge cart data between session and database
     * This can be used for more advanced cart syncing operations
     * 
     * @param mysqli $conn Database connection
     * @param int $user_id User ID
     * @return bool Success or failure
     */
    function merge_cart_data($conn, $user_id) {
        // First transfer session cart to database
        $transfer_result = transfer_session_cart_to_db($conn, $user_id);
        
        // Then load user's database cart into session for reference
        // This step is optional as most operations will query the DB directly
        
        return $transfer_result;
    }