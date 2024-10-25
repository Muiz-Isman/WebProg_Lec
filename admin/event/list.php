<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!is_admin()) {
    header('Location: ../../auth/login.php');
    exit();
}

$stmt = $pdo->query("SELECT * FROM events ORDER BY date DESC");
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Events</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Header Section -->
    <div class="bg-gray-100 border-b">
        <div class="flex items-center px-6 py-3">
            <!-- <div class="text-lg font-medium">Admin Dashboard</div> -->
            <div class="flex items-center gap-4">
                <a href="../dashboard.php" class="text-gray-600 hover:text-gray-800">Back to Dashboard</a>
                <!-- <a href="../users/list.php" class="text-gray-600 hover:text-gray-800">Manage Users</a> -->
                <!-- <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">Logout</a> -->
            </div>
        </div>
    </div>

    <!-- Dark Hero Section -->
    <div class="bg-gray-900 text-white py-6 px-6">
        <h1 class="text-2xl font-bold text-center">Manage Events</h1>
        <p class="text-center text-gray-400 mt-2">View and manage all events in the system</p>
    </div>

    <!-- Main Content -->
    <div class="p-6">
        <div class="mb-6">
            <a href="create.php" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Create New Event</a>
        </div>

        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-10 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Banner</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Max Participants</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($events as $event): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-1 px-1 whitespace-nowrap">
                            <?php if ($event['banner_image']): ?>
                                <img src="../../<?php echo htmlspecialchars($event['banner_image']); ?>" 
                                     class="h-16 w-36 object-cover " alt="Event banner">
                            <?php else: ?>
                                <span class="text-gray-400">No image</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4  text-center whitespace-nowrap"><?php echo htmlspecialchars($event['name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                <?php echo htmlspecialchars($event['category'] ?? 'Uncategorized'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4  text-center whitespace-nowrap"><?php echo date('d M Y', strtotime($event['date'])); ?></td>
                        <td class="px-6 py-4   text-center   whitespace-nowrap"><?php echo date('H:i', strtotime($event['time'])); ?></td>
                        <td class="px-4 py-4  text-center  whitespace-nowrap"><?php echo htmlspecialchars($event['location']); ?></td>
                        <td class="px-4 py-4  text-center whitespace-nowrap"><?php echo $event['max_participants']; ?></td>
                        <td class="px-6 py-4  text-center whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                       <?php echo $event['status'] === 'open' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($event['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="edit.php?id=<?php echo $event['event_id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>
                            <a href="delete.php?id=<?php echo $event['event_id']; ?>" 
                               onclick="return confirm('Are you sure you want to delete this event?')" 
                               class="text-red-600 hover:text-red-900">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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