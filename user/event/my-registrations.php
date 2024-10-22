<?php
// user/events/my-registrations.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get user's registrations
$query = "SELECT er.*, e.name as event_name, e.date, e.time, e.location, e.description 
          FROM event_registrations er 
          JOIN events e ON er.event_id = e.event_id 
          WHERE er.user_id = :user_id 
          ORDER BY er.registration_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle cancellation
if (isset($_POST['cancel']) && isset($_POST['registration_id'])) {
    $query = "UPDATE event_registrations 
              SET status = 'cancelled' 
              WHERE registration_id = :registration_id 
              AND user_id = :user_id 
              AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':registration_id', $_POST['registration_id']);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    header('Location: my-registrations.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Registrations - Event Registration System</title>
</head>
<body>
    <div>
        <h1>My Event Registrations</h1>
        
        <nav>
            <a href="../dashboard.php">Dashboard</a> |
            <a href="../profile.php">My Profile</a> |
            <a href="list.php">All Events</a> |
            <a href="my-registrations.php">My Registrations</a> |
            <!-- <a href="register.php">Register</a> | -->
            <a href="../../auth/logout.php">Logout</a>
        </nav>

        <?php if ($registrations): ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                        <!-- <th>Action</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['date']); ?></td>
                            <td><?php echo htmlspecialchars($reg['time']); ?></td>
                            <td><?php echo htmlspecialchars($reg['location']); ?></td>
                            <td><?php echo htmlspecialchars($reg['registration_date']); ?></td>
                            <td><?php echo htmlspecialchars($reg['status']); ?></td>
                            <td>
                                <?php if ($reg['status'] === 'pending' && strtotime($reg['date']) > time()): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="registration_id" value="<?php echo $reg['registration_id']; ?>">
                                        <button type="submit" name="cancel" onclick="return confirm('Are you sure you want to cancel this registration?')">
                                            Cancel Registration
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No registrations found.</p>
        <?php endif; ?>
        
        <p><a href="list.php">Register for New Events</a></p>
    </div>
</body>
</html>