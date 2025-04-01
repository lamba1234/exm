<?php
require_once 'config.php';

// First drop the tables if they exist
$conn->query("DROP TABLE IF EXISTS expenses_user");
$conn->query("DROP TABLE IF EXISTS user_categories");

// Create user_categories table
$sql = "CREATE TABLE user_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table user_categories created successfully<br>";
} else {
    echo "Error creating user_categories table: " . $conn->error . "<br>";
}

// Create expenses_user table
$sql = "CREATE TABLE expenses_user (
    expense_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    expense_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    receipt_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES user_categories(category_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table expenses_user created successfully";
} else {
    echo "Error creating expenses_user table: " . $conn->error;
}

$conn->close();
?> 