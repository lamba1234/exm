<!-- Sidebar Navigation -->
<div id="sidebar" class="fixed left-0 top-16 w-64 bg-white shadow-lg h-screen transform -translate-x-full transition-transform duration-300 ease-in-out z-40">
    <div class="p-4">
        <ul class="space-y-2">
            <li>
                <a href="user_dashboard.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'bg-blue-50' : ''; ?>">
                    <i class="fas fa-home mr-2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="useraddexpense.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'useraddexpense.php' ? 'bg-blue-50' : ''; ?>">
                    <i class="fas fa-plus mr-2"></i> Add Expense
                </a>
            </li>
            <li>
                <a href="usermanage_expense.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'usermanage_expense.php' ? 'bg-blue-50' : ''; ?>">
                    <i class="fas fa-list mr-2"></i> Manage Expenses
                </a>
            </li>
            <li>
                <a href="usermanagereports.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'usermanagereports.php' ? 'bg-blue-50' : ''; ?>">
                    <i class="fas fa-chart-bar mr-2"></i> View Reports
                </a>
            </li>
            <li>
                <a href="managebudget.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'managebudget.php' ? 'bg-blue-50' : ''; ?>">
                    <i class="fas fa-wallet mr-2"></i> Budget
                </a>
            </li>
            <li>
                <a href="user_profile.php" class="flex items-center p-2 text-gray-700 hover:bg-blue-50 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'user_profile.php' ? 'bg-blue-50' : ''; ?>">
                    <i class="fas fa-user mr-2"></i> Profile
                </a>
            </li>
        </ul>
    </div>
    <!-- Logout Link -->
    <div class="absolute bottom-20 left-0 right-0 p-4 border-t">
        <a href="logout.php" class="flex items-center p-2 text-red-600 hover:bg-red-50 rounded-md">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
    </div>
</div> 