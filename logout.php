<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to user login page
header("Location: user_login.php");
exit();
?> 