<?php
require_once 'structure_logic.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireLogin();
    requireCsrf();
    $id = $_GET['id'] ?? null;

    if ($id) {
    // We need the facility_id to re-render the view correctly
    // But since we are deleting the floor, we need to fetch it first OR pass it in the request
    // Let's fetch it first
    $floor = getFloor($id);
    if ($floor) {
        $facility_id = $floor['facility_id'];
        deleteFloor($id);
        
        // After deletion, we can't be on the deleted floor. 
        // structure_view.php defaults to the first available floor if $active_floor_id is not set.
        // So we just unset it or don't pass it.
        $active_floor_id = null; 
        
        include 'structure_view.php';
    }
    }
}
?>