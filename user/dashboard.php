<?php
// user/dashboard.php
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

// Get user's registrations
$query = "SELECT er.registration_id, e.name as event_name, e.date, e.time, e.location, er.status, er.registration_date 
          FROM event_registrations er 
          JOIN events e ON er.event_id = e.event_id 
          WHERE er.user_id = :user_id 
          ORDER BY er.registration_date DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming events
$query = "SELECT * FROM events 
          WHERE date >= CURDATE() 
          AND status = 'active' 
          ORDER BY date ASC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard - Event Registration System</title>
</head>
<body>
    <div>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
        
        <nav>
            <a href="dashboard.php">Dashboard</a> |
            <a href="profile.php">My Profile</a> |
            <a href="event/list.php">All Events</a> |
            <a href="event/my-registrations.php">My Registrations</a> |
            <!-- <a href="event/register.php">Register</a> | -->
            <a href="../auth/logout.php">Logout</a>
        </nav>

        <div>
            <h2>My Recent Registrations</h2>
            <?php if ($registrations): ?>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['event_name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['date']); ?></td>
                                <td><?php echo htmlspecialchars($reg['time']); ?></td>
                                <td><?php echo htmlspecialchars($reg['location']); ?></td>
                                <td><?php echo htmlspecialchars($reg['status']); ?></td>
                                <td><?php echo htmlspecialchars($reg['registration_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><a href="event/my-registrations.php">View All My Registrations</a></p>
            <?php else: ?>
                <p>No registrations found.</p>
            <?php endif; ?>
        </div>

        <div>
            <h2>Upcoming Events</h2>
            <?php if ($upcoming_events): ?>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['name']); ?></td>
                                <td><?php echo htmlspecialchars($event['date']); ?></td>
                                <td><?php echo htmlspecialchars($event['time']); ?></td>
                                <td><?php echo htmlspecialchars($event['location']); ?></td>
                                <td>
                                    <a href="events/register.php?event_id=<?php echo $event['event_id']; ?>">Register</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><a href="events/list.php">View All Events</a></p>
            <?php else: ?>
                <p>No upcoming events found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>