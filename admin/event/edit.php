<!-- admin/events/edit.php -->
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $max_participants = $_POST['max_participants'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE events SET name = ?, date = ?, time = ?, location = ?, description = ?, max_participants = ?, status = ? WHERE event_id = ?");
    
    if ($stmt->execute([$name, $date, $time, $location, $description, $max_participants, $status, $event_id])) {
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
</head>
<body>
    <h1>Edit Event</h1>
    <form method="POST">
        <div>
            <label>Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($event['name']); ?>" required>
        </div>
        <div>
            <label>Date:</label>
            <input type="date" name="date" value="<?php echo $event['date']; ?>" required>
        </div>
        <div>
            <label>Time:</label>
            <input type="time" name="time" value="<?php echo $event['time']; ?>" required>
        </div>
        <div>
            <label>Location:</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required>
        </div>
        <div>
            <label>Description:</label>
            <textarea name="description" required><?php echo htmlspecialchars($event['description']); ?></textarea>
        </div>
        <div>
            <label>Max Participants:</label>
            <input type="number" name="max_participants" value="<?php echo $event['max_participants']; ?>" required>
        </div>
        <div>
            <label>Status:</label>
            <select name="status">
                <option value="active" <?php echo $event['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <button type="submit">Update Event</button>
        <a href="list.php">Cancel</a>
    </form>
</body>
</html>
