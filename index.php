<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Ambil daftar event yang akan datang
$query = "SELECT * FROM events WHERE date >= CURDATE() AND status = 'open' ORDER BY date ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Registration System</title>
</head>
<body>
    <header>
        <h1>Welcome to Event Registration System</h1>
        <nav>
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php">Admin Dashboard</a>
                <?php else: ?>
                    <a href="/user/dashboard.php">My Dashboard</a>
                <?php endif; ?>
                <a href="auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section>
            <h2>Upcoming Events</h2>
            <?php if($upcoming_events): ?>
                <?php foreach($upcoming_events as $event): ?>
                    <div class="event-card">
                        <h3><?php echo htmlspecialchars($event['name']); ?></h3>
                        <p>Date: <?php echo htmlspecialchars($event['date']); ?></p>
                        <p>Time: <?php echo htmlspecialchars($event['time']); ?></p>
                        <p>Location: <?php echo htmlspecialchars($event['location']); ?></p>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="/user/events/register.php?event_id=<?php echo $event['event_id']; ?>">
                                Register for Event
                            </a>
                        <?php else: ?>
                            <a href="/auth/login.php">Login to Register</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No upcoming events at the moment.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>