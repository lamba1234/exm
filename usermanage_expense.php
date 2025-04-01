<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a normal user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle expense deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $expense_id = $_GET['delete'];
    
    // Verify the expense belongs to the user
    $check_sql = "SELECT * FROM expenses_user WHERE expense_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $expense_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete the expense
        $delete_sql = "DELETE FROM expenses_user WHERE expense_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $expense_id, $user_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "Expense deleted successfully!";
        } else {
            $error_message = "Error deleting expense. Please try again.";
        }
    } else {
        $error_message = "Expense not found or you don't have permission to delete it.";
    }
}

// Get user's expenses with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT e.*, c.category_name 
        FROM expenses_user e 
        LEFT JOIN user_categories c ON e.category_id = c.category_id 
        WHERE e.user_id = ? 
        ORDER BY e.expense_date DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$expenses = $stmt->get_result();

// Get total number of expenses for pagination
$total_sql = "SELECT COUNT(*) as total FROM expenses_user WHERE user_id = ?";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_expenses = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_expenses / $limit);

// Get current budget and total expenses for the month
$budget_sql = "SELECT amount FROM budget WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())";
$budget_stmt = $conn->prepare($budget_sql);
$budget_stmt->bind_param("i", $user_id);
$budget_stmt->execute();
$budget_result = $budget_stmt->get_result();
$current_budget = $budget_result->fetch_assoc()['amount'] ?? 0;

$monthly_expenses_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses_user WHERE user_id = ? AND MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())";
$monthly_expenses_stmt = $conn->prepare($monthly_expenses_sql);
$monthly_expenses_stmt->bind_param("i", $user_id);
$monthly_expenses_stmt->execute();
$monthly_expenses_result = $monthly_expenses_stmt->get_result();
$monthly_expenses = $monthly_expenses_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Expenses - Expense Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'includes/user_header.php'; ?>
    <?php include 'includes/user_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="pt-20 pb-10">
        <div class="w-full">
            <div class="px-4">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Manage Expenses</h1>
                    <a href="useraddexpense.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Expense
                    </a>
                </div>

                <?php if ($success_message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Budget Summary -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Budget Summary</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Total Budget</p>
                            <h4 class="text-xl font-semibold text-blue-600">XAF <?php echo number_format($current_budget, 2); ?></h4>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Current Expenses</p>
                            <h4 class="text-xl font-semibold text-green-600">XAF <?php echo number_format($monthly_expenses, 2); ?></h4>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">Remaining Budget</p>
                            <h4 class="text-xl font-semibold <?php echo ($current_budget - $monthly_expenses) < 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                XAF <?php echo number_format($current_budget - $monthly_expenses, 2); ?>
                            </h4>
                        </div>
                    </div>
                </div>

                <!-- Expenses Table -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($expense = $expenses->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('M d, Y', strtotime($expense['expense_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                                <?php echo htmlspecialchars($expense['category_name']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($expense['description']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            XAF <?php echo number_format($expense['amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <a href="useredit_expense.php?id=<?php echo $expense['expense_id']; ?>" 
                                               class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $expense['expense_id']; ?>" 
                                               class="text-red-600 hover:text-red-900"
                                               onclick="return confirm('Are you sure you want to delete this expense?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-4 flex justify-center">
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 <?php echo $i === $page ? 'bg-blue-50 border-blue-500 text-blue-600' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#menu-toggle').click(function() {
                $('#sidebar').toggleClass('-translate-x-full');
            });

            // Close sidebar when clicking outside
            $(document).click(function(event) {
                if (!$(event.target).closest('#sidebar, #menu-toggle').length) {
                    $('#sidebar').addClass('-translate-x-full');
                }
            });
        });
    </script>
</body>
</html> 