<?php
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

// Fungsi untuk membersihkan input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get user's registrations with prepared statement
$query = "SELECT er.registration_id, e.name as event_name, e.date, e.time, e.location, e.banner_image, er.status, er.registration_date 
          FROM event_registrations er 
          JOIN events e ON er.event_id = e.event_id 
          WHERE er.user_id = :user_id 
          ORDER BY er.registration_date DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all events with registration count and user registration status
$query = "SELECT e.*, 
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND status = 'registered') as registered_count,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = :user_id AND status = 'registered') as is_registered
    FROM events e 
    ORDER BY e.date ASC";
$stmt = $db->prepare($query);
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle event registration with CSRF protection
if (isset($_POST['register_event']) && isset($_POST['csrf_token'])) {
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        die('CSRF token validation failed');
    }
    
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    if ($event_id === false || $event_id === null) {
        die('Invalid event ID');
    }
    
    // Check if already registered
    $check_stmt = $db->prepare("SELECT COUNT(*) FROM event_registrations WHERE user_id = :user_id AND event_id = :event_id AND status = 'registered'");
    $check_stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':event_id' => $event_id
    ]);
    
    if ($check_stmt->fetchColumn() > 0) {
        $error_message = "Already registered for this event";
    } else {
        $register_stmt = $db->prepare("INSERT INTO event_registrations (user_id, event_id, registration_date, status) VALUES (:user_id, :event_id, NOW(), 'registered')");
        
        try {
            $register_stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':event_id' => $event_id
            ]);
            header("Location: " . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'));
            exit();
        } catch (PDOException $e) {
            $error_message = "Registration failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

// Handle event registration cancellation with CSRF protection
if (isset($_POST['cancel_event']) && isset($_POST['csrf_token'])) {
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        die('CSRF token validation failed');
    }
    
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    if ($event_id === false || $event_id === null) {
        die('Invalid event ID');
    }

    $cancel_stmt = $db->prepare("UPDATE event_registrations SET status = 'canceled' WHERE user_id = :user_id AND event_id = :event_id");

    try {
        $cancel_stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':event_id' => $event_id
        ]);
        header("Location: " . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'));
        exit();
    } catch (PDOException $e) {
        $error_message = "Cancellation failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html class="bg-gray-100 min-h-screen">
<head>
    <title>User Dashboard - Event Registration System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://cdn.tailwindcss.com 'unsafe-inline'; style-src 'self' 'unsafe-inline';">
    <style>
        #eventModal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="bg-gray text-black shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold">EventZee</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?= htmlspecialchars('../index.php', ENT_QUOTES, 'UTF-8') ?>" class="hover:text-gray-300">Events</a>
                    <a href="<?= htmlspecialchars('profile.php', ENT_QUOTES, 'UTF-8') ?>" class="hover:text-gray-300">My Profile</a>
                    <a href="<?= htmlspecialchars('../auth/logout.php', ENT_QUOTES, 'UTF-8') ?>" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="bg-gray-900 text-white py-10 text-center">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h1 class="text-4xl font-bold mb-4">EventZee</h1>
            <p class="text-gray-300">Join us for our upcoming events and be part of our community</p>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-12">
        <!-- My Recent Registrations -->
        <?php if ($registrations): ?>
        <div class="mb-16">
            <h2 class="text-3xl font-bold text-center mb-8">My Recent Registrations</h2>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td>
                                <?php if ($reg['banner_image']): ?>
                                    <img src="<?= htmlspecialchars('../' . $reg['banner_image'], ENT_QUOTES, 'UTF-8') ?>" 
                                         class="banner-thumbnail w-full h-16 object-cover" 
                                         alt="Event banner">
                                <?php else: ?>
                                    No image
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($reg['event_name'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($reg['date'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($reg['time'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($reg['location'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($reg['status'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        

    </div>

    <!-- Modal -->
    <div id="eventModal" class="modal">
        <div class="bg-white rounded-lg max-w-2xl mx-auto mt-20 p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalEventName" class="text-2xl font-bold text-gray-900"></h2>
                <span class="close text-gray-500 text-2xl cursor-pointer hover:text-gray-700">&times;</span>
            </div>
            <img id="modalImage" src="" alt="Event Image" class="w-full h-64 object-cover rounded-lg mb-4">
            <p id="modalDescription" class="text-gray-600"></p>
        </div>
    </div>

    <script>
        // Secure JavaScript implementation
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('eventModal');
            const closeModal = document.querySelector('.close');
            const modalEventName = document.getElementById('modalEventName');
            const modalImage = document.getElementById('modalImage');
            const modalDescription = document.getElementById('modalDescription');

            // Fungsi untuk escape HTML untuk mencegah XSS
            function escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            document.querySelectorAll('.description-btn').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const eventName = escapeHtml(this.getAttribute('data-name'));
                    const eventDescription = escapeHtml(this.getAttribute('data-description'));
                    const eventImage = this.getAttribute('data-image');

                    modalEventName.textContent = eventName;
                    modalDescription.textContent = eventDescription;
                    
                    // Validasi URL gambar
                    if (eventImage && eventImage.startsWith('../uploads/events/')) {
                        modalImage.src = eventImage;
                    } else {
                        modalImage.src = '/api/placeholder/400/320';
                    }

                    modal.style.display = 'block';
                });
            });

            closeModal.onclick = function() {
                modal.style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        });
    </script>

    

   
</body>

</html><footer class="bottom-0 left-0 z-20 w-full p-4 bg-white border-t border-gray-200 shadow md:flex md:items-center md:justify-between md:p-6 dark:bg-gray-800 dark:border-gray-600">
    <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">© 2024 <a href="#" class="hover:underline">EventZee</a>
    </span>
    <ul class="flex flex-wrap items-center mt-3 text-sm font-medium text-gray-500 dark:text-gray-400 sm:mt-0">
        <li>
            <a href="#" class="hover:underline me-4 md:me-6">About</a>
        </li>
        <li>
            <a href="#" class="hover:underline me-4 md:me-6">Privacy Policy</a>
        </li>
        <li>
            <a href="#" class="hover:underline me-4 md:me-6">Licensing</a>
        </li>
        <li>
            <a href="#" class="hover:underline">Contact</a>
        </li>
    </ul>
</footer>

