<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Expense Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($_SESSION['user_id'])): ?>
    <script>
        // Update user activity status every 2 minutes
        setInterval(function() {
            fetch('update_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            });
        }, 120000);
    </script>
    <?php endif; ?>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="navbar bg-white shadow-lg">
        <div class="max-w-full mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-700 mr-4 lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <a href="<?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'; ?>" 
                       class="flex items-center space-x-2">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <div class="flex flex-col">
                            <span class="text-xl font-bold text-blue-600">Expense Manager</span>
                            <span class="text-xs text-gray-500">Smart Expense Tracking</span>
                        </div>
                    </a>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($_SESSION['company_name'])): ?>
                        <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['company_name']); ?></span>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="text-gray-700 hover:text-blue-600">Login</a>
                    <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Register Company</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.toggle('active');
        }
    }
    </script>
</body> 