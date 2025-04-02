<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Expense Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold text-blue-600">Expense Managers</a>
                </div>
                <div class="flex items-center space-x-8">
                    <a href="login.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contact Section -->
    <div class="pt-32 pb-20 px-4">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Contact Us</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Contact Information -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h3 class="text-2xl font-semibold text-gray-900 mb-6">Get in Touch</h3>
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

                <!-- Contact Form -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h3 class="text-2xl font-semibold text-gray-900 mb-6">Send us a Message</h3>
                    <form class="space-y-4">
                        <div>
                            <label class="block text-gray-700 mb-2" for="name">Name</label>
                            <input type="text" id="name" name="name" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2" for="email">Email</label>
                            <input type="email" id="email" name="email" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2" for="message">Message</label>
                            <textarea id="message" name="message" rows="4" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition duration-300">
                            Send Message
                        </button>
                    </form>
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