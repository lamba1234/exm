<!-- Admin Sidebar -->
<div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40">
    <div class="h-full flex flex-col">
        <!-- Sidebar Header -->
        <div class="h-16 flex items-center justify-between px-4 border-b">
            <span class="text-xl font-semibold text-gray-800">Admin Menu</span>
            <button id="admin-sidebar-toggle" class="lg:hidden text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto py-4">
            <div class="px-2 space-y-1">
                <a href="admin_dashboard.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'bg-blue-50' : ''; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="admin_manage_employees.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_manage_employees.php' ? 'bg-blue-50' : ''; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Manage Employees</span>
                </a>
                <a href="manage_budgets.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2 <?php echo basename($_SERVER['PHP_SELF']) == 'manage_budgets.php' ? 'bg-blue-50' : ''; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Manage Budget</span>
                </a>
                <a href="admin_reports.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg p-2 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reports.php' ? 'bg-blue-50' : ''; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>View Reports</span>
                </a>
            </div>
        </nav>

        <!-- Logout Link -->
        <div class="p-4 border-t">
            <a href="logout.php" class="flex items-center space-x-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<!-- Sidebar Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-gray-600 bg-opacity-75 z-30 hidden lg:hidden"></div>

<style>
/* Admin Sidebar Styles */
#sidebar {
    top: 0;
    height: 100vh;
}

#sidebar-overlay {
    transition: opacity 0.3s ease-in-out;
}

/* Main content adjustment */
.main-content {
    margin-left: 0;
    transition: margin-left 0.3s ease-in-out;
}

@media (min-width: 1024px) {
    .main-content {
        margin-left: 16rem; /* 256px - width of sidebar */
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('admin-sidebar-toggle');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
    }
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }
});
</script> 