<?php
$host = 'localhost';
$dbname = 'exm_tracker';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Enable error reporting
    $conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?> 