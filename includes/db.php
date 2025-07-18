<?php
// Database configuration
$host = "127.0.0.1";  // Using IP instead of "localhost"
$user = "root"; 
$password = ""; 
$database = "streats";

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
    
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Keep the old MySQLi connection for backwards compatibility if needed
    $conn = new mysqli($host, $user, $password, $database);
    
    // Check MySQLi connection
    if ($conn->connect_error) {
        throw new Exception("MySQLi Connection failed: " . $conn->connect_error);
    }
    
    // Optional connection verification
    // echo "Connected successfully to database.";
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>