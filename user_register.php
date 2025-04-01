<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    
    $errors = [];
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Validate phone number format
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match('/^\+237[0-9]{9}$/', $phone)) {
        $errors[] = "Invalid phone number format. Must be in Cameroon format (+237 followed by 9 digits)";
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, phone, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $email, $hashed_password, $full_name, $phone);
        
        if ($stmt->execute()) {
            $_SESSION['registration_success'] = true;
            header("Location: user_login.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - Expense Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row w-full max-w-4xl bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Left side with image -->
            <div class="hidden md:flex md:w-1/2 bg-blue-600 items-center justify-center p-8">
                <div class="text-center">
                    <img src="assets/images/design-of-financial-chart-vector.jpg" 
                         alt="Financial Management" 
                         class="w-64 h-64 mx-auto mb-4 object-cover rounded-lg"
                         style="max-width: 100%; height: auto;">
                    <h3 class="text-white text-xl font-semibold">Expense Manager</h3>
                    <p class="text-blue-100 mt-2">Streamline your expense tracking process</p>
                </div>
            </div>
            
            <!-- Right side with registration form -->
            <div class="w-full md:w-1/2 p-8">
                <div>
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        Create your user account
                    </h2>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['registration_success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mt-4" role="alert">
                        <span class="block sm:inline">Registration successful! Please login with your email and password.</span>
                    </div>
                    <?php 
                    unset($_SESSION['registration_success']);
                    ?>
                <?php endif; ?>
                
                <form class="mt-8 space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="rounded-md shadow-sm space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                            <input id="email" name="email" type="email" required 
                                class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input id="full_name" name="full_name" type="text" required 
                                class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input id="phone" name="phone" type="tel" required 
                                class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                placeholder="+237 6XXXXXXXX"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '+237'; ?>">
                            <p class="mt-1 text-sm text-gray-500">Enter your phone number starting with +237</p>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input id="password" name="password" type="password" required 
                                class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input id="confirm_password" name="confirm_password" type="password" required 
                                class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Register
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="text-sm text-gray-600">
                            Already have an account? 
                            <a href="user_login.php" class="font-medium text-blue-600 hover:text-blue-500">
                                Sign in here
                            </a>
                        </p>
                    </div>
                </form>
                
                <div class="text-center mt-6">
                    <a href="index.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('phone');
            
            // Set initial value if empty
            if (!phoneInput.value) {
                phoneInput.value = '+237';
            }
            
            // Ensure +237 is always present
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value;
                if (!value.startsWith('+237')) {
                    value = '+237' + value.replace(/^\+237/, '');
                }
                e.target.value = value;
            });
            
            // Prevent deletion of +237
            phoneInput.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' || e.key === 'Delete') {
                    if (this.value.length <= 4) { // +237 is 4 characters
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html> 
