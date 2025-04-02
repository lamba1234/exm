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
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

// Get company name

$company_sql = "SELECT company_name FROM companies WHERE company_id = ?";
$company_stmt = $conn->prepare($company_sql);
$company_stmt->bind_param("i", $company_id);
$company_stmt->execute();
$company_result = $company_stmt->get_result();
$company = $company_result->fetch_assoc();


// Get expense categories
$sql = "SELECT * FROM categories WHERE company_id = ? ORDER BY category_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$categories = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_date = $_POST['expense_date'];
    $amount = floatval($_POST['amount']);
    $category_name = $_POST['category_name'];
    $description = $_POST['description'];

    // Check if budget exists for the current month
    $budget_sql = "SELECT amount FROM company_budgets WHERE company_id = ? AND year = YEAR(?) AND month = MONTH(?)";
    $budget_stmt = $conn->prepare($budget_sql);
    $budget_stmt->bind_param("iss", $company_id, $expense_date, $expense_date);
    $budget_stmt->execute();
    $budget_result = $budget_stmt->get_result();
    $budget = $budget_result->fetch_assoc();

    if (!$budget) {
        $error = "No budget has been set for this month. Please contact your administrator.";
    } else {
        // Get total expenses for current month
        $expenses_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                        WHERE company_id = ? 
                        AND YEAR(expense_date) = YEAR(?) 
                        AND MONTH(expense_date) = MONTH(?)";
        $expenses_stmt = $conn->prepare($expenses_sql);
        $expenses_stmt->bind_param("iss", $company_id, $expense_date, $expense_date);
        $expenses_stmt->execute();
        $expenses_result = $expenses_stmt->get_result();
        $total_expenses = $expenses_result->fetch_assoc()['total'];

        // Check if adding this expense would exceed the budget
        if (($total_expenses + $amount) > $budget['amount']) {
            $error = "This expense would exceed the monthly budget of XAF " . number_format($budget['amount'], 2) . ". Please adjust the amount or contact your administrator.";
        } else {
            // Handle file upload
            $receipt_path = null;
            if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/receipts/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];

                if (in_array($file_extension, $allowed_extensions)) {
                    $file_name = uniqid() . '.' . $file_extension;
                    $receipt_path = $upload_dir.$file_name;
                    
                    if (move_uploaded_file($_FILES['receipt']['tmp_name'], $receipt_path)) {
                        // File uploaded successfully
                    } else {
                        $error = "Failed to upload receipt.";
                    }
                } else {
                    $error = "Invalid file type. Allowed types: JPG, JPEG, PNG, PDF";
                }
            }

            if (!isset($error)) {
                // Start transaction
                $conn->begin_transaction(); 
                
                try {
                    // Check if category exists
                    $sql = "SELECT category_id FROM categories WHERE company_id = ? AND category_name = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("is", $company_id, $category_name);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $category = $result->fetch_assoc();
                        $category_id = $category['category_id'];
                    } else {
                        // Create new category
                        $sql = "INSERT INTO categories (company_id, category_name) VALUES (?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("is", $company_id, $category_name);
                        $stmt->execute();
                        $category_id = $conn->insert_id;
                    }
                    
                    // Insert expense
                    $sql = "INSERT INTO expenses (company_id, employee_id, category_id, expense_date, amount, description, receipt_path, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iiissss", $company_id, $user_id, $category_id, $expense_date, $amount, $description, $receipt_path);
                    
                    if ($stmt->execute()) {
                        $conn->commit();
                        $_SESSION['success'] = "Expense submitted successfully. Waiting for approval.";
                        header("Location: my_expenses.php");
                        exit();
                    } else {
                        throw new Exception("Failed to submit expense");
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Failed to submit expense. Please try again.";
                }
            }
        }
    }
}

// Get current budget for display
$current_year = date('Y');
$current_month = date('n');
$budget_sql = "SELECT amount FROM company_budgets WHERE company_id = ? AND year = ? AND month = ?";
$budget_stmt = $conn->prepare($budget_sql);
$budget_stmt->bind_param("iii", $company_id, $current_year, $current_month);
$budget_stmt->execute();
$current_budget = $budget_stmt->get_result()->fetch_assoc();

// Get total expenses for current month
$expenses_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                WHERE company_id = ? 
                AND YEAR(expense_date) = ? 
                AND MONTH(expense_date) = ?";
$expenses_stmt = $conn->prepare($expenses_sql);
$expenses_stmt->bind_param("iii", $company_id, $current_year, $current_month);
$expenses_stmt->execute();
$expenses_result = $expenses_stmt->get_result();
$total_expenses = $expenses_result->fetch_assoc()['total'];

// Calculate remaining budget
$remaining_budget = ($current_budget['amount'] ?? 0) - $total_expenses;
$budget_utilization = ($current_budget['amount'] ?? 0) > 0 ? ($total_expenses / ($current_budget['amount'] ?? 1)) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Expense - Enterprise Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/notifications.js"></script>
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
                <a href="add_expense.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2 bg-blue-50">
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
                <a href="manage_budgets.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
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
        <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-md rounded-lg p-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-8">Add New Expense</h2>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($current_budget): ?>
                    <div class="bg-white shadow rounded-lg p-6 mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Budget Overview</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-blue-800">Monthly Budget</h3>
                                <p class="text-2xl font-bold text-blue-600">XAF <?php echo number_format($current_budget['amount'], 2); ?></p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-green-800">Total Expenses</h3>
                                <p class="text-2xl font-bold text-green-600">XAF <?php echo number_format($total_expenses, 2); ?></p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-yellow-800">Remaining Budget</h3>
                                <p class="text-2xl font-bold text-yellow-600">XAF <?php echo number_format($remaining_budget, 2); ?></p>
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
                <?php else: ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">No Budget Set</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>No budget has been set for this month. Please contact your administrator to set up a budget before submitting expenses.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($current_budget): ?>
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date</label>
                                <input type="date" name="expense_date" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Amount (XAF)</label>
                                <input type="number" name="amount" step="0.01" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div >
                                <label class="block text-sm font-medium text-gray-700">Category</label>
                                <select name="category_name" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select a category</option>
                                    <option value="Food & Beverages">Food & Beverages</option>
                                    <option value="Water & Utilities">Water & Utilities</option>
                                    <option value="Transportation">Transportation</option>
                                    <option value="Office Supplies">Office Supplies</option>
                                    <option value="Equipment & Maintenance">Equipment & Maintenance</option>
                                    <option value="Communication">Communication</option>
                                    <option value="Marketing & Advertising">Marketing & Advertising</option>
                                    <option value="Training & Development">Training & Development</option>
                                    <option value="Travel & Accommodation">Travel & Accommodation</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Receipt</label>
                                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required
                                       class="mt-1 block w-full text-sm text-gray-500
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-md file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-blue-50 file:text-blue-700
                                              hover:file:bg-blue-100">
                                <p class="mt-1 text-sm text-gray-500">Accepted formats: JPG, JPEG, PNG, PDF</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="3" required
                                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <a href="dashboard.php" 
                               class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                                Submit Expense
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 
