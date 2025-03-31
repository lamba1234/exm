<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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
        $receipt_path = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $receipt_path)) {
            $error_message = "Failed to upload receipt.";
        }
    } else {
        $error_message = "Invalid file type. Allowed types: JPG, JPEG, PNG, PDF";
    }
}

// Get expense categories
$sql = "SELECT * FROM categories WHERE user_id = ? ORDER BY category_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$categories = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $category_name = $_POST['category_name'];
    $description = $_POST['description'];
    $expense_date = $_POST['expense_date'];
    $user_id = $_SESSION['user_id'];

    // Get current budget
    $budget_sql = "SELECT amount FROM budget WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())";
    $budget_stmt = $conn->prepare($budget_sql);
    $budget_stmt->bind_param("i", $user_id);
    $budget_stmt->execute();
    $budget_result = $budget_stmt->get_result();
    $current_budget = $budget_result->fetch_assoc()['amount'] ?? 0;

    // Get total expenses for current month
    $expenses_sql = "SELECT SUM(amount) as total FROM expenses WHERE employee_id = ? AND MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())";
    $expenses_stmt = $conn->prepare($expenses_sql);
    $expenses_stmt->bind_param("i", $user_id);
    $expenses_stmt->execute();
    $expenses_result = $expenses_stmt->get_result();
    $total_expenses = $expenses_result->fetch_assoc()['total'] ?? 0;

    // Check if adding this expense would exceed the budget
    if ($current_budget > 0 && ($total_expenses + $amount) > $current_budget) {
        $error_message = "This expense would exceed your monthly budget of XAF " . number_format($current_budget, 2) . ". Please adjust the amount or update your budget.";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if category exists
            $sql = "SELECT category_id FROM categories WHERE user_id = ? AND category_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $category_name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $category = $result->fetch_assoc();
                $category_id = $category['category_id'];
            } else {
                // Create new category
                $sql = "INSERT INTO categories (user_id, category_name) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $user_id, $category_name);
                $stmt->execute();
                $category_id = $conn->insert_id;
            }

            // Insert expense
            $sql = "INSERT INTO expenses (employee_id, category_id, expense_date, amount, description, receipt_path, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissss", $user_id, $category_id, $expense_date, $amount, $description, $receipt_path);

            if ($stmt->execute()) {
                $conn->commit();
                $success_message = "Expense added successfully!";
            } else {
                throw new Exception("Failed to submit expense");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to submit expense. Please try again.";
        }
    }
}

// Get current budget for display
$budget_sql = "SELECT amount FROM budget WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())";
$budget_stmt = $conn->prepare($budget_sql);
$budget_stmt->bind_param("i", $_SESSION['user_id']);
$budget_stmt->execute();
$budget_result = $budget_stmt->get_result();
$current_budget = $budget_result->fetch_assoc()['amount'] ?? 0;

// Get total expenses for current month
$expenses_sql = "SELECT SUM(amount) as total FROM expenses WHERE employee_id = ? AND MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())";
$expenses_stmt = $conn->prepare($expenses_sql);
$expenses_stmt->bind_param("i", $_SESSION['user_id']);
$expenses_stmt->execute();
$expenses_result = $expenses_stmt->get_result();
$total_expenses = $expenses_result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - Expense Manager</title>
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
                    <a href="user_dashboard.php" class="text-2xl font-bold text-blue-600 ml-4">Expense Manager</a>
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
                    <a href="useraddexpense.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md bg-blue-50">
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
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Add New Expense</h1>

                <?php if (isset($success_message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Budget Overview -->
                <?php if ($current_budget > 0): ?>
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Monthly Budget Overview</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Total Budget</p>
                                <p class="text-lg font-bold text-blue-600">XAF <?php echo number_format($current_budget, 2); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Spent</p>
                                <p class="text-lg font-bold text-red-600">XAF <?php echo number_format($total_expenses, 2); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Remaining</p>
                                <p class="text-lg font-bold text-green-600">XAF <?php echo number_format($current_budget - $total_expenses, 2); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6" enctype="multipart/form-data">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (XAF)</label>
                        <div class="mt-1">
                            <input type="number" step="0.01" name="amount" id="amount" required
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <input type="text" name="category_name" required
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Enter category name">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <div class="mt-1">
                            <textarea name="description" id="description" rows="3" required
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
                        </div>
                    </div>

                    <div>
                        <label for="expense_date" class="block text-sm font-medium text-gray-700">Date</label>
                        <div class="mt-1">
                            <input type="date" name="expense_date" id="expense_date" required
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md" value="<?php echo date('Y-m-d'); ?>">
                        </div>
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

                    <div class="flex justify-end space-x-4">
                        <a href="user_dashboard.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Add Expense
                        </button>
                    </div>
                </form>
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

            // Set default date to today
            $('#expense_date').val(new Date().toISOString().split('T')[0]);
        });
    </script>
</body>
</html>
