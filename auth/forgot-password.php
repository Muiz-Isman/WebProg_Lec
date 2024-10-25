<?php
// auth/forgot-password.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';
$new_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($email)) {
        $error = "Email is required";
    } else {
        try {
            // Check if email exists
            $query = "SELECT user_id, name FROM users WHERE email = :email LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            if($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Generate new password
                $new_password = generateRandomPassword(); // 8 karakter random
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in database
                $query = "UPDATE users SET password = :password WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":password", $hashed_password);
                $stmt->bindParam(":user_id", $user['user_id']);
                $stmt->execute();
                
                $success = "Your password has been reset. Please save your new password:";
            } else {
                $error = "Email address not found.";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}

// Function to generate random password
function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Event Registration System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-center">
    <div class="container mx-auto mt-16">
        <div class="max-w-md mx-auto bg-white p-8 border border-gray-300 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-center mb-6">Forgot Password</h2>
            
            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                <div class="mt-4 p-4 bg-gray-100 rounded text-center">
                        <span class="font-bold text-xl"><?php echo htmlspecialchars($new_password, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                    <p class="mt-4 text-center">
                        <a href="login.php" class="text-blue-500 hover:underline">Click here to login</a>
                    </p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700">Email Address:</label>
                        <input type="email" id="email" name="email" required
                               class="w-full p-2 border border-gray-300 rounded-lg"
                               placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <button type="submit" 
                                class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-400 transition duration-200">
                            Get New Password
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p><a href="login.php" class="text-blue-500 hover:underline">Back to Login</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>