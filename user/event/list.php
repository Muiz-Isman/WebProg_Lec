<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Handle event registration
if (isset($_POST['register_event'])) {
    $event_id = $_POST['event_id'];
    
    // Insert into event_registrations
    $register_stmt = $pdo->prepare("INSERT INTO event_registrations (user_id, event_id, registration_date, status) VALUES (:user_id, :event_id, NOW(), 'registered')");
    
    try {
        $register_stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':event_id' => $event_id
        ]);
        // echo "Successfully registered for event!";
    } catch (PDOException $e) {
        echo "Registration failed: " . $e->getMessage();
    }
}

// Handle event registration cancellation
if (isset($_POST['cancel_event'])) {
    $event_id = $_POST['event_id'];

    // Update status in event_registrations to 'canceled'
    $cancel_stmt = $pdo->prepare("UPDATE event_registrations SET status = 'canceled' WHERE user_id = :user_id AND event_id = :event_id");

    try {
        $cancel_stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':event_id' => $event_id
        ]);
        // echo "Successfully canceled the registration!";
    } catch (PDOException $e) {
        echo "Cancellation failed: " . $e->getMessage();
    }
}

// Get all events without any date filter
$stmt = $pdo->prepare("SELECT e.*, 
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND status = 'registered') as registered_count,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = :user_id AND status = 'registered') as is_registered
    FROM events e 
    ORDER BY e.date ASC");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Available Events - Event Registration System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <h1>Available Events</h1>
    
    <nav>
        <a href="../dashboard.php">Dashboard</a> |
        <a href="../profile.php">My Profile</a> |
        <a href="list.php">All Events</a> |
        <a href="my-registrations.php">My Registrations</a> |
        <a href="../../auth/logout.php">Logout</a>
    </nav>

    <?php if ($events): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Location</th>
                    <th>Description</th>
                    <th>Available Slots</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['name']); ?></td>
                        <td><?php echo $event['date']; ?></td>
                        <td><?php echo $event['time']; ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td><?php echo htmlspecialchars($event['description']); ?></td>
                        <td>
                            <?php 
                            $available = $event['max_participants'] - $event['registered_count'];
                            echo $available . ' of ' . $event['max_participants'];
                            ?>
                        </td>
                        <td>
                            <?php if ($event['is_registered'] > 0): ?>
                                <?php
                                // Check if status is still 'registered'
                                $check_registration_status = $pdo->prepare("SELECT status FROM event_registrations WHERE user_id = :user_id AND event_id = :event_id");
                                $check_registration_status->execute([
                                    ':user_id' => $_SESSION['user_id'],
                                    ':event_id' => $event['event_id']
                                ]);
                                $registration_status = $check_registration_status->fetchColumn();
                                ?>
                                <?php if ($registration_status === 'registered'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                        <input type="submit" name="cancel_event" value="Cancel Registration">
                                    </form>
                                <?php else: ?>
                                    registered
                                <?php endif; ?>
                            <?php elseif ($available > 0): ?>
                                <form method="POST">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <input type="submit" name="register_event" value="Register">
                                </form>
                            <?php else: ?>
                                Full
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No events found.</p>
    <?php endif; ?>
</body>

</html>
