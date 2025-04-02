<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Manager - Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-bg {
            background: linear-gradient(135deg, #1a365d 0%, #2d3748 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill="rgba(255,255,255,0.05)"/></svg>');
            opacity: 0.1;
        }
        .dropdown-menu {
            display: none;
        }
        .dropdown-menu.show {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="#" class="text-2xl font-bold text-blue-600">Expense Manager</a>
                </div>
                <div class="flex items-center space-x-8">
                    <a href="contact.php" class="text-gray-700 hover:text-blue-600">Contact Us</a>
                    <div class="relative">
                        <button id="loginDropdownBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center">
                            Login
                            <i class="fas fa-chevron-down ml-2"></i>
                        </button>
                        <div id="loginDropdown" class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1">
                            <a href="user_login.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Login as User</a>
                            <a href="login.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Login as Employee</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-bg pt-32 pb-20 px-4 relative">
        <div class="max-w-7xl mx-auto text-center relative z-10">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                Welcome to Expense Manager
            </h1>
            <p class="text-xl text-gray-200 mb-8 max-w-2xl mx-auto">
                Streamline your expense management with our professional solution. Track, submit, and approve expenses with ease.
            </p>
            <div class="space-x-4">
                <a href="register.php" class="bg-blue-600 text-white px-8 py-3 rounded-md text-lg hover:bg-blue-700 transition duration-300">
                    Register as Company
                </a>
                <a href="user_register.php" class="bg-green-600 text-white px-8 py-3 rounded-md text-lg hover:bg-green-700 transition duration-300">
                    Register as User
                </a>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Why Choose Us</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-invoice text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Expense Reporting</h3>
                    <p class="text-gray-600">Generate comprehensive expense reports with detailed breakdowns and customizable formats for better financial tracking.</p>
                </div>
                <div class="text-center p-6">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Secure Platform</h3>
                    <p class="text-gray-600">Your data is protected with enterprise-grade security and encryption.</p>
                </div>
                <div class="text-center p-6">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-check text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">User Friendly</h3>
                    <p class="text-gray-600">Intuitive interface designed for seamless navigation and effortless expense management experience.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Expense Manager. All rights reserved.</p>
        </div>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownBtn = document.getElementById('loginDropdownBtn');
            const dropdownMenu = document.getElementById('loginDropdown');

            dropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html> 