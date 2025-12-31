<?php
require_once 'facilities.php';
require_once 'auth.php';

// Allow CORS if necessary
header("Access-Control-Allow-Origin: *");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    requireCsrf();

    // Only Admin can update facilities
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo "Unauthorized";
        exit;
    }

    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo "Error: ID missing";
        exit;
    }

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
        updateFacility($id, $data);
        
        // Use HTMX Redirect header to reload the page with new data
        header("HX-Redirect: /frontend/facility.php?id=" . $id);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error: " . $e->getMessage();
    }
}
