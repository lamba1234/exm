<?php
require_once 'config/database.php';

$sql = "SELECT employee_id, first_name, last_name, email, role FROM employees WHERE role = 'admin'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Admin users found:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['employee_id'] . "\n";
        echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
        echo "Email: " . $row['email'] . "\n";
        echo "Role: " . $row['role'] . "\n\n";
    }
} else {
    echo "No admin users found in the database.";
} 