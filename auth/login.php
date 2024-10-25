<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Check if user is already logged in before including other files
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
        exit();
    } else {
        header('Location: ../user/dashboard.php');
        exit();
    }
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($password)) {
        $error = "All fields are required";
    } else {
        try {
            $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            if($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if(password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Clear any previous output
                    if(ob_get_length()) ob_clean();
                    
                    // Redirect based on role
                    if($user['role'] === 'admin') {
                        header('Location: ../admin/dashboard.php');
                    } else {
                        header('Location: ../user/dashboard.php');
                    }
                    exit();
                } else {
                    $error = "Invalid email or password";
                }
            } else {
                $error = "Invalid email or password";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login - Event Registration System</title>
    <script src="https://cdn.tailwindcss.com"></script> <!-- Tailwind CSS CDN -->
    <style>
        /* You can customize your Tailwind config if needed */
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-center">

    <!-- Main Container -->
    <div class="container mx-auto mt-16">

        <!-- Login Box -->
        <div class="max-w-md mx-auto bg-white p-8 border border-gray-300 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-center mb-6">Login</h2>
            <!-- Error Message Display -->
            <?php if (!empty($error)): ?>
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- Error Message Example -->

            <!-- Login Form -->
            <form method="POST" action="login.php">
                <div class="form-group mb-4">
                    <label for="email" class="block text-gray-700">Email:</label>
                    <input type="email" id="email" name="email" class="w-full p-2 border border-gray-300 rounded-lg" required placeholder="Enter your email">
                </div>

                <div class="form-group mb-4">
                    <label for="password" class="block text-gray-700">Password:</label>
                    <input type="password" id="password" name="password" class="w-full p-2 border border-gray-300 rounded-lg" required placeholder="Enter your password">
                </div>

                <div class="form-group">
                    <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-400 transition duration-200">
                        Login
                    </button>
                </div>
            </form>
            

            <!-- Additional Links -->
            <div class="text-center mt-4">
                <p>Don't have an account? <a href="register.php" class="text-blue-500 hover:underline">Register here</a></p>
                <p><a href="forgot-password.php" class="text-blue-500 hover:underline">Forgot Password?</a></p>
                <p><a href="../index.php" class="text-blue-500 hover:underline">Back to Home</a></p>
            </div>
        </div>
    </div>
    

    <!-- Optional Footer -->
    <footer class="text-center mt-8 text-gray-500">
        <p>&copy; 2024 Event Registration System. All rights reserved.</p>
    </footer>
</body>
</html>
