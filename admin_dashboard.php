<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];

// Get company name
$sql = "SELECT company_name FROM companies WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();

// Get monthly expense statistics
$sql = "SELECT 
            COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as approved_total,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_total,
            COALESCE(SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END), 0) as rejected_total
        FROM expenses 
        WHERE company_id = ? 
        AND MONTH(expense_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(expense_date) = YEAR(CURRENT_DATE())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$monthly_stats = $stmt->get_result()->fetch_assoc();

// Get current budget and utilization
$current_year = date('Y');
$current_month = date('n');
$budget_sql = "SELECT amount FROM company_budgets WHERE company_id = ? AND year = ? AND month = ?";
$budget_stmt = $conn->prepare($budget_sql);
$budget_stmt->bind_param("iii", $company_id, $current_year, $current_month);
$budget_stmt->execute();
$current_budget = $budget_stmt->get_result()->fetch_assoc();

// Calculate total expenses and budget utilization
$total_expenses = $monthly_stats['approved_total'] + $monthly_stats['pending_total'];
$budget_amount = $current_budget['amount'] ?? 0;
$budget_utilization = $budget_amount > 0 ? ($total_expenses / $budget_amount) * 100 : 0;

// Get recent expenses
$sql = "SELECT e.*, c.category_name, CONCAT(emp.first_name, ' ', emp.last_name) as employee_name 
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.category_id 
        LEFT JOIN employees emp ON e.employee_id = emp.employee_id 
        WHERE e.company_id = ? AND e.status = 'pending'
        ORDER BY e.created_at DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$recent_expenses = $stmt->get_result();

// Get employee count
$sql = "SELECT COUNT(*) as total_employees FROM employees WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$employee_count = $stmt->get_result()->fetch_assoc()['total_employees'];

$page_title = "Admin Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Expense Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-full mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <button id="admin-sidebar-toggle" class="text-gray-500 hover:text-gray-700 mr-4 lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <a href="admin_dashboard.php" class="text-2xl font-bold text-blue-600">Expense Manager</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($company['company_name']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Admin Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content pt-16">
        <div class="container mx-auto px-4 py-8">
            <!-- Welcome Section -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-2xl font-bold text-gray-900">Welcome to Admin Dashboard</h2>
                    <p class="mt-1 text-gray-600">Manage your company's expenses and employees efficiently</p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-4 mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Monthly Budget</dt>
                        <dd class="mt-1 text-3xl font-semibold text-blue-600">XAF <?php echo number_format($budget_amount, 2); ?></dd>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Budget Utilization</dt>
                        <dd class="mt-1 text-3xl font-semibold <?php echo $budget_utilization > 80 ? 'text-red-600' : ($budget_utilization > 60 ? 'text-yellow-600' : 'text-green-600'); ?>">
                            <?php echo number_format($budget_utilization, 1); ?>%
                        </dd>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Approved Expenses (This Month)</dt>
                        <dd class="mt-1 text-3xl font-semibold text-green-600">XAF <?php echo number_format($monthly_stats['approved_total'], 2); ?></dd>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Expenses</dt>
                        <dd class="mt-1 text-3xl font-semibold text-yellow-600">XAF <?php echo number_format($monthly_stats['pending_total'], 2); ?></dd>
                    </div>
                </div>
            </div>

            <!-- Budget Overview -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Budget Overview</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-blue-800">Total Budget</h4>
                            <p class="text-2xl font-bold text-blue-600">XAF <?php echo number_format($budget_amount, 2); ?></p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-green-800">Total Expenses</h4>
                            <p class="text-2xl font-bold text-green-600">XAF <?php echo number_format($total_expenses, 2); ?></p>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-yellow-800">Remaining Budget</h4>
                            <p class="text-2xl font-bold text-yellow-600">XAF <?php echo number_format($budget_amount - $total_expenses, 2); ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="relative pt-1">
                            <div class="flex mb-2 items-center justify-between">
                                <div>
                                    <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full <?php echo $budget_utilization > 80 ? 'text-red-600 bg-red-200' : ($budget_utilization > 60 ? 'text-yellow-600 bg-yellow-200' : 'text-green-600 bg-green-200'); ?>">
                                        Budget Utilization
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-semibold inline-block <?php echo $budget_utilization > 80 ? 'text-red-600' : ($budget_utilization > 60 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                        <?php echo number_format($budget_utilization, 1); ?>%
                                    </span>
                                </div>
                            </div>
                            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded <?php echo $budget_utilization > 80 ? 'bg-red-200' : ($budget_utilization > 60 ? 'bg-yellow-200' : 'bg-green-200'); ?>">
                                <div style="width:<?php echo min($budget_utilization, 100); ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center <?php echo $budget_utilization > 80 ? 'bg-red-500' : ($budget_utilization > 60 ? 'bg-yellow-500' : 'bg-green-500'); ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <a href="admin_view_expenses.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            View Expenses
                        </a>
                        <a href="admin_reports.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700">
                            View Reports
                        </a>
                        <a href="manage_budgets.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700">
                            Manage Budget
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Pending Expenses -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Recent Pending Expenses</h3>
                        <a href="admin_view_expenses.php" class="text-blue-600 hover:text-blue-900">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($expense = $recent_expenses->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($expense['expense_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($expense['employee_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($expense['category_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        XAF <?php echo number_format($expense['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button onclick="handleExpenseAction(<?php echo $expense['expense_id']; ?>, 'approve')" class="text-green-600 hover:text-green-900">
                                            Approve
                                        </button>
                                        <button onclick="handleExpenseAction(<?php echo $expense['expense_id']; ?>, 'reject')" class="text-red-600 hover:text-red-900">
                                            Reject
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function handleExpenseAction(expenseId, action) {
            if (!confirm(`Are you sure you want to ${action} this expense?`)) {
                return;
            }

            fetch('handle_expense_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    expense_id: expenseId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Expense ${action}d successfully!`);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request.');
            });
        }
    </script>
</body>
</html> 