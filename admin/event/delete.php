<!-- admin/events/delete.php -->
<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!is_admin()) {
    header('Location: ../../auth/login.php');
    exit();
}

$event_id = $_GET['id'] ?? null;
if ($event_id) {
    // First delete related registrations
    $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ?");
    $stmt->execute([$event_id]);
    
    // Then delete the event
    $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
}

header('Location: list.php');
exit();

