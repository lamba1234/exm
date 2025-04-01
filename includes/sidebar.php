<!-- Sidebar -->
<div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 mt-16">
    <div class="h-16 flex items-center justify-between px-4 border-b">
        <span class="text-xl font-semibold text-gray-800">Menu</span>
        <button id="sidebar-toggle" class="lg:hidden text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    <nav class="mt-6">
        <div class="px-2 space-y-3">
            <a href="admin_dashboard.php" class="flex items-center text-left space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="add_expense.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Add Expense</span>
            </a>
            <a href="view_expenses.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span>View All Expenses</span>
            </a>
            <a href="manage_budgets.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Manage Budget</span>
            </a>
            <a href="manage_employees.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span>Manage Employees</span>
            </a>
        </div>
    </nav>
    <!-- Logout Link -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t">
        <a href="logout.php" class="flex items-center space-x-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: calc(100vh - 4rem); /* Subtract navbar height */
    width: 260px;
    z-index: 40;
    transition: transform 0.3s ease-in-out;
    margin-top: 4rem; /* Add margin to account for navbar */
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 30;
    display: none;
}

@media (max-width: 1024px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-overlay {
        display: block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    
    // Mobile sidebar toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 1024) {
            if (!sidebar.contains(event.target) && !event.target.closest('#sidebar-toggle')) {
                sidebar.classList.remove('active');
            }
        }
    });
});
</script> 