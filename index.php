<?php
session_start();
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.tailwindcss.com cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; font-src cdnjs.cloudflare.com; img-src 'self' data:;");
session_regenerate_id(true);
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Get all events with registration count and user registration status
$query = "SELECT e.*, 
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND status = 'registered') as registered_count,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = :user_id AND status = 'registered') as is_registered
    FROM events e 
    ORDER BY e.date ASC";
$stmt = $db->prepare($query);
$stmt->bindValue(':user_id', isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
$stmt->execute();
$all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query for upcoming events
$upcoming_query = "SELECT e.*, 
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND status = 'registered') as registered_count,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND user_id = :user_id AND status = 'registered') as is_registered
    FROM events e 
    WHERE e.date >= CURDATE()
    ORDER BY e.date ASC";
$upcoming_stmt = $db->prepare($upcoming_query);
$upcoming_stmt->bindValue(':user_id', isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
$upcoming_stmt->execute();
$upcoming_events = $upcoming_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle event registration
if (isset($_POST['register_event']) && isset($_SESSION['user_id'])) {
    $event_id = $_POST['event_id'];
    
    $register_stmt = $db->prepare("INSERT INTO event_registrations (user_id, event_id, registration_date, status) VALUES (:user_id, :event_id, NOW(), 'registered')");
    
    try {
        $register_stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':event_id' => $event_id
        ]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        $error_message = "Registration failed: " . $e->getMessage();
    }
}

if (isset($_POST['register_event']) && isset($_SESSION['user_id'])) {
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Validate event exists and has space
    $validate_stmt = $db->prepare("SELECT max_participants, 
        (SELECT COUNT(*) FROM event_registrations WHERE event_id = events.event_id AND status = 'registered') as current_registrations 
        FROM events WHERE event_id = :event_id");
    $validate_stmt->execute([':event_id' => $event_id]);
    $event_data = $validate_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event_data || $event_data['current_registrations'] >= $event_data['max_participants']) {
        $error_message = "Registration failed: Event is full or does not exist";
        exit();
    }
}

// Handle event registration cancellation
if (isset($_POST['cancel_event']) && isset($_SESSION['user_id'])) {
    $event_id = $_POST['event_id'];

    $cancel_stmt = $db->prepare("UPDATE event_registrations SET status = 'canceled' WHERE user_id = :user_id AND event_id = :event_id");

    try {
        $cancel_stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':event_id' => $event_id
        ]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        $error_message = "Cancellation failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Registration System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* #detailsText {
            display: none; 
        } */
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">


    <!-- Enhanced Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50 backdrop-blur-md bg-white/90">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2">
                    <!-- <i class="fas fa-calendar-check text-blue-500 text-2xl"></i> -->
                    <a href="#" class="text-xl font-bold  hover:from-blue-500 hover:to-blue-300 transition-all duration-300">EventZee</a>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] === 'admin'): ?>
                            <a href="admin/dashboard.php" class="text-gray-600 hover:text-blue-500 transition-colors duration-300 flex items-center space-x-2">
                                <i class="fas fa-chart-line"></i>
                                <span>Admin Dashboard</span>
                            </a>
                        <?php else: ?>
                            <a href="user/dashboard.php" class="text-gray-600 hover:text-blue-500 transition-colors duration-300 flex items-center space-x-2">
                                <!-- <i class="fas fa-user"></i> -->
                                <span>My Details</span>
                            </a>
                        <?php endif; ?>
                        <a href="auth/logout.php" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                        <a href="auth/login.php" class="text-gray-600 hover:text-blue-500 transition-colors duration-300 flex items-center space-x-2">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                        <a href="auth/register.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                            <i class="fas fa-user-plus"></i>
                            <span>Register</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Enhanced Hero Section -->
    <div class="relative bg-gray-900 text-white py-14">
    <div class="absolute inset-0 bg-gray-800"></div>
    <div class="absolute inset-0 opacity-10 bg-pattern"></div>
    
    <div class="relative max-w-7xl mx-20 px-4 ">
        <h1 class="text-5xl font-bold mb-6">
            EventZee
        </h1>
        <p class="text-gray-300 text-lg leading-relaxed mb-6 whitespace-nowrap">
            Join us for transformative experiences and unforgettable gatherings
        </p>
        <button type="button" 
            onclick="showDetails()"
            class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 transition-colors duration-200">
            Details
        </button>
        
        <div id="detailsText" class="hidden mt-6  ">
            <p class="text-gray-300 text-lg leading-relaxed animate-fade-in">
                Eventzee provides concert tickets for global artists with easy and fast access. 
                The platform is flexibly designed to allow users to choose tickets according to 
                their preferences, supported by real-time updates and a secure payment system. 
                A responsive interface ensures a seamless experience across all devices, while 
                customer service is ready to help at any time.
            </p>
        </div>
    </div>
</div>

    <div class="bg-white shadow-md">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex space-x-8 overflow-x-auto">
                <button id="all-events-tab" class="py-4 px-4 text-gray-600 border-b-2 border-transparent hover:text-blue-500 hover:border-blue-500 transition-all duration-300">
                    All Events
                </button>
                <button id="upcoming-events-tab" class="py-4 px-4 text-gray-600 border-b-2 border-transparent hover:text-blue-500 hover:border-blue-500 transition-all duration-300">
                    Upcoming Events
                </button>
            </div>
        </div>
    </div>

    <main class="max-w-6xl mx-auto px-4 py-12">
    <?php if(isset($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-md mb-6 animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo  htmlspecialchars($error_message); ?>
            </div>
        </div>
    <?php endif; ?>

    <div id="all-events-section" class="grid grid-cols-1 gap-8">
        <?php if($all_events): ?>
            <?php foreach($all_events as $event): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:scale-[1.02] transition-all duration-300 hover:shadow-xl">
                    <div class="md:flex">
                        <!-- Event Image -->
                        <div class="md:w-1/3 relative group">
                            <?php if (!empty($event['banner_image'])): ?>
                                <img src="<?php echo htmlspecialchars($event['banner_image']); ?>" 
                                     class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-100" alt="Event banner">
                            <?php else: ?>
                                <div class="w-full h-64 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center group-hover:from-gray-200 group-hover:to-gray-300 transition-all duration-300">
                                    <i class="fas fa-image text-4xl text-gray-400 group-hover:scale-110 transition-transform duration-300"></i>
                                </div>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>

                        <!-- Event Details -->
                        <div class="md:w-2/3 p-6">
                            <!-- [Rest of your existing event card code remains the same] -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-800 mb-2 hover:text-blue-600 transition-colors duration-300">
                                        <?php echo htmlspecialchars($event['name']); ?>
                                    </h2>
                                    <?php if (isset($event['category'])): ?>
                                        <span class="inline-block bg-blue-100 text-blue-800 text-sm px-4 py-1 rounded-full hover:bg-blue-200 transition-colors duration-300">
                                            <i class="fas fa-tag mr-1"></i>
                                            <?php echo htmlspecialchars($event['category']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <div class="text-gray-600 flex items-center space-x-1 hover:text-blue-500 transition-colors duration-300">
                                        <i class="far fa-calendar-alt"></i>
                                        <span><?php echo date('d M Y', strtotime($event['date'])); ?></span>
                                    </div>
                                    <div class="text-gray-600 flex items-center space-x-1 hover:text-blue-500 transition-colors duration-300">
                                        <i class="far fa-clock"></i>
                                        <span><?php echo date('H:i', strtotime($event['time'])); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button 
                                    class="description-btn px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-all duration-300 flex items-center space-x-2"
                                    data-name="<?php echo htmlspecialchars($event['name']); ?>"
                                    data-description="<?php echo htmlspecialchars($event['description']); ?>"
                                    data-image="<?php echo htmlspecialchars($event['banner_image']); ?>">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Show Details</span>
                                </button>
                                
                                <div class="flex items-center justify-between mt-6">
                                    <div class="flex items-center space-x-6">
                                        <div class="text-gray-600 hover:text-blue-500 transition-colors duration-300 flex items-center space-x-2">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($event['location']); ?></span>
                                        </div>
                                        <div class="text-gray-600 hover:text-blue-500 transition-colors duration-300 flex items-center space-x-2">
                                            <i class="fas fa-users"></i>
                                            <span>
                                                <?php 
                                                $available = $event['max_participants'] - $event['registered_count'];
                                                echo $available . ' spots left';
                                                ?>
                                            </span>
                                        </div>
                                    </div>

                                    <?php if (!isset($_SESSION['user_id'])): ?>
                                        <a href="auth/login.php" 
                                           class="inline-flex items-center space-x-2 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg">
                                            <i class="fas fa-sign-in-alt"></i>
                                            <span>Login to Register</span>
                                        </a>
                                    <?php else: ?>
                                        <?php if ($event['is_registered'] > 0): ?>
                                            <?php
                                            $check_status = $db->prepare("SELECT status FROM event_registrations WHERE user_id = :user_id AND event_id = :event_id");
                                            $check_status->execute([
                                                ':user_id' => $_SESSION['user_id'],
                                                ':event_id' => $event['event_id']
                                            ]);
                                            $registration_status = $check_status->fetchColumn();
                                            ?>
                                            <?php if ($registration_status === 'registered'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="event_id" value="<?php echo  htmlspecialchars($event['event_id']); ?>">
                                                    <button type="submit" name="cancel_event" 
                                                            class="inline-flex items-center space-x-2 bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg">
                                                        <i class="fas fa-times-circle"></i>
                                                        <span>Cancel Registration</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="inline-flex items-center space-x-2 bg-gray-200 text-gray-800 px-6 py-2 rounded-lg">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span>Registered</span>
                                                </span>
                                            <?php endif; ?>
                                        <?php elseif ($available > 0): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                                <button type="submit" name="register_event" 
                                                        class="inline-flex items-center space-x-2 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg">
                                                    <i class="fas fa-user-plus"></i>
                                                    <span>Register to Event</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="inline-flex items-center space-x-2 bg-gray-200 text-gray-800 px-6 py-2 rounded-lg">
                                                <i class="fas fa-ban"></i>
                                                <span>Event Full</span>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <i class="fas fa-calendar-times text-gray-400 text-5xl mb-4"></i>
                    <p class="text-gray-600 text-lg">No events found at the moment.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div id="upcoming-events-section" class="hidden grid grid-cols-1 gap-8">
    <?php if($upcoming_events): ?>
        <?php foreach($upcoming_events as $event): ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:scale-[1.02] transition-all duration-300 hover:shadow-xl">
                <div class="md:flex">
                    <!-- Event Image -->
                    <div class="md:w-1/3 relative group">
                        <?php if (!empty($event['banner_image'])): ?>
                            <img src="<?php echo escape($event['banner_image']); ?>" 
                                 class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-100" alt="Event banner">
                        <?php else: ?>
                            <div class="w-full h-64 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center group-hover:from-gray-200 group-hover:to-gray-300 transition-all duration-300">
                                <i class="fas fa-image text-4xl text-gray-400 group-hover:scale-110 transition-transform duration-300"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Event Details -->
                    <div class="md:w-2/3 p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800 mb-2 hover:text-blue-600 transition-colors duration-300">
                                    <?php echo escape($event['name']); ?>
                                </h2>
                                <?php if (isset($event['category'])): ?>
                                    <span class="inline-block bg-blue-100 text-blue-800 text-sm px-4 py-1 rounded-full hover:bg-blue-200 transition-colors duration-300">
                                        <i class="fas fa-tag mr-1"></i>
                                        <?php echo escape($event['category']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-right">
                                <div class="text-gray-600 flex items-center space-x-1 hover:text-blue-500 transition-colors duration-300">
                                    <i class="far fa-calendar-alt"></i>
                                    <span><?php echo escape(date('d M Y', strtotime($event['date']))); ?></span>
                                </div>
                                <div class="text-gray-600 flex items-center space-x-1 hover:text-blue-500 transition-colors duration-300">
                                    <i class="far fa-clock"></i>
                                    <span><?php echo escape(date('H:i', strtotime($event['time']))); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button 
                                class="description-btn px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-all duration-300 flex items-center space-x-2"
                                data-name="<?php echo escape($event['name']); ?>"
                                data-description="<?php echo escape($event['description']); ?>"
                                data-image="<?php echo escape($event['banner_image']); ?>">
                                <i class="fas fa-info-circle"></i>
                                <span>Show Details</span>
                            </button>
                            
                            <div class="flex items-center justify-between mt-6">
                                <div class="flex items-center space-x-6">
                                    <div class="text-gray-600 hover:text-blue-500 transition-colors duration-300 flex items-center space-x-2">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo escape($event['location']); ?></span>
                                    </div>
                                    <div class="text-gray-600 hover:text-blue-500 transition-colors duration-300 flex items-center space-x-2">
                                        <i class="fas fa-users"></i>
                                        <span>
                                            <?php 
                                            $available = $event['max_participants'] - $event['registered_count'];
                                            echo escape($available . ' spots left');
                                            ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if (!isset($_SESSION['user_id'])): ?>
                                    <a href="auth/login.php" 
                                       class="inline-flex items-center space-x-2 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg">
                                        <i class="fas fa-sign-in-alt"></i>
                                        <span>Login to Register</span>
                                    </a>
                                <?php else: ?>
                                    <?php if ($event['is_registered'] > 0): ?>
                                        <?php
                                        $check_status = $db->prepare("SELECT status FROM event_registrations WHERE user_id = :user_id AND event_id = :event_id");
                                        $check_status->execute([
                                            ':user_id' => filter_var($_SESSION['user_id'], FILTER_SANITIZE_NUMBER_INT),
                                            ':event_id' => filter_var($event['event_id'], FILTER_SANITIZE_NUMBER_INT)
                                        ]);
                                        $registration_status = $check_status->fetchColumn();
                                        ?>
                                        <?php if ($registration_status === 'registered'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="event_id" value="<?php echo escape($event['event_id']); ?>">
                                                <button type="submit" name="cancel_event" 
                                                        class="inline-flex items-center space-x-2 bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg">
                                                    <i class="fas fa-times-circle"></i>
                                                    <span>Cancel Registration</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="inline-flex items-center space-x-2 bg-gray-200 text-gray-800 px-6 py-2 rounded-lg">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Registered</span>
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($available > 0): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="event_id" value="<?php echo escape($event['event_id']); ?>">
                                            <button type="submit" name="register_event" 
                                                    class="inline-flex items-center space-x-2 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg">
                                                <i class="fas fa-user-plus"></i>
                                                <span>Register to Event</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="inline-flex items-center space-x-2 bg-gray-200 text-gray-800 px-6 py-2 rounded-lg">
                                            <i class="fas fa-ban"></i>
                                            <span>Event Full</span>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-center py-12">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <i class="fas fa-calendar-times text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-600 text-lg">No upcoming events found.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
</main>




    
    <style>
        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fade-out {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-in-out;
        }

        .animate-fade-out {
            animation: fade-out 0.3s ease-in-out;
        }

        .animate-fade-in-up {
            animation: fade-in-up 0.5s ease-out;
        }

        .bg-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>

    <script>
        // Tab switching functionality
const allEventsTab = document.getElementById('all-events-tab');
const upcomingEventsTab = document.getElementById('upcoming-events-tab');
const allEventsSection = document.getElementById('all-events-section');
const upcomingEventsSection = document.getElementById('upcoming-events-section');

allEventsTab.addEventListener('click', () => {
    allEventsTab.classList.add('text-blue-500', 'border-blue-500');
    upcomingEventsTab.classList.remove('text-blue-500', 'border-blue-500');
    allEventsSection.classList.remove('hidden');
    upcomingEventsSection.classList.add('hidden');
});

upcomingEventsTab.addEventListener('click', () => {
    upcomingEventsTab.classList.add('text-blue-500', 'border-blue-500');
    allEventsTab.classList.remove('text-blue-500', 'border-blue-500');
    upcomingEventsSection.classList.remove('hidden');
    allEventsSection.classList.add('hidden');
});

// Set initial active tab
allEventsTab.click();

// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('eventModal');
    const modalName = document.getElementById('modalName');
    const modalDescription = document.getElementById('modalDescription');
    const modalBanner = document.getElementById('modalBanner');
    const closeModalBtn = document.getElementById('closeModal');

    // Function to close modal
    function closeModal() {
        modal.classList.add('animate-fade-out');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('animate-fade-out');
            document.body.style.overflow = 'auto';
        }, 300);
    }

    // Add click event to all description buttons
    document.querySelectorAll('.description-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            modal.classList.remove('hidden');
            modal.classList.add('animate-fade-in');
            
            modalName.textContent = this.getAttribute('data-name');
            modalDescription.textContent = this.getAttribute('data-description');
            
            const imageUrl = this.getAttribute('data-image');
            if (imageUrl && imageUrl !== 'null') {
                modalBanner.src = imageUrl;
                modalBanner.classList.remove('hidden');
            } else {
                modalBanner.classList.add('hidden');
            }
            
            document.body.style.overflow = 'hidden';
        });
    });

    // Close modal when clicking the close button
    closeModalBtn.addEventListener('click', closeModal);

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Add scroll animation for cards
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
                entry.target.style.opacity = '1';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.bg-white.rounded-xl').forEach(card => {
        card.style.opacity = '0';
        observer.observe(card);
    });
});

        function showDetails() {
            var details = document.getElementById("detailsText");
                if (details.style.display === "none") {
                    details.style.display = "block"; // Menampilkan teks
                } else {
                    details.style.display = "none"; // Menyembunyikan teks jika tombol diklik lagi
                }
        }

    </script>

<!-- Event Details Modal -->
<div id="eventModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <h3 id="modalName" class="text-2xl font-bold text-gray-800"></h3>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <img id="modalBanner" src="" alt="Event banner" class="w-full h-64 object-cover rounded-lg mb-4">
            <p id="modalDescription" class="text-gray-600 leading-relaxed"></p>
        </div>
    </div>
</div>
</body>

<footer class="bottom-0 left-0 z-20 w-full p-4 bg-white border-t border-gray-200 shadow md:flex md:items-center md:justify-between md:p-6 dark:bg-gray-800 dark:border-gray-600">
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
</html>