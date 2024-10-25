<?php
// auth/reset-password.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

// Verify token
if (!isset($_GET['token'])) {
    header('Location: login.php');
    exit();
}

$token = $_GET['token'];
$database = new Database();
$db = $database->getConnection();

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        try {
            // Verify token and get user
            $query = "SELECT pr.user_id 
                     FROM password_resets pr 
                     WHERE pr.token = :token 
                     AND pr.expires_at > NOW() 
                     AND pr.used = 0 
                     LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":token", $token);
            $stmt->execute();
            
            if ($reset = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = :password WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":password", $hashed_password);
                $stmt->bindParam(":user_id", $reset['user_id']);
                $stmt->execute();
                
                // Mark token as used
                $query = "UPDATE password_resets SET used = 1 WHERE token = :token";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":token", $token);
                $stmt->execute();
                
                $success = "Password has been reset successfully. You can now login with your new password.";
            } else {
                $error = "Invalid or expired reset token.";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Event Registration System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-center">
    <div class="container mx-auto mt-16">
        <div class="max-w-md mx-auto bg-white p-8 border border-gray-300 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-center mb-6">Reset Password</h2>
            
            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
                    <p class="mt-2">
                        <a href="login.php" class="text-green-700 underline">Click here to login</a>
                    </p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700">New Password:</label>
                        <input type="password" id="password" name="password" required
                               class="w-full p-2 border border-gray-300 rounded-lg"
                               placeholder="Enter new password">
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="block text-gray-700">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full p-2 border border-gray-300 rounded-lg"
                               placeholder="Confirm new password">
                    </div>

                    <div class="form-group">
                        <button type="submit" 
                                class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-400 transition duration-200">
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>