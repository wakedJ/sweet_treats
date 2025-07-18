<?php
// debug_checker.php - Test if the server can properly receive JSON POST data
// Place this file in the same directory as update_order_status.php

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a log file for this specific test
$log_file = __DIR__ . '/json_test_debug.log';
function log_message($message) {
    global $log_file;
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

log_message("JSON Test debug script started");

// Set header to return JSON
header('Content-Type: application/json');

// Get raw POST data
$raw_post = file_get_contents('php://input');
log_message("Raw POST data received: " . $raw_post);

// Try to decode it as JSON
$json_data = json_decode($raw_post, true);
$json_error = json_last_error();
$json_error_msg = json_last_error_msg();

log_message("JSON decode result: " . ($json_error === JSON_ERROR_NONE ? "Success" : "Failed with error: $json_error_msg"));

// Check if we received POST variables
log_message("POST variables: " . print_r($_POST, true));

// Check request content type
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'Not specified';
log_message("Content-Type header: $content_type");

// Output response for debugging
echo json_encode([
    'success' => true,
    'message' => 'Debug information collected',
    'raw_input_received' => $raw_post,
    'json_decode_success' => ($json_error === JSON_ERROR_NONE),
    'json_error_message' => $json_error_msg,
    'parsed_data' => $json_data,
    'post_variables' => $_POST,
    'content_type' => $content_type,
    'server_info' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ]
]);

log_message("JSON Test debug script completed");
?>