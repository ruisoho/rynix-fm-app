<?php
require_once 'facilities.php';
require_once 'auth.php';

// Allow CORS if necessary
header("Access-Control-Allow-Origin: *");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    requireCsrf();
    
    // Only Admin can create facilities
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo "Unauthorized";
        exit;
    }

    // Collect data
    $data = [
        'name' => $_POST['name'] ?? '',
        'type' => $_POST['type'] ?? '',
        'address' => $_POST['address'] ?? '',
        'area' => $_POST['area'] ?? 0,
        'construction_year' => $_POST['construction_year'] ?? 0,
        'status' => $_POST['status'] ?? 'Active',
        'employees' => $_POST['employees'] ?? 0,
        'op_hours' => $_POST['op_hours'] ?? '',
        'hazard_level' => $_POST['hazard_level'] ?? 'Low',
        'manager_name' => $_POST['manager_name'] ?? '',
        'manager_contact' => $_POST['manager_contact'] ?? '',
        'description' => $_POST['description'] ?? ''
    ];

    try {
        createFacility($data);
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error: " . $e->getMessage();
        exit;
    }
}

// Fetch and display all facilities (for both GET and after POST)
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';

$facilities = getFacilities($search, $type, $status);

if (empty($facilities)) {
    echo "<div class='p-4 text-gray-500'>No facilities found.</div>";
} else {
    foreach ($facilities as $facility) {
        echo "<div class='bg-white border p-4 mb-4 rounded shadow hover:shadow-lg transition-shadow cursor-pointer' 
                   onclick=\"window.location.href='facility.php?id=" . $facility['id'] . "'\">";
        echo "<div class='flex justify-between items-start'>";
        echo "<div>";
        echo "<h3 class='font-bold text-lg text-blue-600'>" . htmlspecialchars($facility['name']) . "</h3>";
        echo "<span class='text-xs font-semibold bg-gray-100 px-2 py-1 rounded text-gray-600'>" . htmlspecialchars($facility['type']) . "</span>";
        echo "</div>";
        echo "<span class='text-sm " . ($facility['status'] === 'Active' ? 'text-green-600' : 'text-red-600') . " font-medium'>" . htmlspecialchars($facility['status']) . "</span>";
        echo "</div>";
        
        echo "<div class='mt-2 text-sm text-gray-600 grid grid-cols-2 gap-2'>";
        echo "<p><strong>Address:</strong> " . htmlspecialchars($facility['address']) . "</p>";
        echo "<p><strong>Manager:</strong> " . htmlspecialchars($facility['manager_name']) . "</p>";
        echo "<p><strong>Area:</strong> " . htmlspecialchars($facility['area']) . " m²</p>";
        echo "<p><strong>Employees:</strong> " . htmlspecialchars($facility['employees']) . "</p>";
        echo "</div>";
        
        echo "<p class='mt-2 text-gray-700 text-sm italic'>" . htmlspecialchars($facility['description']) . "</p>";
        echo "</div>";
    }
}
