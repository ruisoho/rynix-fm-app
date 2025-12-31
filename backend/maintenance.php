<?php
require_once 'db.php';
require_once 'auth.php';
requireLogin();
require_once 'csrf_helper.php';

// Global CSRF Check for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

// Fetch all maintenance logs
function getMaintenanceLogs($filters = []) {
    global $pdo;
    $sql = "SELECT m.*, f.name as facility_name, p.name as provider_name 
            FROM maintenance_logs m 
            JOIN facilities f ON m.facility_id = f.id
            LEFT JOIN providers p ON m.provider_id = p.id
            WHERE 1=1";
    
    $params = [];

    // Backward compatibility: if a single ID is passed instead of an array
    if (!is_array($filters) && $filters !== null && $filters !== '') {
        $filters = ['facility_id' => $filters];
    }
    
    if (is_array($filters)) {
        if (!empty($filters['search'])) {
            $sql .= " AND (m.title LIKE ? OR m.description LIKE ?)";
            $params[] = "%" . $filters['search'] . "%";
            $params[] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['facility_id'])) {
            $sql .= " AND m.facility_id = ?";
            $params[] = $filters['facility_id'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND m.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND m.status = ?";
            $params[] = $filters['status'];
        }
    }

    $sql .= " ORDER BY m.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch facilities for dropdown
function getFacilitiesDropdown() {
    global $pdo;
    return $pdo->query("SELECT id, name FROM facilities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch providers for dropdown
function getProvidersDropdown() {
    global $pdo;
    return $pdo->query("SELECT id, name FROM providers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

// Handle HTMX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facility_id = $_POST['facility_id'] ?? null;
    $provider_id = !empty($_POST['provider_id']) ? $_POST['provider_id'] : null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'Medium';
    $status = $_POST['status'] ?? 'Open';
    $recurrence = $_POST['recurrence'] ?? 'None';
    $scheduled_date = $_POST['scheduled_date'] ?? null;
    $cost = $_POST['cost'] ?? 0;

    // Create New Task
    if (!isset($_POST['action']) && $facility_id && $title) {
        $stmt = $pdo->prepare("INSERT INTO maintenance_logs (facility_id, provider_id, title, description, priority, status, recurrence, scheduled_date, cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$facility_id, $provider_id, $title, $description, $priority, $status, $recurrence, $scheduled_date, $cost]);
        
        // Return updated list
        $logs = getMaintenanceLogs();
        include 'maintenance_list_view.php';
        exit;
    }
}

// Fetch Task Details for Edit Form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_task_form' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM maintenance_logs WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        $facilities = getFacilitiesDropdown();
        $providers = getProvidersDropdown();
        
        include 'maintenance_edit_form.php';
        exit;
    }

    // HTMX Filter Request
    if (isset($_SERVER['HTTP_HX_REQUEST']) && !isset($_GET['action'])) {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'facility_id' => $_GET['facility_id'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        $logs = getMaintenanceLogs($filters);
        include 'maintenance_list_view.php';
        exit;
    }
}

// Handle Update Task (Full Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_task') {
    $id = $_POST['id'] ?? null;
    $facility_id = $_POST['facility_id'] ?? null;
    $provider_id = !empty($_POST['provider_id']) ? $_POST['provider_id'] : null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'Medium';
    $status = $_POST['status'] ?? 'Open';
    $recurrence = $_POST['recurrence'] ?? 'None';
    $scheduled_date = $_POST['scheduled_date'] ?? null;
    $cost = $_POST['cost'] ?? 0;

    if ($id && $facility_id && $title) {
        $stmt = $pdo->prepare("UPDATE maintenance_logs SET facility_id=?, provider_id=?, title=?, description=?, priority=?, status=?, recurrence=?, scheduled_date=?, cost=? WHERE id=?");
        $stmt->execute([$facility_id, $provider_id, $title, $description, $priority, $status, $recurrence, $scheduled_date, $cost, $id]);
        
        // Return updated list
        $logs = getMaintenanceLogs();
        include 'maintenance_list_view.php';
        exit;
    }
}

// Handle Delete Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_task') {
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM maintenance_logs WHERE id = ?");
        $stmt->execute([$id]);
        
        // Return updated list
        $logs = getMaintenanceLogs();
        include 'maintenance_list_view.php';
        exit;
    }
}

// Handle Task Status Update (and Recurrence Logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = $_POST['id'] ?? null;
    $new_status = $_POST['status'] ?? null;
    
    if ($id && $new_status) {
        // Fetch current task details first to check for recurrence
        $stmt = $pdo->prepare("SELECT * FROM maintenance_logs WHERE id = ?");
        $stmt->execute([$id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task) {
            // Update status
            $updateStmt = $pdo->prepare("UPDATE maintenance_logs SET status = ? WHERE id = ?");
            $updateStmt->execute([$new_status, $id]);

            // Check if we need to create a recurring task
            if (($new_status === 'Closed' || $new_status === 'Completed') && 
                $task['recurrence'] !== 'None' && 
                $task['recurrence'] !== null) {
                
                $intervalMap = [
                    '1 Month' => '+1 month',
                    '3 Months' => '+3 months',
                    '6 Months' => '+6 months',
                    '1 Year' => '+1 year',
                    '2 Years' => '+2 years',
                    '4 Years' => '+4 years',
                    '5 Years' => '+5 years'
                ];

                if (isset($intervalMap[$task['recurrence']])) {
                    $baseDate = $task['scheduled_date'] ? $task['scheduled_date'] : date('Y-m-d');
                    $nextDate = date('Y-m-d', strtotime($baseDate . ' ' . $intervalMap[$task['recurrence']]));
                    
                    // Create next task
                    $insertStmt = $pdo->prepare("INSERT INTO maintenance_logs (facility_id, title, description, priority, status, recurrence, scheduled_date, cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    // Copy details, reset status to Open, keep recurrence
                    $insertStmt->execute([
                        $task['facility_id'], 
                        $task['title'], 
                        $task['description'], 
                        $task['priority'], 
                        'Open', 
                        $task['recurrence'], 
                        $nextDate, 
                        $task['cost']
                    ]);
                }
            }
        }

        // Return updated list
        $logs = getMaintenanceLogs();
        include 'maintenance_list_view.php';
        exit;
    }
}

// Handle GET for list refresh
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SERVER['HTTP_HX_REQUEST'])) {
    $logs = getMaintenanceLogs();
    include 'maintenance_list_view.php';
    exit;
}
?>