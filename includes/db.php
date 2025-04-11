    <?php
    // Database configuration
    $host = "127.0.0.1";  // Using IP instead of "localhost"
    $user = "root"; 
    $password = ""; 
    $database = "streats";
    $port = 3307;  // Your custom MySQL port
    
    // Create connection
    try {
        $conn = new mysqli($host, $user, $password, $database, $port);
        
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
  