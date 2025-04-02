<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$company_id = $_SESSION['company_id'];

// Handle employee deletion
if (isset($_POST['delete_employee'])) {
    $employee_id = $_POST['employee_id'];
    
    try {
        // Check if employee has any expenses
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM expenses WHERE employee_id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            throw new Exception("Cannot delete employee with existing expenses. Please deactivate the employee instead.");
        }
        
        // Delete employee
        $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ? AND company_id = ?");
        $stmt->bind_param("ii", $employee_id, $company_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Employee deleted successfully";
        } else {
            throw new Exception("Error deleting employee");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: admin_manage_employees.php");
    exit();
}

// Handle employee status update
if (isset($_POST['update_status'])) {
    $employee_id = $_POST['employee_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $stmt = $conn->prepare("UPDATE employees SET status = ? WHERE employee_id = ? AND company_id = ?");
        $stmt->bind_param("sii", $new_status, $employee_id, $company_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Employee status updated successfully";
        } else {
            throw new Exception("Error updating employee status");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: admin_manage_employees.php");
    exit();
}

// Get all employees for the company
$sql = "SELECT * FROM employees WHERE company_id = ? ORDER BY role DESC, last_name, first_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$employees = $stmt->get_result();

$page_title = "Manage Employees";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Expense Manager</title>
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
            </div>
        </div>
    </nav>

    <!-- Admin Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content pt-16">
        <div class="container mx-auto px-4 py-8">
            <!-- Page Header -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">Manage Employees</h2>
                        <a href="admin_add_employee.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Add New Employee
                        </a>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Employees Table -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($employee = $employees->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($employee['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($employee['employee_code']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $employee['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo ucfirst($employee['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $employee['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($employee['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <a href="admin_edit_employee.php?id=<?php echo $employee['employee_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900">Edit</a>
                                        
                                        <?php if ($employee['status'] === 'active'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                                                <input type="hidden" name="new_status" value="inactive">
                                                <button type="submit" name="update_status" 
                                                        class="text-yellow-600 hover:text-yellow-900"
                                                        onclick="return confirm('Are you sure you want to deactivate this employee?')">
                                                    Deactivate
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                                                <input type="hidden" name="new_status" value="active">
                                                <button type="submit" name="update_status" 
                                                        class="text-green-600 hover:text-green-900"
                                                        onclick="return confirm('Are you sure you want to activate this employee?')">
                                                    Activate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                                            <button type="submit" name="delete_employee" 
                                                    class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Are you sure you want to delete this employee? This action cannot be undone.')">
                                                Delete
                                            </button>
                                        </form>
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
        // Toggle sidebar on mobile
        document.getElementById('admin-sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-sidebar').classList.toggle('hidden');
        });
    </script>
</body>
</html> 