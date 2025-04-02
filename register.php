<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'];
    $company_email = $_POST['company_email'];
    $company_phone = $_POST['company_phone'];
    $company_address = $_POST['company_address'];
    
    // Check for duplicate company information before proceeding
    $errors = array();
    
    // Check duplicate company name
    $stmt = $conn->prepare("SELECT company_name FROM companies WHERE company_name = ?");
    $stmt->bind_param("s", $company_name);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "A company with the name '$company_name' is already registered";
    }
    
    // Check duplicate company email
    $stmt = $conn->prepare("SELECT company_email FROM companies WHERE company_email = ?");
    $stmt->bind_param("s", $company_email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "The email address '$company_email' is already registered to another company";
    }
    
    // Check duplicate company phone
    $stmt = $conn->prepare("SELECT company_phone FROM companies WHERE company_phone = ?");
    $stmt->bind_param("s", $company_phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "The phone number '$company_phone' is already registered to another company";
    }
    
    // Check duplicate admin email
    $admin_email = $_POST['admin_email'];
    $stmt = $conn->prepare("SELECT email FROM employees WHERE email = ?");
    $stmt->bind_param("s", $admin_email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "The admin email '$admin_email' is already registered";
    }
    
    // If there are any duplicate entries, stop and show errors
    if (!empty($errors)) {
        $error = "Registration failed:<br>" . implode("<br>", $errors);
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert company
            $sql = "INSERT INTO companies (company_name, company_email, company_phone, company_address) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $company_name, $company_email, $company_phone, $company_address);
            
            if (!$stmt->execute()) {
                throw new Exception("Error inserting company: " . $stmt->error);
            }
            
            $company_id = $conn->insert_id;
            
            // Insert admin employee
            $admin_first_name = $_POST['admin_first_name'];
            $admin_last_name = $_POST['admin_last_name'];
            $admin_code = $_POST['admin_code'];
            
            $sql = "INSERT INTO employees (company_id, first_name, last_name, email, employee_code, role) 
                    VALUES (?, ?, ?, ?, ?, 'admin')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $company_id, $admin_first_name, $admin_last_name, $admin_email, $admin_code);
            
            if (!$stmt->execute()) {
                throw new Exception("Error inserting admin: " . $stmt->error);
            }
            
            // Insert additional employees
            $employee_count = count($_POST['employee_first_name']);
            for ($i = 0; $i < $employee_count; $i++) {
                if (!empty($_POST['employee_first_name'][$i])) {
                    $first_name = $_POST['employee_first_name'][$i];
                    $last_name = $_POST['employee_last_name'][$i];
                    $email = $_POST['employee_email'][$i];
                    $employee_code = $_POST['employee_code'][$i];
                    
                    $sql = "INSERT INTO employees (company_id, first_name, last_name, email, employee_code) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issss", $company_id, $first_name, $last_name, $email, $employee_code);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error inserting employee $i: " . $stmt->error);
                    }
                }
            }
            
            $conn->commit();
            $_SESSION['success'] = "Company registered successfully! Please login.";
            header("Location: login.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Registration failed: " . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Company - Enterprise Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-4 px-3 sm:px-4 lg:px-6">
        <div class="flex flex-col md:flex-row w-full max-w-5xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Left side with image -->
            <div class="hidden md:flex md:w-1/2 bg-blue-600 items-center justify-center p-3">
                <div class="text-center">
                    <img src="assets/images/design-of-financial-chart-vector.jpg" 
                         alt="Financial Management" 
                         class="w-40 h-40 mx-auto mb-1 object-cover rounded-lg"
                         style="max-width: 100%; height: auto;">
                    <h3 class="text-white text-base font-semibold">Expense Manager</h3>
                    <p class="text-blue-100 text-xs mt-1">Streamline your expense tracking process</p>
                </div>
            </div>
            
            <!-- Right side with registration form -->
            <div class="w-full md:w-1/2 p-4">
                <h2 class="text-xl font-bold text-center text-gray-900 mb-3">Register Your Company</h2>
                
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-2 py-1.5 rounded mb-2 text-xs">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-3">
                    <!-- Company Information -->
                    <div class="border-b pb-3">
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Company Information</h3>
                        <div class="grid grid-cols-1 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Company Name</label>
                                <input type="text" name="company_name" required
                                       class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Company Email</label>
                                <input type="email" name="company_email" required
                                       class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Company Phone</label>
                                <input type="tel" name="company_phone" required
                                       class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Company Address</label>
                                <textarea name="company_address" required rows="1"
                                          class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Admin Information -->
                    <div class="border-b pb-3">
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Admin Account</h3>
                        <div class="grid grid-cols-1 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">First Name</label>
                                <input type="text" name="admin_first_name" required
                                       class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Last Name</label>
                                <input type="text" name="admin_last_name" required
                                       class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Email</label>
                                <input type="email" name="admin_email" required
                                       class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Employee Code</label>
                                <input type="text" name="admin_code" required
                                       class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Employees -->
                    <div id="employees-container">
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Employees credentials</h3>
                        <div class="employee-entry space-y-2">
                            <div class="grid grid-cols-1 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">First Name</label>
                                    <input type="text" name="employee_first_name[]"
                                           class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Last Name</label>
                                    <input type="text" name="employee_last_name[]"
                                           class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Email</label>
                                    <input type="email" name="employee_email[]"
                                           class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Employee id</label>
                                    <input type="text" name="employee_code[]"
                                           class="mt-0.5 block w-full border border-gray-300 rounded-md shadow-sm py-1 px-2 text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mb-2">
                        <p class="text-xs text-gray-600">
                            Already have an account? 
                            <a href="login.php?logout=1" class="font-medium text-blue-600 hover:text-blue-500">
                                Login here
                            </a>
                        </p>
                    </div>
                    
                    <div class="flex justify-between">
                        <button type="button" onclick="addEmployee()" 
                                class="bg-gray-500 text-white px-2 py-1 rounded-md hover:bg-gray-600 text-xs">
                            Add Another Employee
                        </button>
                        <div>
                            <button type="submit" 
                                class="w-full bg-blue-600 text-white px-2 py-1 rounded-md hover:bg-blue-700 text-xs">
                                Register Company
                            </button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="index.php" class="text-xs text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function addEmployee() {
            const container = document.getElementById('employees-container');
            const employeeEntry = document.querySelector('.employee-entry').cloneNode(true);
            
            // Clear input values
            employeeEntry.querySelectorAll('input').forEach(input => input.value = '');
            
            container.appendChild(employeeEntry);
        }
    </script>
</body>
</html> 