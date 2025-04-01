<?php
require_once 'init.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get current user data
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate current password if trying to change password
    if (!empty($new_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $error_message = "Current password is incorrect";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long";
        }
    }

    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $check_email = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
        $check_email->execute([':email' => $email, ':user_id' => $user_id]);
        if ($check_email->rowCount() > 0) {
            $error_message = "Email is already taken by another user";
        }
    }

    if (empty($error_message)) {
        try {
            $pdo->beginTransaction();

            // Update user information
            $update_query = "UPDATE users SET full_name = :full_name, phone = :phone, email = :email";
            $params = [
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':email' => $email,
                ':user_id' => $user_id
            ];

            // Update password if provided
            if (!empty($new_password)) {
                $update_query .= ", password = :password";
                $params[':password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $update_query .= " WHERE id = :user_id";
            $stmt = $pdo->prepare($update_query);
            $stmt->execute($params);

            $pdo->commit();
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $query = "SELECT * FROM users WHERE id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "An error occurred while updating your profile";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Expense Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'includes/user_header.php'; ?>
    <?php include 'includes/user_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="pt-20 pb-10">
        <div class="w-full">
            <div class="px-4">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">User Profile</h1>

                <?php if ($success_message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                       class="w-full rounded border border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                       class="w-full rounded border border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       class="w-full rounded border border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input type="password" name="current_password" 
                                       class="w-full rounded border border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500">Leave blank to keep current password</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" name="new_password" 
                                       class="w-full rounded border border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" 
                                       class="w-full rounded border border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition-colors">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#menu-toggle').click(function() {
                $('#sidebar').toggleClass('-translate-x-full');
            });

            // Close sidebar when clicking outside
            $(document).click(function(event) {
                if (!$(event.target).closest('#sidebar, #menu-toggle').length) {
                    $('#sidebar').addClass('-translate-x-full');
                }
            });
        });
    </script>
</body>
</html> 