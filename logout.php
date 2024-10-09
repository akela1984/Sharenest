<?php
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to the login page after logout
header("Location: index.php"); // Make sure login.php exists in the correct location
exit;
?>
