<?php
// Process address addition if submitted
if (isset($_POST['add_address'])) {
    try {
        $street_address = $_POST['street_address'];
        $city = $_POST['city'];
        $state = isset($_POST['state']) ? $_POST['state'] : '';
        $postal_code = isset($_POST['postal_code']) ? $_POST['postal_code'] : '';
        $phone_number = $_POST['phone_number'];
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // Insert new address
        $query = "INSERT INTO user_addresses (user_id, street_address, city, state, postal_code, phone_number, is_default) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssssi", $user_id, $street_address, $city, $state, $postal_code, $phone_number, $is_default);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        }
        
        // If marked as default, update all other addresses to not be default
        if ($is_default) {
            $address_id = $conn->insert_id;
            $query = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $user_id, $address_id);
            $stmt->execute();
        }
        
        $_SESSION['message'] = "Address added successfully!";
        header("Location: account.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding address: " . $e->getMessage();
    }
}

// FIXED: Process address deletion if submitted
if (isset($_POST['delete_address']) && isset($_POST['address_id'])) {
    try {
        $address_id = (int)$_POST['address_id'];
        
        // Validate address ID
        if ($address_id <= 0) {
            throw new Exception("Invalid address ID");
        }
        
        // First, verify the address belongs to the current user
        $query = "SELECT id FROM user_addresses WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Address not found or you don't have permission to delete it");
        }
        
        // Delete the address
        $query = "DELETE FROM user_addresses WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $address_id, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete address: " . $stmt->error);
        }
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = "Address deleted successfully!";
        } else {
            throw new Exception("No address was deleted");
        }
        
        $stmt->close();
        
        // Redirect to prevent form resubmission
        header("Location: account.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting address: " . $e->getMessage();
        // Redirect even on error to prevent form resubmission
        header("Location: account.php");
        exit();
    }
}

// OPTIONAL: Process address update if submitted (you can add this later)
if (isset($_POST['update_address'])) {
    try {
        $address_id = (int)$_POST['address_id'];
        $street_address = $_POST['street_address'];
        $city = $_POST['city'];
        $state = isset($_POST['state']) ? $_POST['state'] : '';
        $postal_code = isset($_POST['postal_code']) ? $_POST['postal_code'] : '';
        $phone_number = $_POST['phone_number'];
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // Update address
        $query = "UPDATE user_addresses SET street_address = ?, city = ?, state = ?, postal_code = ?, phone_number = ?, is_default = ? 
                  WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssiI", $street_address, $city, $state, $postal_code, $phone_number, $is_default, $address_id, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update address: " . $stmt->error);
        }
        
        // If marked as default, update all other addresses to not be default
        if ($is_default) {
            $query = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $user_id, $address_id);
            $stmt->execute();
        }
        
        $_SESSION['message'] = "Address updated successfully!";
        header("Location: account.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating address: " . $e->getMessage();
    }
}

// Fetch user addresses
$user_addresses = [];
$default_address = null;
if ($is_logged_in) {
    try {
        $query = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $user_addresses[] = $row;
            if ($row['is_default'] == 1) {
                $default_address = $row;
            }
        }
        $stmt->close();
        
        // If no default address set, use the first one as default if available
        if (!$default_address && !empty($user_addresses)) {
            $default_address = $user_addresses[0];
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error retrieving addresses: " . $e->getMessage();
    }
}
?>