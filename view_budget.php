<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$company_id = $_SESSION['company_id'];

// Get current budget
$current_year = date('Y');
$current_month = date('n');
$sql = "SELECT amount FROM company_budgets WHERE company_id = ? AND year = ? AND month = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $company_id, $current_year, $current_month);
$stmt->execute();
$current_budget = $stmt->get_result()->fetch_assoc();

// Get company name
$sql = "SELECT company_name FROM companies WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

// Get total expenses for current month
$sql = "SELECT COALESCE(SUM(amount), 0) as total_expenses 
        FROM expenses 
        WHERE company_id = ? 
        AND MONTH(expense_date) = ? 
        AND YEAR(expense_date) = ? 
        AND status = 'approved'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $company_id, $current_month, $current_year);
$stmt->execute();
$expenses = $stmt->get_result()->fetch_assoc();
$total_expenses = $expenses['total_expenses'];

// Calculate remaining budget
$remaining_budget = ($current_budget['amount'] ?? 0) - $total_expenses;
$budget_utilization = ($current_budget['amount'] ?? 0) > 0 ? 
    ($total_expenses / ($current_budget['amount'] ?? 1)) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Budget - Enterprise Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="dashboard.php" class="text-2xl font-bold text-blue-600">Enterprise Expense Tracker</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-700"><?php echo htmlspecialchars($company['company_name']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Company Budget Overview</h1>

            <!-- Current Budget Section -->
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Monthly Budget for <?php echo date('F Y'); ?></h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-blue-800">Total Budget</h3>
                        <p class="text-2xl font-bold text-blue-600 mt-1">
                            XAF <?php echo number_format($current_budget['amount'] ?? 0, 2); ?>
                        </p>
                    </div>
                    
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-green-800">Total Expenses</h3>
                        <p class="text-2xl font-bold text-green-600 mt-1">
                            XAF <?php echo number_format($total_expenses, 2); ?>
                        </p>
                    </div>
                    
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-yellow-800">Remaining Budget</h3>
                        <p class="text-2xl font-bold text-yellow-600 mt-1">
                            XAF <?php echo number_format($remaining_budget, 2); ?>
                        </p>
                    </div>
                </div>

                <!-- Budget Utilization Progress Bar -->
                <div class="mt-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Budget Utilization</span>
                        <span><?php echo number_format($budget_utilization, 1); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo min($budget_utilization, 100); ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Budget Guidelines -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Budget Guidelines</h2>
                <div class="prose max-w-none">
                    <ul class="list-disc pl-5 space-y-2">
                        <li>All expenses must be submitted with proper documentation and receipts.</li>
                        <li>Expenses will be reviewed and approved by the admin team.</li>
                        <li>Please submit expenses promptly to ensure timely processing.</li>
                        <li>Contact your manager if you have any questions about expense submissions.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 