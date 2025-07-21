<?php
// Database configuration (dummy example for public repo)
$host = "your_host";
$user = "your_db_user";
$password = "your_db_password";
$database = "your_db_name";

try {
    $conn = new mysqli($host, $user, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>
