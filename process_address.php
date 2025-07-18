<?php
// Prevent direct access
if (!defined('SITE_ROOT')) {
    die("Direct access not allowed");
}

/**
 * Process address-related actions (add, edit, delete)
 */
class AddressManager {
    private $conn; // Database connection

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    /**
     * Add a new user address
     * 
     * @param array $address_data Address details to be added
     * @return array Response with success status and message
     */
    public function addAddress($address_data) {
        // Validate input
        $validation_result = $this->validateAddressData($address_data);
        if (!$validation_result['valid']) {
            return [
                'success' => false,
                'message' => $validation_result['message']
            ];
        }

        // Prepare SQL query
        $query = "INSERT INTO user_addresses (
            user_id, 
            first_name, 
            last_name, 
            street_address, 
            city, 
            state, 
            zip_code, 
            country, 
            phone_number, 
            is_default
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            // Begin transaction to handle default address
            $this->conn->begin_transaction();

            // If setting as default, remove previous default
            if (isset($address_data['is_default']) && $address_data['is_default']) {
                $this->resetDefaultAddress($_SESSION['user_id']);
            }

            // Prepare and execute statement
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param(
                "issssssssi", 
                $_SESSION['user_id'],
                $address_data['first_name'] ?? '',
                $address_data['last_name'] ?? '',
                $address_data['street_address'],
                $address_data['city'],
                $address_data['state'] ?? '',
                $address_data['zip_code'] ?? '',
                $address_data['country'] ?? '',
                $address_data['phone_number'],
                $address_data['is_default'] ? 1 : 0
            );

            $result = $stmt->execute();

            if ($result) {
                $this->conn->commit();
                return [
                    'success' => true,
                    'message' => 'Address added successfully',
                    'address_id' => $this->conn->insert_id
                ];
            } else {
                $this->conn->rollback();
                return [
                    'success' => false,
                    'message' => 'Failed to add address: ' . $stmt->error
                ];
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Address add error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An unexpected error occurred'
            ];
        }
    }

    /**
     * Update an existing address
     * 
     * @param array $address_data Address details to be updated
     * @return array Response with success status and message
     */
    public function editAddress($address_data) {
        // Validate input
        $validation_result = $this->validateAddressData($address_data);
        if (!$validation_result['valid']) {
            return [
                'success' => false,
                'message' => $validation_result['message']
            ];
        }

        // Ensure the address belongs to the current user
        $check_query = "SELECT id FROM user_addresses WHERE id = ? AND user_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bind_param("ii", $address_data['address_id'], $_SESSION['user_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'Address not found or unauthorized'
            ];
        }

        // Prepare update query
        $query = "UPDATE user_addresses SET 
            first_name = ?, 
            last_name = ?, 
            street_address = ?, 
            city = ?, 
            state = ?, 
            zip_code = ?, 
            country = ?, 
            phone_number = ?, 
            is_default = ?
            WHERE id = ?";

        try {
            // Begin transaction to handle default address
            $this->conn->begin_transaction();

            // If setting as default, remove previous default
            if (isset($address_data['is_default']) && $address_data['is_default']) {
                $this->resetDefaultAddress($_SESSION['user_id']);
            }

            // Prepare and execute statement
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param(
                "ssssssssii", 
                $address_data['first_name'] ?? '',
                $address_data['last_name'] ?? '',
                $address_data['street_address'],
                $address_data['city'],
                $address_data['state'] ?? '',
                $address_data['zip_code'] ?? '',
                $address_data['country'] ?? '',
                $address_data['phone_number'],
                $address_data['is_default'] ? 1 : 0,
                $address_data['address_id']
            );

            $result = $stmt->execute();

            if ($result) {
                $this->conn->commit();
                return [
                    'success' => true,
                    'message' => 'Address updated successfully'
                ];
            } else {
                $this->conn->rollback();
                return [
                    'success' => false,
                    'message' => 'Failed to update address: ' . $stmt->error
                ];
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Address update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An unexpected error occurred'
            ];
        }
    }

    /**
     * Delete an address
     * 
     * @param int $address_id ID of address to delete
     * @return array Response with success status and message
     */
    public function deleteAddress($address_id) {
        // Validate input
        if (!is_numeric($address_id)) {
            return [
                'success' => false,
                'message' => 'Invalid address ID'
            ];
        }

        // Prepare delete query
        $query = "DELETE FROM user_addresses WHERE id = ? AND user_id = ?";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $address_id, $_SESSION['user_id']);
            $result = $stmt->execute();

            if ($result && $stmt->affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => 'Address deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Address not found or could not be deleted'
                ];
            }
        } catch (Exception $e) {
            error_log("Address delete error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An unexpected error occurred'
            ];
        }
    }

    /**
     * Reset default address for a user
     * 
     * @param int $user_id User ID
     */
    private function resetDefaultAddress($user_id) {
        $reset_query = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
        $reset_stmt = $this->conn->prepare($reset_query);
        $reset_stmt->bind_param("i", $user_id);
        $reset_stmt->execute();
    }

    /**
     * Validate address data before processing
     * 
     * @param array $data Address data to validate
     * @return array Validation result
     */
    private function validateAddressData($data) {
        // Required fields validation
        $required_fields = [
            'street_address' => 'Street Address',
            'city' => 'City',
            'phone_number' => 'Phone Number'
        ];

        foreach ($required_fields as $field => $label) {
            if (empty(trim($data[$field] ?? ''))) {
                return [
                    'valid' => false,
                    'message' => "The {$label} field is required."
                ];
            }
        }

        // Phone number validation (basic format check)
        if (!preg_match('/^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/', $data['phone_number'])) {
            return [
                'valid' => false,
                'message' => 'Invalid phone number format.'
            ];
        }

        return [
            'valid' => true
        ];
    }
}

// Handle address actions via AJAX or form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure database connection and user session
    require_once 'db_connection.php';
    session_start();

    // Instantiate AddressManager
    $address_manager = new AddressManager($conn);

    // Determine action type
    $action = $_POST['action'] ?? $_POST['add_address'] ?? $_POST['edit_address'] ?? $_POST['delete_address'] ?? null;

    // Process the action
    switch ($action) {
        case 'add_address':
            // Remove unnecessary fields or sanitize input as needed
            $result = $address_manager->addAddress($_POST);
            break;

        case 'edit_address':
            $result = $address_manager->editAddress($_POST);
            break;

        case 'delete_address':
            // For AJAX delete requests
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $address_manager->deleteAddress($data['address_id'] ?? $_POST['address_id']);
            break;

        default:
            $result = [
                'success' => false,
                'message' => 'Invalid action'
            ];
    }

    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>