<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!is_admin()) {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header('Location: list.php');
    exit();
}

// Define available roles
$roles = ['user', 'admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $new_password = $_POST['new_password'];
    
    // Start building the SQL query and parameters
    $sql_parts = ["name = ?, email = ?, role = ?"];
    $params = [$name, $email, $role];
    
    // Add password update if provided
    if (!empty($new_password)) {
        $sql_parts[] = "password = ?";
        $params[] = password_hash($new_password, PASSWORD_DEFAULT);
    }
    
    $params[] = $user_id;
    $sql = "UPDATE users SET " . implode(", ", $sql_parts) . ", updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        $_SESSION['success'] = "User updated successfully!";
        header('Location: list.php');
        exit();
    } else {
        $_SESSION['error'] = "Error updating user!";
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: list.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Header Section -->
    <div class="bg-gray-100 border-b">
        <div class="flex items-center px-6 py-3">
            <!-- <div class="text-lg font-medium">Admin Dashboard</div> -->
            <div class="flex items-center gap-4">
                <a href="../users/list.php" class="text-gray-600 hover:text-gray-800">Back To The Control User</a>
                <!-- <a href="list.php" class="text-gray-600 hover:text-gray-800">Manage Users</a> -->
                <!-- <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">Logout</a> -->
            </div>
        </div>
    </div>

    <!-- Dark Hero Section -->
    <div class="bg-gray-900 text-white py-4 px-6">
        <h1 class="text-2xl font-bold text-center">Edit Manage User</h1>
        <!-- <p class="text-center text-gray-400 mt-2">Manage users, events, and more through the dashboard</p> -->
    </div>

    <!-- Main Content -->
    <div class="max-w-2xl mx-auto p-6">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Name:</label>
                    <input type="text" 
                           name="name" 
                           value="<?php echo htmlspecialchars($user['name']); ?>" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                    <input type="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Role:</label>
                    <select name="role" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role; ?>" <?php echo $user['role'] === $role ? 'selected' : ''; ?>>
                                <?php echo ucfirst($role); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">New Password (leave blank to keep current):</label>
                    <input type="password" 
                           name="new_password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                
                <div class="flex items-center gap-4">
                    <button type="submit" 
                            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Update User
                    </button>
                    <a href="list.php" 
                       class="text-gray-600 hover:text-gray-800">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

<footer class="bottom-0 left-0 z-20 w-full p-4 bg-white border-t border-gray-200 shadow md:flex md:items-center md:justify-between md:p-6 dark:bg-gray-800 dark:border-gray-600">
    <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">© 2024 <a href="#" class="hover:underline">EventZee</a>
    </span>
    <ul class="flex flex-wrap items-center mt-3 text-sm font-medium text-gray-500 dark:text-gray-400 sm:mt-0">
        <li>
            <a href="#" class="hover:underline me-4 md:me-6">About</a>
        </li>
        <li>
            <a href="#" class="hover:underline me-4 md:me-6">Privacy Policy</a>
        </li>
        <li>
            <a href="#" class="hover:underline me-4 md:me-6">Licensing</a>
        </li>
        <li>
            <a href="#" class="hover:underline">Contact</a>
        </li>
    </ul>
</footer>
</html>