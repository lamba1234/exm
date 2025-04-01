<?php
require_once 'init.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'day';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Adjust dates based on report type
switch($search_type) {
    case 'week':
        $start_date = date('Y-m-d', strtotime($start_date . ' - ' . date('w', strtotime($start_date)) . ' days'));
        $end_date = date('Y-m-d', strtotime($end_date . ' + ' . (6 - date('w', strtotime($end_date))) . ' days'));
        break;
    case 'month':
        $start_date = date('Y-m-01', strtotime($start_date));
        $end_date = date('Y-m-t', strtotime($end_date));
        break;
    default: // day
        $start_date = date('Y-m-d', strtotime($start_date));
        $end_date = date('Y-m-d', strtotime($end_date));
}

// Get expenses for display
$query = "SELECT e.*, c.category_name 
          FROM expenses_user e 
          JOIN user_categories c ON e.category_id = c.category_id 
          WHERE e.user_id = :user_id AND e.expense_date BETWEEN :start_date AND :end_date
          ORDER BY e.expense_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([
    ':user_id' => $user_id,
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total expenses
$total = 0;
foreach ($result as $row) {
    $total += $row['amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Reports - Expense Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'includes/user_header.php'; ?>
    <?php include 'includes/user_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="pt-20 pb-10">
        <div class="w-full">
            <div class="px-4">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">Expense Reports</h1>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <!-- Search Form -->
                    <form method="GET" class="mb-8" id="reportForm">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                                <select name="search_type" id="reportType" class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="day" <?php echo $search_type == 'day' ? 'selected' : ''; ?>>Daily Report</option>
                                    <option value="week" <?php echo $search_type == 'week' ? 'selected' : ''; ?>>Weekly Report</option>
                                    <option value="month" <?php echo $search_type == 'month' ? 'selected' : ''; ?>>Monthly Report</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <div id="startDateContainer">
                                    <!-- Input will be dynamically inserted here -->
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                <div id="endDateContainer">
                                    <!-- Input will be dynamically inserted here -->
                                </div>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                                    Generate Report
                                </button>
                            </div>
                        </div>
                    </form>

                    <?php if (!empty($result)): ?>
                    <!-- Results Table -->
                    <div class="overflow-x-auto">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm text-gray-600">Showing <?php echo count($result); ?> transactions</span>
                            <button onclick="window.print()" class="text-sm text-gray-600 hover:text-gray-800 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                </svg>
                                Print Report
                            </button>
                        </div>

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($result as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('Y-m-d', strtotime($row['expense_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                            <?php echo htmlspecialchars($row['category_name']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo number_format($row['amount'], 2); ?> XAF
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($row['description']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <th colspan="2" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-900"><?php echo number_format($total, 2); ?> XAF</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        No transactions found for the selected period.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function createDateInput(type, value, name) {
        const input = document.createElement('input');
        input.type = type;
        input.name = name;
        input.value = value;
        input.className = 'w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500';
        return input;
    }

    function updateDateInputs() {
        const reportType = document.getElementById('reportType').value;
        const startDateContainer = document.getElementById('startDateContainer');
        const endDateContainer = document.getElementById('endDateContainer');
        const currentStartDate = startDateContainer.querySelector('input')?.value || '<?php echo $start_date; ?>';
        const currentEndDate = endDateContainer.querySelector('input')?.value || '<?php echo $end_date; ?>';

        // Clear existing inputs
        startDateContainer.innerHTML = '';
        endDateContainer.innerHTML = '';

        switch(reportType) {
            case 'week':
                // Create week input
                startDateContainer.appendChild(createDateInput('week', currentStartDate, 'start_date'));
                endDateContainer.appendChild(createDateInput('week', currentEndDate, 'end_date'));
                break;

            case 'month':
                // Create month input
                startDateContainer.appendChild(createDateInput('month', currentStartDate, 'start_date'));
                endDateContainer.appendChild(createDateInput('month', currentEndDate, 'end_date'));
                break;

            default: // day
                // Create date input
                startDateContainer.appendChild(createDateInput('date', currentStartDate, 'start_date'));
                endDateContainer.appendChild(createDateInput('date', currentEndDate, 'end_date'));
                break;
        }
    }

    // Initial setup
    updateDateInputs();

    // Update inputs when report type changes
    document.getElementById('reportType').addEventListener('change', updateDateInputs);

    // Sidebar toggle functionality
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
    });
    </script>

    <style>
    @media print {
        .container {
            padding: 0 !important;
        }
        .shadow-md {
            box-shadow: none !important;
        }
        .rounded-lg {
            border-radius: 0 !important;
        }
        button {
            display: none !important;
        }
        .bg-gray-50 {
            background-color: #f9fafb !important;
        }
        .main-content {
            margin-left: 0 !important;
        }
    }
    </style>
</body>
</html> 