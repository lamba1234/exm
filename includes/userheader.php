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

<!-- Sidebar -->
<div id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition duration-200 ease-in-out z-40 w-64 bg-white shadow-lg">
    <div class="flex items-center justify-center h-16 border-b">
        <span class="text-xl font-semibold text-gray-800">Menu</span>
    </div>
    <nav class="mt-6">
        <div class="px-2 space-y-3">
            <a href="user_dashboard.php" class="flex items-center text-left space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <i class="fas fa-home w-6"></i>
                <span>Dashboard</span>
            </a>
            <a href="useraddexpense.php" class="flex items-center text-left space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <i class="fas fa-plus w-6"></i>
                <span>Add Expense</span>
            </a>
            <a href="usermanage_expense.php" class="flex items-center text-left space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <i class="fas fa-list w-6"></i>
                <span>Manage Expenses</span>
            </a>
            <a href="user_expenses.php" class="flex items-center text-left space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <i class="fas fa-chart-bar w-6"></i>
                <span>View Reports</span>
            </a>
            <a href="user_budget.php" class="flex items-center text-left space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <i class="fas fa-wallet w-6"></i>
                <span>Budget</span>
            </a>
            <a href="user_profile.php" class="flex items-center text-left space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <i class="fas fa-user w-6"></i>
                <span>Profile</span>
            </a>
        </div>
    </nav>
</div>

<!-- Overlay for mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-30 hidden"></div>