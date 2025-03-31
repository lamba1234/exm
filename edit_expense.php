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
$sql = "SELECT e.*, c.category_name 
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.category_id 
        WHERE e.expense_id = ? AND e.employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $expense_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Expense not found or you don't have permission to edit it.";
    header("Location: my_expenses.php");
    exit();
}

$expense = $result->fetch_assoc();

// Only allow editing of pending expenses
if ($expense['status'] !== 'pending') {
    $_SESSION['error'] = "Only pending expenses can be edited.";
    header("Location: my_expenses.php");
    exit();
}

// Get categories for the dropdown
$categories_sql = "SELECT category_id, category_name FROM categories ORDER BY category_name";
$categories_result = $conn->query($categories_sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $expense_date = $_POST['expense_date'];
    
    // Handle file upload if a new receipt is provided
    $receipt_path = $expense['receipt_path'];
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/receipts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $new_filepath = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $new_filepath)) {
            // Delete old receipt if it exists
            if ($receipt_path && file_exists($receipt_path)) {
                unlink($receipt_path);
            }
            $receipt_path = $new_filepath;
        }
    }
    
    // Update the expense
    $update_sql = "UPDATE expenses SET 
                   category_id = ?, 
                   amount = ?, 
                   description = ?, 
                   expense_date = ?";
    
    $types = "idss";
    $params = [$category_id, $amount, $description, $expense_date];
    
    if ($receipt_path !== $expense['receipt_path']) {
        $update_sql .= ", receipt_path = ?";
        $types .= "s";
        $params[] = $receipt_path;
    }
    
    $update_sql .= " WHERE expense_id = ? AND employee_id = ?";
    $types .= "ii";
    $params[] = $expense_id;
    $params[] = $user_id;
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param($types, ...$params);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Expense updated successfully.";
        header("Location: my_expenses.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating expense. Please try again.";
    }
}

// Get company name for the header
$company_sql = "SELECT company_name FROM companies WHERE company_id = ?";
$company_stmt = $conn->prepare($company_sql);
$company_stmt->bind_param("i", $_SESSION['company_id']);
$company_stmt->execute();
$company = $company_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Expense - Enterprise Expense Tracker</title>
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
                        <h2 class="text-2xl font-bold text-gray-900">Edit Expense</h2>
                        <a href="my_expenses.php" class="text-blue-600 hover:text-blue-900">← Back to Expenses</a>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="category_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>" 
                                            <?php echo $category['category_id'] == $expense['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount (XAF)</label>
                            <input type="number" name="amount" step="0.01" required 
                                   value="<?php echo htmlspecialchars($expense['amount']); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" name="expense_date" required 
                                   value="<?php echo date('Y-m-d', strtotime($expense['expense_date'])); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" required rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo htmlspecialchars($expense['description']); ?></textarea>
                        </div>

                        <!-- Receipt -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Receipt</label>
                            <?php if ($expense['receipt_path']): ?>
                                <div class="mt-2">
                                    <a href="<?php echo htmlspecialchars($expense['receipt_path']); ?>" 
                                       target="_blank" 
                                       class="text-blue-600 hover:text-blue-900">
                                        View Current Receipt
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="receipt" accept="image/*,.pdf"
                                   class="mt-1 block w-full text-sm text-gray-500
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-50 file:text-blue-700
                                          hover:file:bg-blue-100">
                            <p class="mt-1 text-sm text-gray-500">Upload a new receipt to replace the current one (optional)</p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Update Expense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 