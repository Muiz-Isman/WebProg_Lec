<?php
// user/profile.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Get current user data
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email)) {
        $error = "Name and email are required";
    } else {
        // Check if email already exists (excluding current user)
        $query = "SELECT user_id FROM users WHERE email = :email AND user_id != :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = "Email already exists";
        } else {
            // If changing password
            if (!empty($current_password)) {
                if (password_verify($current_password, $user['password'])) {
                    if (!empty($new_password) && !empty($confirm_password)) {
                        if ($new_password === $confirm_password) {
                            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $query = "UPDATE users SET name = :name, email = :email, password = :password WHERE user_id = :user_id";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':password', $password_hash);
                        } else {
                            $error = "New passwords do not match";
                        }
                    } else {
                        $error = "New password and confirmation are required";
                    }
                } else {
                    $error = "Current password is incorrect";
                }
            } else {
                // Update without changing password
                $query = "UPDATE users SET name = :name, email = :email WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
            }

            if (empty($error)) {
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['name'] = $name;
                    $success = "Profile updated successfully";
                    // Refresh user data
                    $query = "SELECT * FROM users WHERE user_id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Error updating profile";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Event Registration System</title>
</head>
<body>
    <div>
        <h1>My Profile</h1>
        
        <nav>
            <a href="dashboard.php">Dashboard</a> |
            <a href="profile.php">My Profile</a> |
            <a href="event/list.php">All Events</a> |
            <a href="event/my-registrations.php">My Registrations</a> |
            <!-- <a href="event/register.php">Register</a> | -->
            <a href="../auth/logout.php">Logout</a>
        </nav>

        <?php if ($error): ?>
            <div style="color: red;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="color: green;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo htmlspecialchars($user['name']); ?>">
            </div>
            
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            
            <h3>Change Password (leave blank to keep current password)</h3>
            
            <div>
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password">
            </div>
            
            <div>
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password">
            </div>
            
            <div>
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            
            <div>
                <button type="submit">Update Profile</button>
            </div>
        </form>
    </div>
</body>
</html>