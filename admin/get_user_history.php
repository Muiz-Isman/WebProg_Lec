<?php
session_start();
require_once "../config/database.php";
require_once "../includes/auth.php";

if (!is_admin()) {
    exit("Unauthorized access");
}

if (!isset($_GET["user_id"])) {
    exit("User ID is required");
}

$user_id = $_GET["user_id"];

try {
    // Get user details first
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "<p class=\"text-red-500 p-4\">User not found.</p>";
        exit;
    }

    // Display user info
    echo "<div class=\"mb-6 p-4 bg-gray-50 rounded\">
            <h4 class=\"text-lg font-semibold\">" . htmlspecialchars($user["name"]) . "</h4>
            <p class=\"text-gray-600\">" . htmlspecialchars($user["email"]) . "</p>
          </div>";

    // Get all event registrations for this user
    $stmt = $pdo->prepare("
        SELECT er.*, e.name as event_name, e.date as event_date, e.location
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE er.user_id = ?
        ORDER BY er.registration_date DESC
    ");
    
    $stmt->execute([$user_id]);
    $registrations = $stmt->fetchAll();

    if (empty($registrations)) {
        echo "<p class=\"text-gray-600 p-4\">No event registrations found for this user.</p>";
        exit;
    }

    echo "<table class=\"min-w-full divide-y divide-gray-200\">
            <thead>
                <tr>
                    <th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Event Name</th>
                    <th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Event Date</th>
                    <th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Location</th>
                    <th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Registration Date</th>
                    <th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Status</th>
                </tr>
            </thead>
            <tbody class=\"bg-white divide-y divide-gray-200\">";

    foreach ($registrations as $reg) {
        $event_date = new DateTime($reg["event_date"]);
        $reg_date = new DateTime($reg["registration_date"]);
        
        echo "<tr>
                <td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900\">" . htmlspecialchars($reg["event_name"]) . "</td>
                <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">" . $event_date->format("M d, Y") . "</td>
                <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">" . htmlspecialchars($reg["location"]) . "</td>
                <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">" . $reg_date->format("M d, Y H:i") . "</td>
                <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">" . htmlspecialchars($reg["status"]) . "</td>
            </tr>";
    }

    echo "</tbody></table>";

} catch (PDOException $e) {
    echo "<p class=\"text-red-500 p-4\">Error: Unable to fetch user history data.</p>";
}
?>