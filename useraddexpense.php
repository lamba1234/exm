<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: user_login.php");
    
    exit();
}

$user_id = $_SESSION['user_id'];

// Get expense categories
$sql = "SELECT * FROM user_categories WHERE user_id = ? ORDER BY category_name";
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

    // Get current budget (only for normal users)
    $current_budget = 0;
    $total_expenses = 0;
    if ($_SESSION['user_type'] === 'user') {
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
    }

    // Check if adding this expense would exceed the budget (only for normal users)
    if ($_SESSION['user_type'] === 'user' && $current_budget > 0 && ($total_expenses + $amount) > $current_budget) {
        $error_message = "This expense would exceed your budget of XAF " . number_format($current_budget, 2) . ". Please adjust the amount or update your budget.";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if category exists
            $sql = "SELECT category_id FROM user_categories WHERE user_id = ? AND category_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $category_name);
            $stmt->execute();
            $result = $stmt->get_result();            

            if ($result->num_rows > 0) {
                $category = $result->fetch_assoc();
                $category_id = $category['category_id'];
            } else {
                // Create new category
                $sql = "INSERT INTO user_categories (user_id, category_name) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $user_id, $category_name);
                $stmt->execute();
                $category_id = $conn->insert_id;
            }

            // Debug information
            error_log("Attempting to insert expense with values:");
            error_log("user_id: " . $user_id);
            error_log("category_id: " . $category_id);
            error_log("expense_date: " . $expense_date);
            error_log("amount: " . $amount);
            error_log("description: " . $description);

            // Insert expense
            $sql = "INSERT INTO expenses_user (user_id, category_id, expense_date, amount, description) 
            VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisss", $user_id, $category_id, $expense_date, $amount, $description);

            if ($stmt->execute()) {
                $conn->commit();
                $success_message = "Expense added successfully!";
            } else {
                throw new Exception("Failed to submit expense: " . $stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get current budget for display (only for normal users)
$current_budget = 0;
$total_expenses = 0;
if ($_SESSION['user_type'] === 'user') {
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
}
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
    <?php include 'includes/user_header.php'; ?>
    <?php include 'includes/user_sidebar.php'; ?>

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
                <?php if ($_SESSION['user_type'] === 'user' && $current_budget > 0): ?>
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Budget Overview</h2>
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

                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (XAF)</label>
                        <div class="mt-1">
                            <input type="number" step="0.01" name="amount" id="amount" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div>
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
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <div class="mt-1">
                            <textarea name="description" id="description" rows="3"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>

                    <div>
                        <label for="expense_date" class="block text-sm font-medium text-gray-700">Date</label>
                        <div class="mt-1">
                            <input type="date" name="expense_date" id="expense_date" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" value="<?php echo date('Y-m-d'); ?>">
                        </div>
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
