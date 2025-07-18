<?php
// ajax_handler.php - Create this new file to handle AJAX requests

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the request is for saving a rating
if (isset($_POST['action']) && $_POST['action'] === 'save_rating') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }

    // Validate incoming data
    if (!isset($_POST['product_id']) || !isset($_POST['rating'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    // Get and sanitize data
    $user_id = $_SESSION['user_id'];
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);

    // Validate rating range
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
        exit;
    }

    // Database connection
    require_once 'db_connection.php'; // Include your database connection file

    try {
        // Check if a rating already exists
        $checkStmt = $pdo->prepare("SELECT id FROM ratings WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$user_id, $product_id]);
        $existingRating = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingRating) {
            // Update existing rating
            $stmt = $pdo->prepare("UPDATE ratings SET rating = ?, created_at = NOW() WHERE id = ?");
            $success = $stmt->execute([$rating, $existingRating['id']]);
        } else {
            // Insert new rating
            $stmt = $pdo->prepare("INSERT INTO ratings (user_id, product_id, rating, created_at) VALUES (?, ?, ?, NOW())");
            $success = $stmt->execute([$user_id, $product_id, $rating]);
        }
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Rating saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save rating']);
        }
        
    } catch (PDOException $e) {
        // Log the error (don't expose details to the user)
        error_log("Rating save error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
    
    exit;
}

// Handle other AJAX actions here if needed
echo json_encode(['success' => false, 'message' => 'Invalid action']);