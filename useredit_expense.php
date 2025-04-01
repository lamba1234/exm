<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a normal user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: user_login.php");
    exit();
}

// Check if expense ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: usermanage_expense.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$expense_id = $_GET['id'];
$success_message = '';
$error_message = '';

// Get expense details
$sql = "SELECT e.*, c.category_name 
        FROM expenses_user e 
        LEFT JOIN user_categories c ON e.category_id = c.category_id 
        WHERE e.expense_id = ? AND e.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $expense_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Expense not found or you don't have permission to edit it.";
    header("Location: usermanage_expense.php");
    exit();
}

$expense = $result->fetch_assoc();

// Get user's categories for the dropdown
$categories_sql = "SELECT category_id, category_name FROM user_categories WHERE user_id = ? ORDER BY category_name";
$categories_stmt = $conn->prepare($categories_sql);
$categories_stmt->bind_param("i", $user_id);
$categories_stmt->execute();
$categories = $categories_stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'];
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];
    $expense_date = $_POST['expense_date'];

    // Get current budget and total expenses for the month
    $budget_sql = "SELECT amount FROM budget WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())";
    $budget_stmt = $conn->prepare($budget_sql);
    $budget_stmt->bind_param("i", $user_id);
    $budget_stmt->execute();
    $budget_result = $budget_stmt->get_result();
    $current_budget = $budget_result->fetch_assoc()['amount'] ?? 0;

    // Get total expenses for current month excluding the current expense
    $expenses_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses_user 
                     WHERE user_id = ? 
                     AND MONTH(expense_date) = MONTH(CURRENT_DATE()) 
                     AND YEAR(expense_date) = YEAR(CURRENT_DATE())
                     AND expense_id != ?";
    $expenses_stmt = $conn->prepare($expenses_sql);
    $expenses_stmt->bind_param("ii", $user_id, $expense_id);
    $expenses_stmt->execute();
    $expenses_result = $expenses_stmt->get_result();
    $total_expenses = $expenses_result->fetch_assoc()['total'] ?? 0;

    // Check if updating this expense would exceed the budget
    if ($current_budget > 0 && ($total_expenses + $amount) > $current_budget) {
        $error_message = "This expense would exceed your monthly budget of XAF " . number_format($current_budget, 2) . ". Please adjust the amount or update your budget.";
    } else {
        // Update the expense
        $update_sql = "UPDATE expenses_user SET 
                      category_id = ?, 
                      amount = ?, 
                      description = ?, 
                      expense_date = ? 
                      WHERE expense_id = ? AND user_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("idssii", $category_id, $amount, $description, $expense_date, $expense_id, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Expense updated successfully!";
            header("Location: usermanage_expense.php");
            exit();
        } else {
            $error_message = "Error updating expense. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Expense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Edit Expense</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <?php while ($category = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $category['category_id']; ?>" 
                                                <?php echo $category['category_id'] == $expense['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount (XAF)</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       value="<?php echo $expense['amount']; ?>" step="0.01" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3"><?php echo htmlspecialchars($expense['description']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="expense_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="expense_date" name="expense_date" 
                                       value="<?php echo $expense['expense_date']; ?>" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Expense</button>
                                <a href="usermanage_expense.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 