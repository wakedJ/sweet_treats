<?php
// check_admin.php - Enhanced security for admin access
session_start();

// Function to redirect to login with proper URL construction
function redirectToLogin() {
    // Clear any existing session data
    session_unset();
    session_destroy();
    
    // Start a new session
    session_start();
    
    // Construct the absolute URL to login page
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Get the directory path without the admin folder
    $script_dir = dirname($_SERVER['PHP_SELF']);
    if (basename($script_dir) === 'admin') {
        $base_dir = dirname($script_dir);
    } else {
        $base_dir = $script_dir;
    }
    
    $login_url = $protocol . '://' . $host . $base_dir . '/login.php';
    
    // Set error message for unauthorized access
    $_SESSION['login_error'] = 'Access denied. Please login with admin credentials.';
    
    // Redirect to login
    header("Location: $login_url");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
}

// Check if user has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirectToLogin();
}

// Optional: Add additional security checks
// Check if session is still valid (you can add timestamp validation)
if (!isset($_SESSION['user_name'])) {
    redirectToLogin();
}


$timeout_duration = 3600; // 1 hour in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    redirectToLogin();
}
$_SESSION['last_activity'] = time();


// Optional: Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>