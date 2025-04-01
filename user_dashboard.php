<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get total expenses for the current month
$sql = "SELECT COALESCE(SUM(amount), 0) as total 
        FROM expenses_user 
        WHERE user_id = ? 
        AND MONTH(expense_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(expense_date) = YEAR(CURRENT_DATE())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthly_total = $stmt->get_result()->fetch_assoc()['total'];

// Get total expenses for today
$sql = "SELECT COALESCE(SUM(amount), 0) as total 
        FROM expenses_user 
        WHERE user_id = ? 
        AND DATE(expense_date) = CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$today_total = $stmt->get_result()->fetch_assoc()['total'];

// Get current budget
$budget_sql = "SELECT amount FROM budget WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())";
$budget_stmt = $conn->prepare($budget_sql);
$budget_stmt->bind_param("i", $user_id);
$budget_stmt->execute();
$budget_result = $budget_stmt->get_result();
$current_budget = $budget_result->fetch_assoc()['amount'] ?? 0;

// Get total expenses for current month
$expenses_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses_user WHERE user_id = ? AND MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())";
$expenses_stmt = $conn->prepare($expenses_sql);
$expenses_stmt->bind_param("i", $user_id);
$expenses_stmt->execute();
$expenses_result = $expenses_stmt->get_result();
$total_expenses = $expenses_result->fetch_assoc()['total'] ?? 0;

$remaining_budget = $current_budget - $total_expenses;

// Get recent expenses
$sql = "SELECT 
            e.expense_id,
            e.amount,
            e.description,
            e.expense_date,
            e.receipt_path,
            c.category_name,
            c.category_id
        FROM expenses_user e 
        LEFT JOIN user_categories c ON e.category_id = c.category_id 
        WHERE e.user_id = ? 
        ORDER BY e.expense_date DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_expenses = $stmt->get_result();

// Reset the result pointer
$recent_expenses->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Expense Manager</title>
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
                <h1 class="text-3xl font-bold text-gray-900 mb-8">User Dashboard</h1>
                
                <!-- Expense Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <!-- Total Expenses This Month Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700">Total Expenses</h3>
                                <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo number_format($monthly_total, 2); ?> XAF</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-dollar-sign text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Expenses Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700">Today's Expenses</h3>
                                <p class="text-3xl font-bold text-green-600 mt-2"><?php echo number_format($today_total, 2); ?> XAF</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-calendar-day text-green-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Total Budget Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700">Total Budget</h3>
                                <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo number_format($current_budget, 2); ?> XAF</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-money-bill-wave text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Remaining Budget Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700">Remaining Budget</h3>
                                <p class="text-3xl font-bold text-green-600 mt-2"><?php echo number_format($remaining_budget, 2); ?> XAF</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-piggy-bank text-green-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <!-- Quick Actions Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
                        <div class="space-y-4">
                            <a href="useraddexpense.php" class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i> Add New Expense 
                            </a>
                        </div>
                    </div>

                    <!-- Recent Expenses Table -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Expenses</h2>
                        <div class="w-full">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="p-4 text-left">Category</th>
                                        <th class="p-4 text-left">Description</th>
                                        <th class="p-4 text-left">Date</th>
                                        <th class="p-4 text-left">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_expenses->num_rows > 0): ?>
                                        <?php while ($expense = $recent_expenses->fetch_assoc()): ?>
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="p-4">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-receipt text-blue-600 mr-3"></i>
                                                        <span><?php echo htmlspecialchars($expense['category_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="p-4"><?php echo htmlspecialchars($expense['description']); ?></td>
                                                <td class="p-4"><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                                <td class="p-4 font-semibold">XAF <?php echo number_format($expense['amount'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="p-8 text-center text-gray-500">
                                                <i class="fas fa-receipt text-4xl mb-2"></i>
                                                <p>No expenses found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
