<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$company_id = $_SESSION['company_id'];

// Handle expense approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_id = $_POST['expense_id'];
    $action = $_POST['action'];
    $comment = $_POST['comment'] ?? '';

    try {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        
        $sql = "UPDATE expenses SET 
                status = ?, 
                admin_comment = ?,
                reviewed_by = ?,
                reviewed_at = NOW() 
                WHERE expense_id = ? AND company_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiii", $status, $comment, $_SESSION['user_id'], $expense_id, $company_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Expense has been " . $status;
        } else {
            throw new Exception("Error updating expense");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: manage_expenses.php");
    exit();
}

// Get all expenses for the company
$sql = "SELECT e.*, 
        c.category_name, 
        CONCAT(emp.first_name, ' ', emp.last_name) as employee_name,
        CONCAT(admin.first_name, ' ', admin.last_name) as reviewer_name
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.category_id 
        LEFT JOIN employees emp ON e.employee_id = emp.employee_id
        LEFT JOIN employees admin ON e.reviewed_by = admin.employee_id
        WHERE e.company_id = ? 
        ORDER BY e.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$expenses = $stmt->get_result();

$page_title = "Manage Expenses";
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8 mt-16">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6">Manage Expenses</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($expense = $expenses->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo date('M d, Y', strtotime($expense['expense_date'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($expense['employee_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($expense['category_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            XAF <?php echo number_format($expense['amount'], 2); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php
                                switch ($expense['status']) {
                                    case 'approved':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'rejected':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    default:
                                        echo 'bg-yellow-100 text-yellow-800';
                                }
                                ?>">
                                <?php echo ucfirst($expense['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php if ($expense['receipt_path']): ?>
                                <button onclick="viewReceipt('<?php echo htmlspecialchars($expense['receipt_path']); ?>')"
                                        class="text-blue-600 hover:text-blue-900">
                                    View Receipt
                                </button>
                            <?php else: ?>
                                No Receipt
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if ($expense['status'] === 'pending'): ?>
                                <button onclick="showActionModal(<?php echo $expense['expense_id']; ?>, 'approve')"
                                        class="text-green-600 hover:text-green-900 mr-3">
                                    Approve
                                </button>
                                <button onclick="showActionModal(<?php echo $expense['expense_id']; ?>, 'reject')"
                                        class="text-red-600 hover:text-red-900">
                                    Reject
                                </button>
                            <?php else: ?>
                                <span class="text-gray-500">
                                    <?php 
                                    echo "Reviewed by " . htmlspecialchars($expense['reviewer_name']) . "<br>";
                                    echo "on " . date('M d, Y H:i', strtotime($expense['reviewed_at']));
                                    if ($expense['admin_comment']) {
                                        echo "<br>Comment: " . htmlspecialchars($expense['admin_comment']);
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white p-8 rounded-lg max-w-2xl w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Receipt Image</h3>
            <button onclick="closeReceiptModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="receipt-container">
            <img id="receiptImage" src="" alt="Receipt" class="max-w-full h-auto">
        </div>
    </div>
</div>

<!-- Action Modal -->
<div id="actionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white p-8 rounded-lg max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 id="actionTitle" class="text-xl font-bold">Approve/Reject Expense</h3>
            <button onclick="closeActionModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" id="expense_id" name="expense_id">
            <input type="hidden" id="action" name="action">
            
            <div>
                <label for="comment" class="block text-sm font-medium text-gray-700">Comment (Optional)</label>
                <textarea id="comment" name="comment" rows="3" 
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          placeholder="Add a comment about your decision..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeActionModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="confirmButton"
                        class="px-4 py-2 rounded-md text-white">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function viewReceipt(path) {
    document.getElementById('receiptImage').src = path;
    document.getElementById('receiptModal').classList.remove('hidden');
    document.getElementById('receiptModal').classList.add('flex');
}

function closeReceiptModal() {
    document.getElementById('receiptModal').classList.remove('flex');
    document.getElementById('receiptModal').classList.add('hidden');
}

function showActionModal(expenseId, action) {
    document.getElementById('expense_id').value = expenseId;
    document.getElementById('action').value = action;
    document.getElementById('actionTitle').textContent = action === 'approve' ? 'Approve Expense' : 'Reject Expense';
    document.getElementById('confirmButton').className = 
        action === 'approve' 
            ? 'bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md'
            : 'bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md';
    document.getElementById('confirmButton').textContent = action === 'approve' ? 'Approve' : 'Reject';
    
    document.getElementById('actionModal').classList.remove('hidden');
    document.getElementById('actionModal').classList.add('flex');
}

function closeActionModal() {
    document.getElementById('actionModal').classList.remove('flex');
    document.getElementById('actionModal').classList.add('hidden');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const receiptModal = document.getElementById('receiptModal');
    const actionModal = document.getElementById('actionModal');
    
    if (event.target === receiptModal) {
        closeReceiptModal();
    }
    if (event.target === actionModal) {
        closeActionModal();
    }
}
</script> 