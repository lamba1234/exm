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
            <div class="flex items-center">
                <span class="text-lg font-semibold text-gray-800">Welcome back, <span class="text-blue-600"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span></span>
            </div>
        </div>
    </div>
</nav> 