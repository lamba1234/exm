<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle budget setting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_budget'])) {
    $budget_amount = floatval($_POST['budget_amount']);

    // Check if budget already exists for this month
    $check_sql = "SELECT id FROM budget WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing budget
        $sql = "UPDATE budget SET amount = ? WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $budget_amount, $user_id);
    } else {
        // Insert new budget
        $sql = "INSERT INTO budget (user_id, amount, date) VALUES (?, ?, CURRENT_DATE())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("id", $user_id, $budget_amount);
    }

    if ($stmt->execute()) {
        $success_message = "Budget set successfully!";
    } else {
        $error_message = "Error setting budget. Please try again.";
    }
}

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

// Get recent expenses
$recent_expenses_sql = "SELECT e.*, c.category_name FROM expenses_user e LEFT JOIN user_categories c ON e.category_id = c.category_id WHERE e.user_id = ? ORDER BY e.expense_date DESC LIMIT 5";
$recent_expenses_stmt = $conn->prepare($recent_expenses_sql);
$recent_expenses_stmt->bind_param("i", $user_id);
$recent_expenses_stmt->execute();
$recent_expenses = $recent_expenses_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Budget - Expense Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'includes/user_header.php'; ?>
    <?php include 'includes/user_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="pt-20 pb-10 px-4">
        <div class="max-w-7xl mx-auto">
            <!-- Budget Overview -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Budget Overview</h1>

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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-900">Monthly Budget</h3>
                        <p class="text-2xl font-bold text-blue-600">XAF <?php echo number_format($current_budget, 2); ?></p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-red-900">Total Expenses</h3>
                        <p class="text-2xl font-bold text-red-600">XAF <?php echo number_format($total_expenses, 2); ?></p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-green-900">Remaining Budget</h3>
                        <p class="text-2xl font-bold text-green-600">XAF <?php echo number_format($current_budget - $total_expenses, 2); ?></p>
                    </div>
                </div>

                <form method="POST" class="space-y-4">
                    <div>
                        <label for="budget_amount" class="block text-sm font-medium text-gray-700">Set Monthly Budget (XAF)</label>
                        <div class="mt-1">
                            <input type="number" step="0.01" name="budget_amount" id="budget_amount" required
                                   value="<?php echo $current_budget; ?>"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div>
                        <button type="submit" name="set_budget" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Update Budget
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Expenses -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Expenses</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($recent_expenses->num_rows > 0): ?>
                                <?php while ($expense = $recent_expenses->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($expense['expense_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($expense['category_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($expense['description']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                            XAF <?php echo number_format($expense['amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No expenses found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
