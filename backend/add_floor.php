<?php
require_once 'structure_logic.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    requireCsrf();
    $facility_id = $_POST['facility_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $level_number = $_POST['level_number'] ?? 0;

    if ($facility_id && $name) {
        $active_floor_id = createFloor($facility_id, $name, $level_number);
    }
    
    // Return the updated view
    include 'structure_view.php';
}
