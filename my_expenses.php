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
$company_id = $_SESSION['company_id'];
$employee_id = $_SESSION['user_id'];

// Get company name
$sql = "SELECT company_name FROM companies WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();

// Set up filtering and pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Initialize base query with simpler structure
$sql = "SELECT e.*, c.category_name
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.category_id 
        WHERE e.employee_id = ?";

$params = [$employee_id];
$types = "i";

// Add status filter if provided
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $sql .= " AND e.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// Add date filters if provided
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $sql .= " AND e.expense_date >= ?";
    $params[] = $_GET['start_date'];
    $types .= "s";
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $sql .= " AND e.expense_date <= ?";
    $params[] = $_GET['end_date'];
    $types .= "s";
}

// Add ordering and pagination
$sql .= " ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error . "<br>SQL: " . $sql . "<br>Types: " . $types . "<br>Params: " . print_r($params, true));
}

// Verify parameter count matches
if (count($params) !== strlen($types)) {
    die("Parameter count mismatch. Types length: " . strlen($types) . ", Params count: " . count($params));
}

try {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $expenses = $stmt->get_result();
} catch (Exception $e) {
    die("Error executing statement: " . $e->getMessage());
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM expenses e WHERE e.employee_id = ?";
$count_params = [$employee_id];
$count_types = "i";

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $count_sql .= " AND e.status = ?";
    $count_params[] = $_GET['status'];
    $count_types .= "s";
}

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $count_sql .= " AND e.expense_date >= ?";
    $count_params[] = $_GET['start_date'];
    $count_types .= "s";
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $count_sql .= " AND e.expense_date <= ?";
    $count_params[] = $_GET['end_date'];
    $count_types .= "s";
}

$stmt = $conn->prepare($count_sql);
if ($stmt === false) {
    die("Error preparing count statement: " . $conn->error . "<br>SQL: " . $count_sql . "<br>Types: " . $count_types . "<br>Params: " . print_r($count_params, true));
}

// Verify count parameter count matches
if (count($count_params) !== strlen($count_types)) {
    die("Count parameter count mismatch. Types length: " . strlen($count_types) . ", Params count: " . count($count_params));
}

try {
    $stmt->bind_param($count_types, ...$count_params);
    $stmt->execute();
    $total_records = $stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);
} catch (Exception $e) {
    die("Error executing count statement: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Expenses - Enterprise Expense Tracker</title>
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
            <!-- Page Header -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-2xl font-bold text-gray-900">My Expenses</h2>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filters -->
                    <form method="GET" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All</option>
                                <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo isset($_GET['status']) && $_GET['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo isset($_GET['status']) && $_GET['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" name="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" name="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($expense = $expenses->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($expense['expense_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($expense['category_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($expense['description']); ?>
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
                                        <?php if ($expense['status'] !== 'pending' && $expense['admin_comment']): ?>
                                            <div class="mt-1 text-xs text-gray-500">
                                                <?php echo htmlspecialchars($expense['admin_comment']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-3">
                                            <a href="view_expense.php?id=<?php echo $expense['expense_id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">View</a>
                                            <?php if ($expense['status'] === 'pending'): ?>
                                                <a href="edit_expense.php?id=<?php echo $expense['expense_id']; ?>" 
                                                   class="text-yellow-600 hover:text-yellow-900">Edit</a>
                                                <a href="delete_expense.php?id=<?php echo $expense['expense_id']; ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this expense? This action cannot be undone.');"
                                                   class="text-red-600 hover:text-red-900">Delete</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="mt-4 flex justify-center">
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['start_date']) ? '&start_date=' . htmlspecialchars($_GET['start_date']) : ''; ?><?php echo isset($_GET['end_date']) ? '&end_date=' . htmlspecialchars($_GET['end_date']) : ''; ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 <?php echo $page === $i ? 'bg-blue-50' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 