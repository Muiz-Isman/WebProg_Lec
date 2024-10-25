<?php
   require_once "../config/database.php";
   require_once "../includes/auth.php";
   session_start();

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
   ?>