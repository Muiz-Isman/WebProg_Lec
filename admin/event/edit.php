<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!is_admin()) {
    header('Location: ../../auth/login.php');
    exit();
}

$event_id = $_GET['id'] ?? null;
if (!$event_id) {
    header('Location: list.php');
    exit();
}

// Get list of categories
$categories = ['Indian', 'American', 'Korean', 'Chinese', 'Arabian', 'Indonesia'];

// Define status options - keeping consistent with create.php
$statuses = ['open', 'closed', 'canceled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $max_participants = $_POST['max_participants'];
    $status = $_POST['status'];
    $category = $_POST['category'];
    
    // Handle banner image upload
    $banner_image_sql = '';
    $params = [$name, $date, $time, $location, $description, $max_participants, $status, $category];
    
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/events/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_path)) {
            $banner_image_sql = ', banner_image = ?';
            $params[] = 'uploads/events/' . $file_name;
        }
    }
    
    $params[] = $event_id;
    $stmt = $pdo->prepare("UPDATE events SET name = ?, date = ?, time = ?, location = ?, description = ?, max_participants = ?, status = ?, category = ? {$banner_image_sql} WHERE event_id = ?");
    
    if ($stmt->execute($params)) {
        header('Location: list.php');
        exit();
    }
}

$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: list.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Event</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Header Section -->
    <div class="bg-gray-100 border-b">
        <div class="flex justify-between items-center px-6 py-3">
            <!-- <div class="text-lg font-medium">Admin Dashboard</div> -->
            <div class="flex items-center gap-4">
                <a href="list.php" class="text-gray-600 hover:text-gray-800">Back to List</a>
                <!-- <a href="../users/list.php" class="text-gray-600 hover:text-gray-800">Manage Users</a> -->
                <!-- <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">Logout</a> -->
            </div>
        </div>
    </div>

    <!-- Dark Hero Section -->
    <div class="bg-gray-900 text-white py-4">
        <h1 class="text-2xl font-bold text-center">Edit Event</h1>
        <p class="text-center text-gray-400 mt-2">Update event details and information</p>
    </div>

    <!-- Main Content -->
    <div class="p-6">
        <form method="POST" enctype="multipart/form-data" class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name:</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($event['name']); ?>" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category:</label>
                    <select name="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" 
                                    <?php echo $event['category'] === $category ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status:</label>
                    <select name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>"
                                    <?php echo $event['status'] === $status ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($status)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Banner Image:</label>
                    <?php if ($event['banner_image']): ?>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Current Image:</p>
                            <img src="../../<?php echo htmlspecialchars($event['banner_image']); ?>" 
                                 class="mt-2 max-w-xs rounded-lg shadow">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="banner_image" accept="image/*" onchange="previewImage(this)"
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <div id="imagePreview"></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date:</label>
                        <input type="date" name="date" value="<?php echo $event['date']; ?>" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Time:</label>
                        <input type="time" name="time" value="<?php echo $event['time']; ?>" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Location:</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description:</label>
                    <textarea name="description" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 h-32"><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Max Participants:</label>
                    <input type="number" name="max_participants" value="<?php echo $event['max_participants']; ?>" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex justify-end space-x-4 pt-4">
                    <a href="list.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">Cancel</a>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Update Event</button>
                </div>
            </div>
        </form>
    </div>

    <script>
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="mt-2 max-w-xs rounded-lg shadow">`;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
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