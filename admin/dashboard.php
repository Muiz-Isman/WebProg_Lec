<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    header('Location: ../auth/login.php');
    exit();
}

// Handle CSV Export
if (isset($_GET['export']) && isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
    $stmt = $pdo->prepare("
        SELECT e.name as event_name, u.name, u.email, er.registration_date, er.status
        FROM event_registrations er
        JOIN users u ON er.user_id = u.user_id
        JOIN events e ON er.event_id = e.event_id
        WHERE er.event_id = ?
    ");
    $stmt->execute([$event_id]);
    $export_data = $stmt->fetchAll();

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="event_registrants.csv"');

    // Create CSV file
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Event Name', 'Participant Name', 'Email', 'Registration Date', 'Status'));
    
    foreach ($export_data as $row) {
        fputcsv($output, array(
            $row['event_name'],
            $row['name'],
            $row['email'],
            $row['registration_date'],
            $row['status']
        ));
    }
    
    fclose($output);
    exit();
}

// Get dashboard stats
$stmt = $pdo->query("SELECT COUNT(*) FROM events");
$totalEvents = $stmt->fetchColumn();

// Get events list with registration count
$query = "
    SELECT 
        e.*,
        (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.event_id) as registrant_count 
    FROM events e 
    ORDER BY e.date DESC
";
$stmt = $pdo->query($query);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$totalUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM event_registrations");
$totalRegistrations = $stmt->fetchColumn();

// Get events list

// Get users list
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html class="h-full bg-gray-50">
<head>
    <title>Admin Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Add jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
        
<body class="h-full">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="#" class="text-xl font-bold text-gray-800">Admin Dashboard</a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="event/list.php" class="text-gray-600 hover:text-gray-900">Manage Events</a>
                        <a href="users/list.php" class="text-gray-600 hover:text-gray-900">Manage Users</a>
                        <a href="../auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="bg-gray-900 text-white py-20 text-center">
            <h1 class="text-4xl font-bold mb-4">Admin Control Center</h1>
            <p class="text-gray-400 max-w-2xl mx-auto">Manage users, events, and more through the dashboard</p>
        </div>

        <!-- Main Content -->
        <main class="max-w-6xl mx-auto px-4 py-12">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-bold text-gray-800">Total Events</h2>
                    <p class="text-gray-600 mt-2"><?php echo $totalEvents; ?></p>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-bold text-gray-800">Total Users</h2>
                    <p class="text-gray-600 mt-2"><?php echo $totalUsers; ?></p>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-bold text-gray-800">Total Registrations</h2>
                    <p class="text-gray-600 mt-2"><?php echo $totalRegistrations; ?></p>
                </div>
            </div>

            <!-- Events List -->
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Events List</h2>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full leading-normal">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 text-center border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Image</th>
                                <th class="px-5 py-3 text-center border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Event Name</th>
                                <th class="px-5 py-3 text-center border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Date</th>
                                <th class="px-5 py-3 text-center border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Location</th>
                                <!-- <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Max_Participants</th> -->
                                <th class="px-5 py-3 text-center border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Registered</th>
                                <th class="px-5 py-3 text-center border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                <th class="px-5 py-3 text-center border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                            <tr class="hover:bg-gray-50">
            <td class="px-2 py-2 border-b border-gray-200 bg-white">
                <?php if ($event['banner_image']): ?>
                    <div class="w-26 h-20">  <!-- Container dengan ukuran tetap -->
                        <img src="../<?php echo htmlspecialchars($event['banner_image']); ?>" 
                            class=" w-full h-full object-cover " 
                            alt="Event banner">
                    </div>
                <?php else: ?>
                    <div class="w-20 h-20 bg-gray-100 flex items-center justify-center rounded-lg">
                        <span class="text-gray-400">No image</span>
                    </div>
                <?php endif; ?>
            </td>
            <td class="px-5 py-4 border-b border-gray-200 bg-white">
                <div class="text-gray-900"><?php echo htmlspecialchars($event['name']); ?></div>
            </td>
            <td class="px-5 py-4 border-b border-gray-200 bg-white">
                <div class="text-gray-900"><?php echo $event['date']; ?></div>
            </td>
            <td class="px-5 py-4 border-b border-gray-200 bg-white">
                <div class="text-gray-900"><?php echo htmlspecialchars($event['location']); ?></div>
            </td>
            <td class="px-5 py-4 border-b border-gray-200 bg-white">
                <span class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-blue-800 bg-blue-100 rounded-full">
                    <?php echo $event['registrant_count']; ?>/<?php echo $event['max_participants']; ?>
                </span>
            </td>
            <td class="px-5 py-4 border-b border-gray-200 bg-white">
                <span class="text-gray-900"><?php echo $event['status']; ?></span>
            </td>
            <td class="px-5 py-4 border-b border-gray-200 bg-white">
                <div class="flex space-x-2">
                    <button onclick="viewRegistrants(<?php echo $event['event_id']; ?>)" 
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-blue-500 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        View Registrants
                    </button>
                    <a href="?export=true&event_id=<?php echo $event['event_id']; ?>" 
                    class="inline-flex items-center px-3 text-center py-1 text-sm font-medium text-white bg-green-500 rounded hover:bg-green-700  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                        Export CSV
                    </a>
                </div>
            </td>
</tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Users List -->
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Users List</h2>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full leading-normal">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">ID</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Name</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Email</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Role</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Created At</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?php echo $user['user_id']; ?></td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?php echo htmlspecialchars($user['name']); ?></td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?php echo ucfirst($user['role']); ?></td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?php 
                                    $created_at = new DateTime($user['created_at']);
                                    echo $created_at->format('M d, Y H:i'); 
                                ?></td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <button onclick="viewUserHistory(<?php echo $user['user_id']; ?>)" 
                                            class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-900">
                                        View History
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Registrants -->
    <div id="registrantsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-4/5 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Event Registrants</h3>
                <div id="registrantsContent" class="max-h-96 overflow-y-auto">
                    <!-- Content will be loaded here -->
                </div>
                <div class="mt-4">
                    <button onclick="closeModal()" class="mt-3 bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal for User History -->
    <div id="userHistoryModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-4/5 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">User Registration History</h3>
                <div id="userHistoryContent" class="max-h-96 overflow-y-auto">
                    <!-- Content will be loaded here -->
                </div>
                <div class="mt-4">
                    <button onclick="closeHistoryModal()" class="mt-3 bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function viewRegistrants(eventId) {
        // Show modal
        document.getElementById('registrantsModal').classList.remove('hidden');
        
        // Fetch registrants data
        $.ajax({
            url: 'get_registrants.php',
            method: 'GET',
            data: { event_id: eventId },
            success: function(response) {
                document.getElementById('registrantsContent').innerHTML = response;
            },
            error: function() {
                document.getElementById('registrantsContent').innerHTML = 
                    '<p class="text-red-500">Error loading registrants data.</p>';
            }
        });
    }

    function closeModal() {
        document.getElementById('registrantsModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('registrantsModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>

    <footer class="bottom-0 left-0 z-20 w-full p-4 bg-white border-t border-gray-200 shadow md:flex md:items-center md:justify-between md:p-6 dark:bg-gray-800 dark:border-gray-600">
        <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">© 2024 <a href="#" class="hover:underline">EventZee</a>
        </span>
        <ul class="flex flex-wrap items-center mt-3 text-sm font-medium text-gray-500 dark:text-gray-400 sm:mt-0">
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

<script>

function viewUserHistory(userId) {
    // Show modal
    document.getElementById('userHistoryModal').classList.remove('hidden');
    
    // Fetch user history data
    $.ajax({
        url: 'get_user_history.php',
        method: 'GET',
        data: { user_id: userId },
        success: function(response) {
            document.getElementById('userHistoryContent').innerHTML = response;
        },
        error: function() {
            document.getElementById('userHistoryContent').innerHTML = 
                '<p class="text-red-500">Error loading user history data.</p>';
        }
    });
}

function closeHistoryModal() {
    document.getElementById('userHistoryModal').classList.add('hidden');
}

// Update existing window.onclick to handle both modals
window.onclick = function(event) {
    var registrantsModal = document.getElementById('registrantsModal');
    var historyModal = document.getElementById('userHistoryModal');
    if (event.target == registrantsModal) {
        closeModal();
    }
    if (event.target == historyModal) {
        closeHistoryModal();
    }
}
</script>


<?php
// Create get_registrants.php file for loading registrant data via AJAX
if (!file_exists('get_registrants.php')) {
   $get_registrants_content = '<?php
   require_once "../config/database.php";
   require_once "../includes/auth.php";

   if (!is_admin()) {
       exit("Unauthorized access");
   }

   if (!isset($_GET["event_id"])) {
       exit("Event ID is required");
   }

   $event_id = $_GET["event_id"];

   try {
       $stmt = $pdo->prepare("
           SELECT er.*, u.name, u.email, e.name as event_name 
           FROM event_registrations er
           JOIN users u ON er.user_id = u.user_id
           JOIN events e ON er.event_id = e.event_id
           WHERE er.event_id = ?
           ORDER BY er.registration_date DESC
       ");
       
       $stmt->execute([$event_id]);
       $registrants = $stmt->fetchAll();

       if (empty($registrants)) {
           echo "<p class=\"text-gray-600 p-4\">No registrants found for this event.</p>";
           exit;
       }

       echo "<table class=\"min-w-full divide-y divide-gray-200\">
               <thead>
                   <tr>
                       <th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Name</th>
                       <th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Email</th>
                       <th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Registration Date</th>
                       <th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Status</th>
                   </tr>
               </thead>
               <tbody class=\"bg-white divide-y divide-gray-200\">";

       foreach ($registrants as $registrant) {
           $reg_date = new DateTime($registrant["registration_date"]);
           echo "<tr>
                   <td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900\">" . htmlspecialchars($registrant["name"]) . "</td>
                   <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">" . htmlspecialchars($registrant["email"]) . "</td>
                   <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">" . $reg_date->format("M d, Y H:i") . "</td>
                   <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">" . htmlspecialchars($registrant["status"]) . "</td>
               </tr>";
       }

       echo "</tbody></table>";

   } catch (PDOException $e) {
       echo "<p class=\"text-red-500 p-4\">Error: Unable to fetch registrants data.</p>";
   }
   ?>';

   // Create the get_registrants.php file
   file_put_contents('get_registrants.php', $get_registrants_content);
}
?>
                