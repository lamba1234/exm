<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$company_id = $_SESSION['company_id'];
$message = '';
$error = '';

// Handle form submission for setting budget
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_budget'])) {
    $amount = floatval($_POST['amount']);
    $year = intval($_POST['year']);
    $month = intval($_POST['month']);

    // Validate inputs
    if ($amount <= 0 || $year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
        $error = "Invalid input values. Please check your entries.";
    } else {
        // Check if budget already exists for this month
        $check_sql = "SELECT budget_id FROM company_budgets WHERE company_id = ? AND year = ? AND month = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iii", $company_id, $year, $month);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing budget
            $sql = "UPDATE company_budgets SET amount = ? WHERE company_id = ? AND year = ? AND month = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("diii", $amount, $company_id, $year, $month);
        } else {
            // Insert new budget
            $sql = "INSERT INTO company_budgets (company_id, amount, year, month) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("idii", $company_id, $amount, $year, $month);
        }

        if ($stmt->execute()) {
            $message = "Budget successfully set for " . date('F Y', mktime(0, 0, 0, $month, 1, $year));
        } else {
            $error = "Error setting budget. Please try again.";
        }
    }
}

// Get current budget
$current_year = date('Y');
$current_month = date('n');
$sql = "SELECT amount FROM company_budgets WHERE company_id = ? AND year = ? AND month = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $company_id, $current_year, $current_month);
$stmt->execute();
$current_budget = $stmt->get_result()->fetch_assoc();

// Get budget history
$sql = "SELECT * FROM company_budgets WHERE company_id = ? ORDER BY year DESC, month DESC LIMIT 12";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$budget_history = $stmt->get_result();

// Get company name
$sql = "SELECT company_name FROM companies WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Company Budget - Enterprise Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
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
                    <a href="admin_dashboard.php" class="text-2xl font-bold text-blue-600">Enterprise Expense Tracker</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700"><?php echo htmlspecialchars($company['company_name']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 mt-16">
        <div class="h-16 flex items-center justify-between px-4 border-b">
            <span class="text-xl font-semibold text-gray-800">Menu</span>
            <button id="sidebar-toggle" class="lg:hidden text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <nav class="mt-6">
            <div class="px-2 space-y-3">
                <a href="admin_dashboard.php" class="flex items-center text-left space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="add_expense.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Add Expense</span>
                </a>
                <a href="view_expenses.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span>View All Expenses</span>
                </a>
                <a href="manage_budgets.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2 bg-blue-50">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Manage Budget</span>
                </a>
                <a href="manage_employees.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Manage Employees</span>
                </a>
            </div>
        </nav>
        <!-- Logout Link -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t">
            <a href="logout.php" class="flex items-center space-x-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <style>
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 30;
        display: none;
    }

    @media (max-width: 1024px) {
        .sidebar-overlay {
            display: block;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        
        // Mobile sidebar toggle
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(event.target) && !event.target.closest('#sidebar-toggle')) {
                    sidebar.classList.remove('active');
                }
            }
        });
    });
    </script>

    <!-- Main Content -->
    <div class="lg:ml-64 pt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">Manage Company Budget</h1>

                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Current Budget Section -->
                <div class="bg-white shadow rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Current Budget</h2>
                    <div class="text-3xl font-bold text-blue-600">
                        XAF <?php echo number_format($current_budget['amount'] ?? 0, 2); ?>
                    </div>
                    <p class="text-gray-600 mt-2">For <?php echo date('F Y'); ?></p>
                </div>

                <!-- Set Budget Form -->
                <div class="bg-white shadow rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Set New Budget</h2>
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">Amount (XAF)</label>
                                <input type="number" step="0.01" name="amount" id="amount" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                                <input type="number" name="year" id="year" required min="2000" max="2100"
                                    value="<?php echo $current_year; ?>"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="month" class="block text-sm font-medium text-gray-700">Month</label>
                                <select name="month" id="month" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $i == $current_month ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <button type="submit" name="set_budget"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Set Budget
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Budget History -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Budget History</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Set On</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($budget = $budget_history->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('F Y', mktime(0, 0, 0, $budget['month'], 1, $budget['year'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            XAF <?php echo number_format($budget['amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('M d, Y', strtotime($budget['created_at'])); ?>
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