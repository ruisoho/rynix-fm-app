<?php
require_once 'db.php';
require_once 'notifications_helper.php';
require_once 'auth.php';
require_once 'csrf_helper.php';

// Ensure user is logged in
requireLogin();

// Release session lock for performance (read-only session usage)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

function getDailyTasks($filters = []) {
    global $pdo;
    $sql = "SELECT t.*, f.name as facility_name, fl.name as floor_name, r.name as room_name, p.name as provider_name,
            u.full_name as creator_name
            FROM daily_tasks t 
            LEFT JOIN facilities f ON t.facility_id = f.id
            LEFT JOIN floors fl ON t.floor_id = fl.id
            LEFT JOIN rooms r ON t.room_id = r.id
            LEFT JOIN providers p ON t.provider_id = p.id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE 1=1";
    
    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
        $params[] = "%" . $filters['search'] . "%";
        $params[] = "%" . $filters['search'] . "%";
    }

    if (!empty($filters['facility_id'])) {
        $sql .= " AND t.facility_id = ?";
        $params[] = $filters['facility_id'];
    }

    if (!empty($filters['status'])) {
        $sql .= " AND t.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['assigned_to'])) {
        $sql .= " AND t.assigned_to LIKE ?";
        $params[] = "%" . $filters['assigned_to'] . "%";
    }

    if (!empty($filters['created_by'])) {
        $sql .= " AND t.created_by = ?";
        $params[] = $filters['created_by'];
    }

    $sql .= " ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFacilitiesDropdownTasks() {
    global $pdo;
    return $pdo->query("SELECT id, name FROM facilities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

function getProvidersDropdownTasks() {
    global $pdo;
    return $pdo->query("SELECT id, name FROM providers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

// Handle GET requests (Dropdown updates & List Refresh)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        // Get Floors for Facility
        if ($_GET['action'] === 'get_floors' && isset($_GET['facility_id'])) {
            $stmt = $pdo->prepare("SELECT id, name FROM floors WHERE facility_id = ? ORDER BY level_number");
            $stmt->execute([$_GET['facility_id']]);
            $floors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<option value="">Select Floor</option>';
            foreach ($floors as $floor) {
                echo "<option value='" . $floor['id'] . "'>" . htmlspecialchars($floor['name']) . "</option>";
            }
            exit;
        }
        
        // Get Rooms for Floor
        if ($_GET['action'] === 'get_rooms' && isset($_GET['floor_id'])) {
            $stmt = $pdo->prepare("SELECT id, name FROM rooms WHERE floor_id = ? ORDER BY name");
            $stmt->execute([$_GET['floor_id']]);
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<option value="">Select Room</option>';
            foreach ($rooms as $room) {
                echo "<option value='" . $room['id'] . "'>" . htmlspecialchars($room['name']) . "</option>";
            }
            exit;
        }
    }
    
    // Handle HTMX List Refresh
    if (isset($_SERVER['HTTP_HX_REQUEST']) && !isset($_GET['action'])) {
        // Staff should not be able to list all tasks via this endpoint
        if ($_SESSION['user']['role'] === 'staff') {
            http_response_code(403);
            exit;
        }

        $filters = [
            'search' => $_GET['search'] ?? '',
            'facility_id' => $_GET['facility_id'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        $tasks = getDailyTasks($filters);
        include 'tasks_list_view.php';
        exit;
    }
}

// Handle POST request (Create Task)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $facility_id = $_POST['facility_id'] ?? null;
    $floor_id = !empty($_POST['floor_id']) ? $_POST['floor_id'] : null;
    $room_id = !empty($_POST['room_id']) ? $_POST['room_id'] : null;
    $provider_id = !empty($_POST['provider_id']) ? $_POST['provider_id'] : null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? '';
    $priority = $_POST['priority'] ?? 'Medium';
    
    // Handle Unified Assignee Logic (from Edit Form)
    if (isset($_POST['assignee_select'])) {
        $assignee_select = $_POST['assignee_select'];
        $provider_id = null; // Reset default
        $assigned_to = '';   // Reset default
        
        if (strpos($assignee_select, 'p_') === 0) {
            $provider_id = substr($assignee_select, 2);
        } elseif ($assignee_select === 'custom') {
            $assigned_to = $_POST['custom_assignee'] ?? '';
        }
    }

    $due_date = $_POST['due_date'] ?? null;
    $status = $_POST['status'] ?? 'Pending';

    // Handle Ticket Creation (Staff Portal)
    if (isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
        if ($facility_id && $title) {
            $created_by = $_SESSION['user_id'] ?? null;
            $status = 'Pending'; // Force pending for tickets
            $stmt = $pdo->prepare("INSERT INTO daily_tasks (facility_id, floor_id, room_id, title, description, priority, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");
            $stmt->execute([$facility_id, $floor_id, $room_id, $title, $description, $priority, $status, $created_by]);
            
            // Notify Admins about new ticket
            notifyRole('admin', "New ticket created: " . $title, 'info', 'tasks.php');
            
            http_response_code(200);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
    }

    if (!isset($_POST['action']) && $facility_id && $title) {
        if ($_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            exit;
        }
        $created_by = $_SESSION['user_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO daily_tasks (facility_id, floor_id, room_id, provider_id, title, description, assigned_to, due_date, status, priority, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$facility_id, $floor_id, $room_id, $provider_id, $title, $description, $assigned_to, $due_date, $status, $priority, $created_by]);
        
        $tasks = getDailyTasks();
        include 'tasks_list_view.php';
        exit;
    }
}

// Fetch Task Details for Edit Form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_task_form' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM daily_tasks WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    $facilities = getFacilitiesDropdownTasks();
    $providers = getProvidersDropdownTasks();
    
    // Get floors for current facility
    $floors = [];
    if ($task['facility_id']) {
        $stmt = $pdo->prepare("SELECT id, name FROM floors WHERE facility_id = ? ORDER BY level_number");
        $stmt->execute([$task['facility_id']]);
        $floors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get rooms for current floor
    $rooms = [];
    if ($task['floor_id']) {
        $stmt = $pdo->prepare("SELECT id, name FROM rooms WHERE floor_id = ? ORDER BY name");
        $stmt->execute([$task['floor_id']]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    include 'tasks_edit_form.php';
    exit;
}

// Fetch Task Details for Detail View (Read-Only)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_task_details' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT t.*, f.name as facility_name, fl.name as floor_name, r.name as room_name, p.name as provider_name,
            u.full_name as creator_name
            FROM daily_tasks t 
            LEFT JOIN facilities f ON t.facility_id = f.id
            LEFT JOIN floors fl ON t.floor_id = fl.id
            LEFT JOIN rooms r ON t.room_id = r.id
            LEFT JOIN providers p ON t.provider_id = p.id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.id = ?");
    $stmt->execute([$_GET['id']]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    include 'tasks_detail_view.php';
    exit;
}

// Handle Update Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_task') {
    requireCsrf();
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        exit;
    }
    $id = $_POST['id'] ?? null;
    $facility_id = $_POST['facility_id'] ?? null;
    $floor_id = !empty($_POST['floor_id']) ? $_POST['floor_id'] : null;
    $room_id = !empty($_POST['room_id']) ? $_POST['room_id'] : null;
    $provider_id = !empty($_POST['provider_id']) ? $_POST['provider_id'] : null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? '';
    
    // Handle Unified Assignee Logic (from Edit Form)
    if (isset($_POST['assignee_select'])) {
        $assignee_select = $_POST['assignee_select'];
        $provider_id = null; // Reset default
        $assigned_to = '';   // Reset default
        
        if (strpos($assignee_select, 'p_') === 0) {
            $provider_id = substr($assignee_select, 2);
        } elseif ($assignee_select === 'custom') {
            $assigned_to = $_POST['custom_assignee'] ?? '';
        }
    }

    $due_date = $_POST['due_date'] ?? null;
    $status = $_POST['status'] ?? 'Pending';
    $priority = $_POST['priority'] ?? 'Medium';

    if ($id && $facility_id && $title) {
        // Fetch old task data for notifications
        $stmt_old = $pdo->prepare("SELECT status, created_by, title FROM daily_tasks WHERE id = ?");
        $stmt_old->execute([$id]);
        $old_task = $stmt_old->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("UPDATE daily_tasks SET facility_id=?, floor_id=?, room_id=?, provider_id=?, title=?, description=?, assigned_to=?, due_date=?, status=?, priority=? WHERE id=?");
        $stmt->execute([$facility_id, $floor_id, $room_id, $provider_id, $title, $description, $assigned_to, $due_date, $status, $priority, $id]);
        
        // Notify Creator if status changed
        if ($old_task && $old_task['created_by'] && $old_task['status'] !== $status) {
            $creator_id = $old_task['created_by'];
            $updater_id = $_SESSION['user_id'] ?? null;
            
            // Don't notify if the creator is the one updating
            if ($creator_id != $updater_id) {
                $msg = "Ticket status updated to $status: " . $old_task['title'];
                // Use relative link to tasks page? Or maybe staff portal?
                // If creator is staff, they use staff_portal.php. If admin, tasks.php.
                // We don't know the creator's role easily here without querying.
                // But tasks.php is the main view. Staff can see their status in staff_portal.php.
                // Let's link to staff_portal.php if we assume it's a ticket.
                // Actually, the notification system just provides a link. 
                // Let's just say 'staff_portal.php' as a safe default for staff tickets, 
                // but admins might find it weird.
                // Safest: No link, or intelligent link.
                // Let's use 'staff_portal.php' since this is mostly for "Ticket Status Changed".
                createNotification($creator_id, $msg, 'info', 'staff_portal.php');
            }
        }
        
        $tasks = getDailyTasks();
        include 'tasks_list_view.php';
        exit;
    }
}

// Handle Delete Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_task') {
    requireCsrf();
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        exit;
    }
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM daily_tasks WHERE id = ?");
        $stmt->execute([$id]);
        
        $tasks = getDailyTasks();
        include 'tasks_list_view.php';
        exit;
    }
}
?>
