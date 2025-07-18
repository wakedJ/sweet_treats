<?php
// This is a standalone script - not included by index.php
// Start the session
session_start();

// Process logout request
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'yes' || isset($_GET['timeout'])) {
    // Store the role before clearing the session
    $was_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Set a special cookie to prevent admin access after logout
    setcookie('admin_logged_out', '1', time() + 86400, '/');
    
    // Destroy the session
    session_destroy();
    
    // Start a new session for the redirect message
    session_start();
    
    // Set a notification message
    if (isset($_GET['timeout'])) {
        $_SESSION['message'] = "Your session has timed out due to inactivity.";
    } else {
        $_SESSION['message'] = "You have been logged out from the admin panel.";
    }
    
    // Add cache control headers to prevent back button access
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
    
    // Redirect to the main website homepage
    header("Location: ../../index.php");
    exit();
} else {
    // If this page was accessed directly without confirmation
    header("Location: ../index.php");
    exit();
}
?>