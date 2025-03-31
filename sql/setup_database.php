<?php
require_once '../config/database.php';

// Read and execute schema.sql
$schema = file_get_contents(__DIR__ . '/schema.sql');
if ($conn->multi_query($schema)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "Database schema created successfully!\n";
} else {
    echo "Error creating database schema: " . $conn->error . "\n";
}

// Read and execute add_last_activity.sql
$add_last_activity = file_get_contents(__DIR__ . '/add_last_activity.sql');
if ($conn->multi_query($add_last_activity)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "Last activity column added successfully!\n";
} else {
    echo "Error adding last activity column: " . $conn->error . "\n";
}

$conn->close(); 