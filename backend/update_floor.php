<?php
require_once 'structure_logic.php';
require_once 'auth.php';
require_once 'csrf_helper.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? null;
    $level_number = $_POST['level_number'] ?? null;
    $facility_id = $_POST['facility_id'] ?? null;

    if ($id && $name && $level_number !== null) {
        updateFloor($id, $name, $level_number);
    }
    
    // Set the active floor to the one we just updated so it stays open
    $active_floor_id = $id;
    
    // Re-render the structure view
    include 'structure_view.php';
}
?>