<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Verify admin access
if (!is_admin()) {
    header('Location: ../auth/login.php');
    exit();
}


//Get counts for dashboard
$stmt = $pdo->query("SELECT COUNT(*) FROM events");
$totalEvents = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$totalUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM event_registrations");
$totalRegistrations = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Admin Dashboard</h1>
    <nav>
        <a href="dashboard.php">Dashboard</a> |
        <a href="event/list.php">Manage Events</a> |
        <a href="users/list.php">Manage Users</a> |
        <a href="../auth/logout.php">Logout</a>
    </nav>
    
    <h2>Overview</h2>
    <div>
        <h3>Total Events: <?php echo $totalEvents; ?></h3>
        <h3>Total Users: <?php echo $totalUsers; ?></h3>
        <h3>Total Registrations: <?php echo $totalRegistrations; ?></h3>
    </div>
</body>
</html>
