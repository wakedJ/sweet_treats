<?php
session_start();
// Destroy all session data
$_SESSION = array();
session_destroy();
// Redirect to home page
header("Location: index.php");
exit;
?>