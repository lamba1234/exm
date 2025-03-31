<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if expense ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_expenses.php");
    exit();
}

$expense_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// First verify that the expense belongs to the user and is pending
$check_sql = "SELECT status, receipt_path FROM expenses WHERE expense_id = ? AND employee_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $expense_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Expense not found or you don't have permission to delete it.";
    header("Location: my_expenses.php");
    exit();
}

$expense = $result->fetch_assoc();

// Only allow deletion of pending expenses
if ($expense['status'] !== 'pending') {
    $_SESSION['error'] = "Only pending expenses can be deleted.";
    header("Location: my_expenses.php");
    exit();
}

// Delete the receipt file if it exists
if ($expense['receipt_path'] && file_exists($expense['receipt_path'])) {
    unlink($expense['receipt_path']);
}

// Delete the expense from the database
$delete_sql = "DELETE FROM expenses WHERE expense_id = ? AND employee_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $expense_id, $user_id);

if ($delete_stmt->execute()) {
    $_SESSION['success'] = "Expense deleted successfully.";
} else {
    $_SESSION['error'] = "Error deleting expense. Please try again.";
}

$delete_stmt->close();
$conn->close();

header("Location: my_expenses.php");
exit(); 