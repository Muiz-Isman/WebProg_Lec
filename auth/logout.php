<?php
// Start the session
session_start();

// Include any necessary files (adjust path as needed)
require_once '../config/database.php';
require_once '../includes/functions.php';

// Unset specific session variables
unset($_SESSION['user_id']);
unset($_SESSION['name']);
unset($_SESSION['role']);

// Clear the entire session array
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page (adjust path as needed)
header('Location: ../index.php');
exit();
?>