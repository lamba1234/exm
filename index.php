<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Manager - Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <a href="#about" class="text-gray-700 hover:text-blue-600">About Us</a>
                    <a href="#contact" class="text-gray-700 hover:text-blue-600">Contact Us</a>
                    <a href="login.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="pt-32 pb-20 px-4">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                Welcome to Expense Manager
            </h1>
            <p class="text-xl text-gray-600 mb-8">
                Streamline your expense management with our professional solution
            </p>
            <div class="space-x-4">
                <a href="register.php" class="bg-blue-600 text-white px-8 py-3 rounded-md text-lg hover:bg-blue-700">
                    Register as Company
                </a>
                <a href="user_register.php" class="bg-green-600 text-white px-8 py-3 rounded-md text-lg hover:bg-green-700">
                    Register as User
                </a>
            </div>
        </div>
    </div>

    <!-- About Us Section -->
    <div id="about" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">About Us</h2>
            <div class="max-w-3xl mx-auto text-center">
                <p class="text-lg text-gray-600 leading-relaxed">
                    Expense Manager is a comprehensive solution designed to help businesses and individuals manage their expenses efficiently. Our platform provides a user-friendly interface for tracking, submitting, and approving expenses while maintaining detailed records and generating insightful reports. We understand the challenges of expense management and have created a solution that simplifies the process while ensuring accuracy and compliance.
                </p>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <div id="contact" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Contact Us</h2>
            <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-lg p-8">
                <div class="space-y-6">
                    <div class="flex items-center">
                        <i class="fas fa-phone text-blue-600 text-xl mr-4"></i>
                        <span class="text-gray-700">+237 677 412 573</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt text-blue-600 text-xl mr-4"></i>
                        <span class="text-gray-700">Douala, Cameroon</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-envelope text-blue-600 text-xl mr-4"></i>
                        <span class="text-gray-700">christianndeh6@gmail.com</span>
                    </div>
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
</body>
</html> 