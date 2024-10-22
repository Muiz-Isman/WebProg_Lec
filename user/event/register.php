<?php
// user/events/register.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

if (!isset($_GET['event_id'])) {
    header('Location: list.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$error = '';
$success = '';

// Get event details
$query = "SELECT *, 
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = :event_id) as registered_count
          FROM events 
          WHERE event_id = :event_id AND status = 'active' AND date >= CURDATE()";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $_GET['event_id']);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: list.php');
    exit();
}

// Check if already registered
$query = "SELECT * FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':event_id', $_GET['event_id']);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    header('Location: list.php');
    exit();
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if event is full
    if ($event['registered_count'] >= $event['max_participants']) {
        $error = "Sorry, this event is already full.";
    } else {
        // Register user for the event
        $query = "INSERT INTO event_registrations (user_id, event_id, registration_date, status) 
                  VALUES (:user_id, :event_id, NOW(), 'pending')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':event_id', $_GET['event_id']);
        
        if ($stmt->execute()) {
            $success = "Registration successful! You are now registered for this event.";
        } else {
            $error = "Error occurred during registration. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Registration - Event Registration System</title>
</head>
<body>
    <div>
        <h1>Event Registration</h1>
        
        <nav>
            <a href="../dashboard.php">Dashboard</a> |
            <a href="../profile.php">My Profile</a> |
            <a href="list.php">All Events</a> |
            <a href="my-registrations.php">My Registrations</a> |
            <a href="register.php">Register</a> |
            <a href="../../auth/logout.php">Logout</a>
        </nav>

        <?php if ($error): ?>
            <div style="color: red;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="color: green;">
                <?php echo htmlspecialchars($success); ?>
                <p><a href="my-registrations.php">View My Registrations</a></p>
            </div>
        <?php else: ?>
            <div>
                <h2><?php echo htmlspecialchars($event['name']); ?></h2>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?></p>
                <p><strong>Time:</strong> <?php echo htmlspecialchars($event['time']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($event['description']); ?></p>
                <p><strong>Available Slots:</strong> <?php echo $event['max_participants'] - $event['registered_count']; ?> of <?php echo $event['max_participants']; ?></p>
                
                <form method="POST" action="">
                    <p>Are you sure you want to register for this event?</p>
                    <button type="submit">Confirm Registration</button>
                    <a href="list.php">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
