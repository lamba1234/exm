<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['expense_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$expense_id = $_POST['expense_id'];
$status = $_POST['status'];
$company_id = $_SESSION['company_id'];

// Validate status
if (!in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Update expense status
$sql = "UPDATE expenses SET status = ?, updated_at = NOW() 
        WHERE expense_id = ? AND company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $status, $expense_id, $company_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update expense status']);
} 