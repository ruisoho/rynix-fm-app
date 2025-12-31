<?php
require_once 'structure_logic.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || (isset($_POST['_method']) && $_POST['_method'] === 'DELETE')) {
    requireLogin();
    requireCsrf();
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if ($id) {
        deleteRoom($id);
    }
}
?>
