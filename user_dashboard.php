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
        FROM expenses 
        WHERE employee_id = ? 
        AND MONTH(expense_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(expense_date) = YEAR(CURRENT_DATE())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthly_total = $stmt->get_result()->fetch_assoc()['total'];

// Get total expenses for today
$sql = "SELECT COALESCE(SUM(amount), 0) as total 
        FROM expenses 
        WHERE employee_id = ? 
        AND DATE(expense_date) = CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$today_total = $stmt->get_result()->fetch_assoc()['total'];

// Get recent expenses
$sql = "SELECT e.*, c.category_name
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.category_id 
        WHERE e.employee_id = ? 
        ORDER BY e.expense_date DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_expenses = $stmt->get_result();
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
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <button id="menu-toggle" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <a href="#" class="text-2xl font-bold text-blue-600 ml-4">Expense Manager</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar Navigation -->
    <div id="sidebar" class="fixed left-0 top-16 w-64 bg-white shadow-lg h-screen transform -translate-x-full transition-transform duration-300 ease-in-out z-40">
        <div class="p-4">
            <ul class="space-y-2">
                <li>
                    <a href="user_dashboard.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md">
                        <i class="fas fa-home mr-2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="useraddexpense.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md">
                        <i class="fas fa-plus mr-2"></i> Add Expense
                    </a>
                </li>
                <li>
                    <a href="manageexpense.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md">
                        <i class="fas fa-wallet mr-2"></i> Manage Budget
                    </a>
                </li>
                <li>
                    <a href="view_reports.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md">
                        <i class="fas fa-chart-bar mr-2"></i> View Reports
                    </a>
                </li>
                <li>
                    <a href="edit_profile.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md">
                        <i class="fas fa-user-edit mr-2"></i> Edit Profile
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="pt-20 pb-10 px-4">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">User Dashboard</h1>
            
            <!-- Expense Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Total Expenses Card -->
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
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Quick Actions Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
                    <div class="space-y-4">
                        <a href="useraddexpense.php" class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i> Add New Expense
                        </a>
                        <a href="view_reports.php" class="block w-full text-center bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            <i class="fas fa-chart-bar mr-2"></i> View Reports
                        </a>
                    </div>
                </div>

                <!-- Recent Expenses Table -->
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
