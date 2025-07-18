<?php
// Enhanced auth_check.php - Authentication and user data management

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure database connection is established
if (!isset($conn) || !$conn) {
    // Include database connection if not already included
    include_once 'db.php';
}

// Function to construct absolute URLs
function getAbsoluteUrl($path) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_dir = dirname($_SERVER['PHP_SELF']);
    
    // Handle admin subdirectory
    if (basename($base_dir) === 'admin') {
        $base_dir = dirname($base_dir);
    }
    
    return $protocol . '://' . $host . $base_dir . '/' . ltrim($path, '/');
}

// Function to redirect based on user role
function redirectLoggedInUser($user_role) {
    if ($user_role === 'admin') {
        $redirect_url = getAbsoluteUrl('admin/index.php');
    } else {
        $redirect_url = getAbsoluteUrl('account.php');
    }
    
    header("Location: $redirect_url");
    exit();
}

// Function to redirect to login
function redirectToLogin($error_message = '') {
    if (!empty($error_message)) {
        $_SESSION['login_error'] = $error_message;
    }
    
    $login_url = getAbsoluteUrl('login.php');
    header("Location: $login_url");
    exit();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Initialize variables
$user_data = [];
$user_address = [];

// If user is logged in, fetch their profile information
if ($is_logged_in) {
    try {
        // Fetch user data
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare user query: " . $conn->error);
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            
            // Store user role in session if not already there
            if (!isset($_SESSION['user_role'])) {
                $_SESSION['user_role'] = $user_data['role'];
            }
            
            // Store user name in session if not already there
            if (!isset($_SESSION['user_name'])) {
                $_SESSION['user_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
            }
            
        } else {
            // If no user found, destroy session
            session_unset();
            session_destroy();
            $is_logged_in = false;
            $user_id = null;
        }
        
        if ($stmt) {
            $stmt->close();
        }
        
        // Only proceed with address fetch if user data was found
        if ($is_logged_in) {
            // Fetch default user address
            $query = "SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1";
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Failed to prepare default address query: " . $conn->error);
            }
            
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user_address = $result->fetch_assoc();
            } else {
                // If no default address found, get any address
                $query = "SELECT * FROM user_addresses WHERE user_id = ? LIMIT 1";
                $stmt = $conn->prepare($query);
                
                if (!$stmt) {
                    throw new Exception("Failed to prepare fallback address query: " . $conn->error);
                }
                
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user_address = $result->fetch_assoc();
                }
            }
            
            if ($stmt) {
                $stmt->close();
            }
        }
        
    } catch (Exception $e) {
        // Log the error
        error_log("User data retrieval error: " . $e->getMessage());
        
        // Set a user-friendly error message
        $_SESSION['error'] = "An error occurred while retrieving your account information.";
        
        // Destroy session to prevent further issues
        session_unset();
        session_destroy();
        $is_logged_in = false;
        $user_id = null;
    }
}

// REDIRECT LOGIC - Add this section for specific page protections

// Get current script name to determine which page we're on
$current_page = basename($_SERVER['PHP_SELF']);

// If user is logged in and trying to access login page, redirect them
if ($is_logged_in && $current_page === 'login.php') {
    redirectLoggedInUser($_SESSION['user_role']);
}


$timeout_duration = 3600; // 1 hour in seconds
if ($is_logged_in && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    redirectToLogin('Session expired. Please login again.');
}
if ($is_logged_in) {
    $_SESSION['last_activity'] = time();
}


// Optional: Regenerate session ID for security (every 5 minutes)
if ($is_logged_in) {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

?>
