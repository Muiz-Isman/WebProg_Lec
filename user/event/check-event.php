<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Debug: Print all events with their status
$debug_stmt = $pdo->query("SELECT event_id, name, date, status FROM events");
$all_events = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>All Events in Database:</h2>";
echo "<pre>";
print_r($all_events);
echo "</pre>";

// Update events to be active if they're not
$update_stmt = $pdo->prepare("UPDATE events SET status = 'active' WHERE status IS NULL OR status = ''");
$update_stmt->execute();
echo "Events updated to active status.";

// Now check events again
$check_stmt = $pdo->query("SELECT event_id, name, date, status FROM events");
$updated_events = $check_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Events After Update:</h2>";
echo "<pre>";
print_r($updated_events);
echo "</pre>";
?>