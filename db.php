    <?php
    // Database configuration
    $host = "sql107.infinityfree.com";  // Using IP instead of "localhost"
    $user = "if0_38843389"; 
    $password = "streatskamed"; 
    $database = "if0_38843389_streats";
    
    // Create connection
    try {
        $conn = new mysqli($host, $user, $password, $database);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Optional connection verification
        // echo "Connected successfully to database.";
        
    } catch (Exception $e) {
        die("Database error: " . $e->getMessage());
    }
    ?>
  