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
</head>
<body>
    <div>
        <h2>Register</h2>
        <?php if($error): ?>
            <div style="color: red;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div style="color: green;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div>
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <!-- <div>
                <label for="role">Role:</label>
                <select id="role" name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div> -->
            
            <div>
                <button type="submit">Register</button>
            </div>
        </form>
        
        <p>Already have an account? <a href="login.php">Login here</a></p>
        <!-- <p><a href="/">Back to Home</a></p> -->
    </div>
</body>
</html>