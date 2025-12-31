<?php
require_once 'structure_logic.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    requireCsrf();
    $floor_id = $_POST['floor_id'] ?? null;
    $facility_id = $_POST['facility_id'] ?? null; // Needed to re-render the view correctly
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? 'Office';
    $capacity = $_POST['capacity'] ?? 0;
    $area = $_POST['area'] ?? 0;

    if ($floor_id && $name) {
        createRoom($floor_id, $name, $type, $capacity, $area);
    }
    
    // Set active floor for the view
    $active_floor_id = $floor_id;

    // Return the updated view
    if ($facility_id) {
        include 'structure_view.php';
    } else {
        echo "Error: Facility ID missing for view refresh.";
    }
}
