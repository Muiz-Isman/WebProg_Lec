<?php
// auth/register.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] === 'admin') {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/user/dashboard.php');
    }
}

$error = '';
$success = '';

// Define allowed roles
$allowed_roles = ['user', 'admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = isset($_POST['role']) ? sanitize_input($_POST['role']) : 'user';
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!in_array($role, $allowed_roles)) {
        $error = "Invalid role selected";
    } else {
        // Check if email already exists
        $query = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if($stmt->fetchColumn() > 0) {
            $error = "Email already exists";
        } else {
            // Insert new user with role
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":role", $role);
            
            if($stmt->execute()) {
                $success = "Registration successful! Please login.";
                // Optional: Automatically redirect to login page after a few seconds
                header("refresh:2;url=login.php");
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Event Registration System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <div class="flex-grow flex items-center justify-center p-6">
        <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-semibold text-center text-gray-800 mb-6">Register</h2>
            
            <?php if($error): ?>
                <div class="bg-red-50 text-red-500 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-50 text-green-500 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="name" class="block text-gray-700 mb-2">Name:</label>
                    <input type="text" id="name" name="name" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
                        placeholder="Enter your name"
                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : ''; ?>">

                </div>

                <!-- <div>
    <label for="role" class="block text-gray-700 mb-2">Role:</label>
    <select id="role" name="role" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow">
        <option value="user">User</option>
        <option value="admin">Admin</option>
    </select>
                </div> -->
                
                <div>
                    <label for="email" class="block text-gray-700 mb-2">Email:</label>
                    <input type="email" id="email" name="email" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
                        placeholder="Enter your email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                        </div>
                
                <div>
                    <label for="password" class="block text-gray-700 mb-2">Password:</label>
                    <input type="password" id="password" name="password" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
                        placeholder="Enter your password">
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-gray-700 mb-2">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
                        placeholder="Confirm your password">
                </div>
                
                <button type="submit" 
                    class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600 transition-colors">
                    Register
                </button>
            </form>
            
            <div class="mt-6 space-y-2 text-center">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-blue-500 hover:text-blue-600 hover:underline">Login here</a>
                </p>
                <p class="text-gray-600">
                    <a href="../index.php" class="text-blue-500 hover:text-blue-600 hover:underline">Back to Home</a>
                </p>
            </div>
        </div>
    </div>
    
    <footer class="text-center py-4 text-gray-500">
        © 2024 Event Registration System. All rights reserved.
    </footer>
</body>
</html>