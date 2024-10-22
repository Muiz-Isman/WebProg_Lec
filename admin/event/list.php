<!-- admin/events/list.php -->
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
</head>
<body>
    <h1>Manage Events</h1>
    <nav>
        <a href="../dashboard.php">Dashboard</a> |
        <a href="create.php">Create New Event</a>
    </nav>
    
    <table border="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Location</th>
                <th>Max Participants</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): ?>
            <tr>
                <td><?php echo htmlspecialchars($event['name']); ?></td>
                <td><?php echo $event['date']; ?></td>
                <td><?php echo $event['time']; ?></td>
                <td><?php echo htmlspecialchars($event['location']); ?></td>
                <td><?php echo $event['max_participants']; ?></td>
                <td><?php echo $event['status']; ?></td>
                <td>
                    <a href="edit.php?id=<?php echo $event['event_id']; ?>">Edit</a> |
                    <a href="delete.php?id=<?php echo $event['event_id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>