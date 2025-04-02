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
$sql = "SELECT cb.*, 
        COALESCE(SUM(CASE WHEN e.status = 'approved' THEN e.amount ELSE 0 END), 0) as utilized_amount
        FROM company_budgets cb
        LEFT JOIN expenses e ON cb.company_id = e.company_id 
            AND cb.year = YEAR(e.expense_date) 
            AND cb.month = MONTH(e.expense_date)
        WHERE cb.company_id = ? 
        GROUP BY cb.budget_id
        ORDER BY cb.year DESC, cb.month DESC LIMIT 12";
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
    <title>Manage Company Budget - Expense Manager</title>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilized Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilization %</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Set On</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($budget = $budget_history->fetch_assoc()): 
                                    $remaining = $budget['amount'] - $budget['utilized_amount'];
                                    $utilization = $budget['amount'] > 0 ? ($budget['utilized_amount'] / $budget['amount']) * 100 : 0;
                                ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('F Y', mktime(0, 0, 0, $budget['month'], 1, $budget['year'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            XAF <?php echo number_format($budget['amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            XAF <?php echo number_format($budget['utilized_amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $remaining < 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                            XAF <?php echo number_format($remaining, 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-24 bg-gray-200 rounded-full h-2.5 mr-2">
                                                    <div class="h-2.5 rounded-full <?php echo $utilization > 80 ? 'bg-red-600' : ($utilization > 60 ? 'bg-yellow-600' : 'bg-green-600'); ?>" 
                                                         style="width: <?php echo min($utilization, 100); ?>%"></div>
                                                </div>
                                                <span class="text-sm <?php echo $utilization > 80 ? 'text-red-600' : ($utilization > 60 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                                    <?php echo number_format($utilization, 1); ?>%
                                                </span>
                                            </div>
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