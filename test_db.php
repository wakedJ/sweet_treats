<?php
$conn = new mysqli("127.0.0.1", "root", "", "streats", 3307);

if ($conn->connect_error) {
    die("Failed: " . $conn->connect_error);
} else {
    echo "✅ Connected!";
}
?>
