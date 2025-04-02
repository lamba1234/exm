<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];

// Get company name
$sql = "SELECT company_name FROM companies WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();

// Get monthly expense statistics for all employees
$sql = "SELECT 
            e.employee_id,
            CONCAT(emp.first_name, ' ', emp.last_name) as employee_name,
            COUNT(*) as total_expenses,
            COALESCE(SUM(CASE WHEN e.status = 'approved' THEN e.amount ELSE 0 END), 0) as approved_total,
            COALESCE(SUM(CASE WHEN e.status = 'pending' THEN e.amount ELSE 0 END), 0) as pending_total,
            COALESCE(SUM(CASE WHEN e.status = 'rejected' THEN e.amount ELSE 0 END), 0) as rejected_total
        FROM expenses e 
        LEFT JOIN employees emp ON e.employee_id = emp.employee_id 
        WHERE e.company_id = ? 
        AND MONTH(e.expense_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(e.expense_date) = YEAR(CURRENT_DATE())
        GROUP BY e.employee_id, emp.first_name, emp.last_name
        ORDER BY approved_total DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$monthly_stats = $stmt->get_result();

// Get expense trends for the last 6 months
$sql = "SELECT 
            DATE_FORMAT(expense_date, '%Y-%m') as month,
            COUNT(*) as total_expenses,
            COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as approved_total,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_total,
            COALESCE(SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END), 0) as rejected_total
        FROM expenses 
        WHERE company_id = ? 
        AND expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
        ORDER BY month DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$trends = $stmt->get_result();

$page_title = "Admin Reports";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - Expense Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <!-- Page Title -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-2xl font-bold text-gray-900">Expense Reports</h2>
                    <p class="mt-1 text-gray-600">View detailed expense reports and analytics</p>
                </div>
            </div>

            <!-- Monthly Statistics -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Expense Statistics</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Expenses</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rejected</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($stat = $monthly_stats->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($stat['employee_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $stat['total_expenses']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                        XAF <?php echo number_format($stat['approved_total'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">
                                        XAF <?php echo number_format($stat['pending_total'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                        XAF <?php echo number_format($stat['rejected_total'], 2); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Expense Trends -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Expense Trends (Last 6 Months)</h3>
                    <div class="h-96">
                        <canvas id="expenseTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prepare data for the chart
        const trends = <?php 
            $chartData = [];
            while ($trend = $trends->fetch_assoc()) {
                $chartData[] = [
                    'month' => date('M Y', strtotime($trend['month'] . '-01')),
                    'approved' => $trend['approved_total'],
                    'pending' => $trend['pending_total'],
                    'rejected' => $trend['rejected_total']
                ];
            }
            echo json_encode(array_reverse($chartData));
        ?>;

        // Create the chart
        const ctx = document.getElementById('expenseTrendsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trends.map(t => t.month),
                datasets: [
                    {
                        label: 'Approved',
                        data: trends.map(t => t.approved),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Pending',
                        data: trends.map(t => t.pending),
                        borderColor: 'rgb(234, 179, 8)',
                        backgroundColor: 'rgba(234, 179, 8, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Rejected',
                        data: trends.map(t => t.rejected),
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'XAF ' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': XAF ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 