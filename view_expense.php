<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if expense ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_expenses.php");
    exit();
}

$expense_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get expense details
$sql = "SELECT e.*, c.category_name,
        CONCAT(admin.first_name, ' ', admin.last_name) as reviewer_name,
        CONCAT(emp.first_name, ' ', emp.last_name) as employee_name
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.category_id 
        LEFT JOIN employees admin ON e.reviewed_by = admin.employee_id
        LEFT JOIN employees emp ON e.employee_id = emp.employee_id
        WHERE e.expense_id = ? AND e.employee_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $expense_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my_expenses.php");
    exit();
}

$expense = $result->fetch_assoc();

// Get company name
$company_id = $_SESSION['company_id'];
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
    <title>View Expense - Enterprise Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
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

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 pt-16">
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Expense Details</h2>
                        <a href="my_expenses.php" class="text-blue-600 hover:text-blue-900">← Back to Expenses</a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-gray-600">Expense ID:</span>
                                    <span class="font-medium"><?php echo $expense['expense_id']; ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Date:</span>
                                    <span class="font-medium"><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Category:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($expense['category_name']); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Amount:</span>
                                    <span class="font-medium">XAF <?php echo number_format($expense['amount'], 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Information -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4">Status Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-gray-600">Status:</span>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $expense['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                            ($expense['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo ucfirst($expense['status']); ?>
                                    </span>
                                </div>
                                <?php if ($expense['status'] !== 'pending'): ?>
                                    <div>
                                        <span class="text-gray-600">Reviewed By:</span>
                                        <span class="font-medium"><?php echo htmlspecialchars($expense['reviewer_name']); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Review Date:</span>
                                        <span class="font-medium"><?php echo date('M d, Y', strtotime($expense['reviewed_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4">Description</h3>
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($expense['description'])); ?></p>
                        </div>

                        <!-- Admin Comments -->
                        <?php if ($expense['status'] !== 'pending' && $expense['admin_comment']): ?>
                        <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4">Admin Comments</h3>
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($expense['admin_comment'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 