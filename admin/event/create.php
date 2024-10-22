<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!is_admin()) {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $max_participants = $_POST['max_participants'];
    
    $stmt = $pdo->prepare("INSERT INTO events (name, date, time, location, description, max_participants, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    
    if ($stmt->execute([$name, $date, $time, $location, $description, $max_participants])) {
        header('Location: list.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Event</title>
</head>
<body>
    <h1>Create New Event</h1>
    <form method="POST">
        <div>
            <label>Name:</label>
            <input type="text" name="name" required>
        </div>
        <div>
            <label>Date:</label>
            <input type="date" name="date" required>
        </div>
        <div>
            <label>Time:</label>
            <input type="time" name="time" required>
        </div>
        <div>
            <label>Location:</label>
            <input type="text" name="location" required>
        </div>
        <div>
            <label>Description:</label>
            <textarea name="description" required></textarea>
        </div>
        <div>
            <label>Max Participants:</label>
            <input type="number" name="max_participants" required>
        </div>
        <button type="submit">Create Event</button>
        <a href="list.php">Cancel</a>
    </form>
</body>
</html>