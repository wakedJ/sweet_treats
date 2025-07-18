<?php

// Pure AJAX endpoint that only returns JSON
header('Content-Type: application/json');

// Turn off display errors to prevent HTML error messages in JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Initialize response variable
$response = ['success' => false, 'message' => 'Invalid request method or action'];

// Include your database connection file
require_once '../../includes/db.php';

// Handle different actions
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'get':
        // Get categories with pagination and filtering
        try {
            // Get pagination parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Get filtering parameters
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
            
            // Build WHERE clause
            $where_clause = "";
            $params = [];
            $types = "";
            
            if (!empty($search)) {
                $where_clause .= " WHERE (name LIKE ? OR description LIKE ?)";
                $search_param = "%{$search}%";
                $params[] = $search_param;
                $params[] = $search_param;
                $types .= "ss";
            }
            
            if ($status !== 'all') {
                $status_value = ($status === 'active') ? 1 : 0;
                if (empty($where_clause)) {
                    $where_clause .= " WHERE status = ?";
                } else {
                    $where_clause .= " AND status = ?";
                }
                $params[] = $status_value;
                $types .= "i";
            }
            
            // Count total records for pagination
            $count_sql = "SELECT COUNT(*) as total FROM categories" . $where_clause;
            $count_stmt = $conn->prepare($count_sql);
            
            if (!empty($params)) {
                $count_stmt->bind_param($types, ...$params);
            }
            
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $total_records = $count_result->fetch_assoc()['total'];
            $total_pages = ceil($total_records / $limit);
            
            // Query categories with product count and pagination
            $sql = "SELECT c.*, 
                   (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count 
                   FROM categories c" . $where_clause . " 
                   ORDER BY c.name ASC LIMIT ?, ?";
            
            $stmt = $conn->prepare($sql);
            
            // Add limit parameters
            $params[] = $offset;
            $params[] = $limit;
            $types .= "ii";
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = [
                    'id' => $row['id'],
                    'name' => htmlspecialchars($row['name']),
                    'description' => $row['description'] ? htmlspecialchars($row['description']) : null,
                    'status' => $row['status'],
                    'product_count' => $row['product_count']
                ];
            }
            
            $response = [
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'categories' => $categories,
                'total_records' => $total_records,
                'current_page' => $page,
                'total_pages' => $total_pages
            ];
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Error retrieving categories: ' . $e->getMessage()
            ];
        }
        break;
        
    case 'get_single':
        // Get a single category by ID
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                throw new Exception("Invalid category ID");
            }
            
            $sql = "SELECT * FROM categories WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Category not found");
            }
            
            $category = $result->fetch_assoc();
            
            $response = [
                'success' => true,
                'message' => 'Category retrieved successfully',
                'category' => $category
            ];
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Error retrieving category: ' . $e->getMessage()
            ];
        }
        break;
        
    case 'update':
        // Update a category
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : null;
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
            
            if ($id <= 0) {
                throw new Exception("Invalid category ID");
            }
            
            if (empty($name)) {
                throw new Exception("Category name is required");
            }
            
            // Check if the category exists
            $check_sql = "SELECT id FROM categories WHERE id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                throw new Exception("Category not found");
            }

            // Check if the name already exists for another category
            $name_check_sql = "SELECT id FROM categories WHERE name = ? AND id != ?";
            $name_check_stmt = $conn->prepare($name_check_sql);
            $name_check_stmt->bind_param("si", $name, $id);
            $name_check_stmt->execute();
            $name_check_result = $name_check_stmt->get_result();
            
            if ($name_check_result->num_rows > 0) {
                throw new Exception("A category with this name already exists");
            }
            
            // Update the category
            $sql = "UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $name, $description, $status, $id);
            
            if ($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'Category updated successfully',
                    'category_id' => $id
                ];
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Error updating category: ' . $e->getMessage()
            ];
        }
        break;
        
    case 'delete':
        // Delete a category
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            
            if ($id <= 0) {
                throw new Exception("Invalid category ID");
            }
            
            // Check if category has products
            $product_check_sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
            $product_check_stmt = $conn->prepare($product_check_sql);
            $product_check_stmt->bind_param("i", $id);
            $product_check_stmt->execute();
            $product_check_result = $product_check_stmt->get_result();
            $product_count = $product_check_result->fetch_assoc()['count'];
            
            if ($product_count > 0) {
                throw new Exception("Cannot delete category with associated products. Please reassign or delete the products first.");
            }
            
            // Delete the category
            $sql = "DELETE FROM categories WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $response = [
                    'success' => true,
                    'message' => 'Category deleted successfully'
                ];
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Error deleting category: ' . $e->getMessage()
            ];
        }
        break;
        
    default:
        $response = [
            'success' => false,
            'message' => 'Invalid action'
        ];
}

// Return JSON response
echo json_encode($response);
exit;