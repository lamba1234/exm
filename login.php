<?php
session_start();
require_once 'config.php';

// Handle logout parameter
if (isset($_GET['logout'])) {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $code = trim($_POST['employee_code']);
    $company_name = trim($_POST['company_name']);
    
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    if (empty($code)) {
        $errors[] = "Employee Code is required";
    }

    if (empty($company_name)) {
        $errors[] = "Company Name is required";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT e.*, c.company_name FROM employees e JOIN companies c ON e.company_id = c.company_id WHERE e.email = ? AND e.employee_code = ? AND c.company_name = ?");
        $stmt->bind_param("sss", $email, $code, $company_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $employee = $result->fetch_assoc();
            $_SESSION['user_id'] = $employee['employee_id'];
            $_SESSION['username'] = $employee['first_name'] . ' ' . $employee['last_name'];
            $_SESSION['full_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
            $_SESSION['user_type'] = 'employee';
            $_SESSION['company_id'] = $employee['company_id'];
            $_SESSION['company_name'] = $employee['company_name'];
            $_SESSION['role'] = $employee['role'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "Invalid email, employee code, or company name";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login - Expense Manager</title>
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
            
            <!-- Right side with login form -->
            <div class="w-full md:w-1/2 p-8">
                <div>
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        Employee Sign In
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
                
                <form class="mt-8 space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="rounded-md shadow-sm space-y-4">
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                            <input id="company_name" name="company_name" type="text" required 
                                class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                            <input id="email" name="email" type="email" required 
                                class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label for="employee_code" class="block text-sm font-medium text-gray-700">Employee Code</label>
                            <input id="employee_code" name="employee_code" type="text" required 
                                class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Sign in
                        </button>
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
</body>
</html>
