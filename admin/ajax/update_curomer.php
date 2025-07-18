<?php
// Start session for flash messages
session_start();

// Include database connection
require_once "../includes/db.php";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: customers.php");
    exit;
}

// Check if customer ID is provided
if (!isset($_POST['customer_id']) || empty($_POST['customer_id'])) {
    $_SESSION['message'] = "Customer ID is missing.";
    $_SESSION['message_type'] = "alert-danger";
    header("Location: customers.php");
    exit;
}

$customer_id = intval($_POST['customer_id']);

// Sanitize and validate input
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$status = $_POST['status'] ?? 'active';
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$zip_code = trim($_POST['zip_code'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email)) {
    $_SESSION['message'] = "First name, last name, and email are required fields.";
    $_SESSION['message_type'] = "alert-danger";
    header("Location: edit_customer.php?id=" . $customer_id);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = "Please enter a valid email address.";
    $_SESSION['message_type'] = "alert-danger";
    header("Location: edit_customer.php?id=" . $customer_id);
    exit;
}

// Validate status
$valid_statuses = ['active', 'inactive', 'suspended'];
if (!in_array($status, $valid_statuses)) {
    $_SESSION['message'] = "Invalid status value.";
    $_SESSION['message_type'] = "alert-danger";
    header("Location: edit_customer.php?id=" . $customer_id);
    exit;
}

// Check if email already exists for another customer
$check_email_sql = "SELECT id FROM users WHERE email = ? AND id != ? AND role = 'customer'";
$check_stmt = $conn->prepare($check_email_sql);
$check_stmt->bind_param("si", $email, $customer_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['message'] = "Email address is already in use by another customer.";
    $_SESSION['message_type'] = "alert-danger";
    header("Location: edit_customer.php?id=" . $customer_id);
    $check_stmt->close();
    $conn->close();
    exit;
}

$check_stmt->close();

// Prepare and execute the update query
$update_sql = "UPDATE users 
              SET first_name = ?, 
                  last_name = ?, 
                  email = ?, 
                  phone_number = ?, 
                  status = ?, 
                  address = ?, 
                  city = ?, 
                  state = ?, 
                  zip_code = ?, 
                  notes = ?,
                  updated_at = NOW()
              WHERE id = ? AND role = 'customer'";

$stmt = $conn->prepare($update_sql);
$stmt->bind_param(
    "ssssssssssi", 
    $first_name, 
    $last_name, 
    $email, 
    $phone_number, 
    $status, 
    $address, 
    $city, 
    $state, 
    $zip_code, 
    $notes, 
    $customer_id
);

// Execute the query and check for success
if ($stmt->execute()) {
    $_SESSION['message'] = "Customer information updated successfully.";
    $_SESSION['message_type'] = "alert-success";
    header("Location: view_customer.php?id=" . $customer_id);
} else {
    $_SESSION['message'] = "Error updating customer information: " . $conn->error;
    $_SESSION['message_type'] = "alert-danger";
    header("Location: edit_customer.php?id=" . $customer_id);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>