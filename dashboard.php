<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Check if company_id exists and redirect to login if not found
if (!$company_id) {
    session_destroy(); // Clear all session data
    header("Location: login.php?error=" . urlencode("Session expired. Please log in again."));
    exit();
}

// Get company name
$sql = "SELECT company_name FROM companies WHERE company_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();

// Check if company exists
if (!$company) {
    die("Error: Company not found. Please contact support.");
}

// Get recent expenses
$sql = "SELECT e.*, c.category_name, CONCAT(emp.first_name, ' ', emp.last_name) as employee_name 
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.category_id 
        LEFT JOIN employees emp ON e.employee_id = emp.employee_id 
        WHERE e.company_id = ? 
        ORDER BY e.created_at DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$recent_expenses = $stmt->get_result();

// Get total expenses for the current month
$sql = "SELECT COALESCE(SUM(amount), 0) as total 
        FROM expenses 
        WHERE company_id = ? 
        AND MONTH(expense_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(expense_date) = YEAR(CURRENT_DATE())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$monthly_total = $stmt->get_result()->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Enterprise Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/notifications.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Check for success message in URL parameters
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const successMsg = urlParams.get('success');
            if (successMsg) {
                showNotification(decodeURIComponent(successMsg));
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-full mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-700 mr-4 lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <a href="dashboard.php" class="text-2xl font-bold text-blue-600">Enterprise Expense Tracker</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700"><?php echo htmlspecialchars($company['company_name']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-2xl font-bold text-gray-900">Welcome to your Dashboard</h2>
                    <p class="mt-1 text-gray-600">Manage your company's expenses efficiently</p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Monthly Expenses</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">XAF <?php echo number_format($monthly_total, 2); ?></dd>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Your Role</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo ucfirst($role); ?></dd>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Company</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo htmlspecialchars($company['company_name']); ?></dd>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <a href="add_expense.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Add New Expense
                        </a>
                        <a href="<?php echo $role === 'admin' ? 'view_expenses.php' : 'my_expenses.php'; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            <?php echo $role === 'admin' ? 'View All Expenses' : 'View My Expenses'; ?>
                        </a>
                        <a href="view_budget.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700">
                            View Company Budget
                        </a>
                        <?php if ($role === 'admin'): ?>
                        <a href="manage_employees.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                            Manage Employees
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Expenses -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Expenses</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($expense = $recent_expenses->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($expense['expense_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($expense['employee_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($expense['category_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        XAF <?php echo number_format($expense['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $expense['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                                ($expense['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php echo ucfirst($expense['status']); ?>
                                        </span>
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
</body>
</html>