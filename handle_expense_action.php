<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['expense_id']) || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$expense_id = $data['expense_id'];
$action = $data['action'];

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Get expense details first
$sql = "SELECT e.*, emp.email as employee_email, emp.first_name, emp.last_name 
        FROM expenses e 
        LEFT JOIN employees emp ON e.employee_id = emp.employee_id 
        WHERE e.expense_id = ? AND e.company_id = ? AND e.status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $expense_id, $_SESSION['company_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Expense not found or already processed']);
    exit();
}

$expense = $result->fetch_assoc();

// Start transaction
$conn->begin_transaction();

try {
    // Update expense status
    $new_status = $action === 'approve' ? 'approved' : 'rejected';
    $sql = "UPDATE expenses SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE expense_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $expense_id);
    $stmt->execute();

    // If expense is approved, update budget utilization
    if ($action === 'approve') {
        // Get current budget for the expense's month
        $budget_sql = "SELECT amount FROM company_budgets 
                      WHERE company_id = ? 
                      AND year = YEAR(?) 
                      AND month = MONTH(?)";
        $budget_stmt = $conn->prepare($budget_sql);
        $budget_stmt->bind_param("iss", $_SESSION['company_id'], $expense['expense_date'], $expense['expense_date']);
        $budget_stmt->execute();
        $budget_result = $budget_stmt->get_result();
        $budget = $budget_result->fetch_assoc();

        if ($budget) {
            // Update budget utilization
            $utilization_sql = "UPDATE company_budgets 
                              SET utilized_amount = COALESCE(utilized_amount, 0) + ? 
                              WHERE company_id = ? 
                              AND year = YEAR(?) 
                              AND month = MONTH(?)";
            $utilization_stmt = $conn->prepare($utilization_sql);
            $utilization_stmt->bind_param("diiss", $expense['amount'], $_SESSION['company_id'], $expense['expense_date'], $expense['expense_date']);
            $utilization_stmt->execute();
        }
    }

    $conn->commit();
    
    // Try to send email notification after successful transaction
    try {
        $to = $expense['employee_email'];
        $subject = "Expense " . ucfirst($new_status);
        $message = "Dear " . $expense['first_name'] . " " . $expense['last_name'] . ",\n\n";
        $message .= "Your expense of XAF " . number_format($expense['amount'], 2) . " has been " . $new_status . ".\n\n";
        $message .= "Best regards,\nAdmin Team";
        $headers = "From: noreply@yourcompany.com";

        mail($to, $subject, $message, $headers);
        
        echo json_encode(['success' => true, 'message' => 'Expense ' . $new_status . ' successfully']);
    } catch (Exception $e) {
        // Log email error but don't affect the response
        error_log("Failed to send email notification: " . $e->getMessage());
        echo json_encode(['success' => true, 'message' => 'Expense ' . $new_status . ' successfully']);
    }
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error processing expense: ' . $e->getMessage()]);
} 