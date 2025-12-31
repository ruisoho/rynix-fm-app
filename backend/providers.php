<?php
require_once 'db.php';
require_once 'auth.php';
require_once 'csrf_helper.php';

// Ensure user is logged in
requireLogin();

// Release session lock for performance if not needed for write operations
if ($_SERVER['REQUEST_METHOD'] === 'GET' && session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Fetch all providers
function getProviders($filters = []) {
    global $pdo;
    $sql = "SELECT * FROM providers WHERE 1=1";
    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND (name LIKE ? OR contact_person LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $params[] = "%" . $filters['search'] . "%";
        $params[] = "%" . $filters['search'] . "%";
        $params[] = "%" . $filters['search'] . "%";
        $params[] = "%" . $filters['search'] . "%";
    }

    if (!empty($filters['service_type'])) {
        $sql .= " AND service_type = ?";
        $params[] = $filters['service_type'];
    }

    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }

    $sql .= " ORDER BY name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle GET requests (List Refresh & Edit Form)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get Provider Details
    if (isset($_GET['action']) && $_GET['action'] === 'get_provider_details' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM providers WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);
        include 'providers_details_view.php';
        exit;
    }

    // Get Edit Form
    if (isset($_GET['action']) && $_GET['action'] === 'get_provider_form' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM providers WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);
        include 'providers_edit_form.php';
        exit;
    }

    // HTMX List Refresh
    if (isset($_SERVER['HTTP_HX_REQUEST']) && !isset($_GET['action'])) {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'service_type' => $_GET['service_type'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        $providers = getProviders($filters);
        include 'providers_list_view.php';
        exit;
    }
}

// Handle POST requests (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    // Admin only
    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        die('Unauthorized');
    }

    $name = $_POST['name'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $service_type = $_POST['service_type'] ?? '';
    if ($service_type === 'other' && !empty($_POST['custom_service_type'])) {
        $service_type = $_POST['custom_service_type'];
    }
    $address = $_POST['address'] ?? '';
    $customer_number = $_POST['customer_number'] ?? '';
    $hourly_rate = $_POST['hourly_rate'] ?? 0;
    $status = $_POST['status'] ?? 'Active';
    $specialization = $_POST['specialization'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Create Provider
    if (!isset($_POST['action']) && $name) {
        $stmt = $pdo->prepare("INSERT INTO providers (name, contact_person, email, phone, service_type, address, customer_number, hourly_rate, status, specialization, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $contact_person, $email, $phone, $service_type, $address, $customer_number, $hourly_rate, $status, $specialization, $notes]);
        
        $providers = getProviders();
        include 'providers_list_view.php';
        exit;
    }

    // Update Provider
    if (isset($_POST['action']) && $_POST['action'] === 'update_provider') {
        $id = $_POST['id'] ?? null;
        if ($id && $name) {
            $stmt = $pdo->prepare("UPDATE providers SET name=?, contact_person=?, email=?, phone=?, service_type=?, address=?, customer_number=?, hourly_rate=?, status=?, specialization=?, notes=? WHERE id=?");
            $stmt->execute([$name, $contact_person, $email, $phone, $service_type, $address, $customer_number, $hourly_rate, $status, $specialization, $notes, $id]);
            
            $providers = getProviders();
            include 'providers_list_view.php';
            exit;
        }
    }

    // Delete Provider
    if (isset($_POST['action']) && $_POST['action'] === 'delete_provider') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM providers WHERE id = ?");
            $stmt->execute([$id]);
            
            $providers = getProviders();
            include 'providers_list_view.php';
            exit;
        }
    }
}
?>