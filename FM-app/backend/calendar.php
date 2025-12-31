<?php
require_once 'db.php';
require_once 'auth.php';
require_once 'csrf_helper.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];
$userRole = $user['role'];
$userId = $user['id'];

// Handle GET requests (Fetch Events)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start = $_GET['start'] ?? date('Y-m-d', strtotime('-1 month'));
    $end = $_GET['end'] ?? date('Y-m-d', strtotime('+1 month'));

    $allEvents = [];

    // 1. Fetch Maintenance Tasks
    if (in_array($userRole, ['admin', 'manager', 'staff', 'technician'])) {
        $sql = "SELECT id, title, scheduled_date, status, priority FROM maintenance_logs WHERE scheduled_date BETWEEN ? AND ?";
        $params = [$start, $end];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tasks as $task) {
            $color = '#3788d8'; // Default blue
            if ($task['priority'] === 'High') $color = '#ef4444'; // Red
            if ($task['status'] === 'Closed') $color = '#10b981'; // Green

            $allEvents[] = [
                'id' => 'm_' . $task['id'],
                'title' => 'Maintenance: ' . $task['title'],
                'start' => $task['scheduled_date'],
                'color' => $color,
                'extendedProps' => [
                    'type' => 'maintenance',
                    'status' => $task['status'],
                    'priority' => $task['priority']
                ]
            ];
        }
    }

    // 2. Fetch Daily Tasks
    if (in_array($userRole, ['admin', 'manager', 'staff', 'technician'])) {
        $sql = "SELECT id, title, due_date, status, priority FROM daily_tasks WHERE due_date BETWEEN ? AND ?";
        $params = [$start, $end];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($dailyTasks as $task) {
            $color = '#f59e0b'; // Default: Amber/Orange
            // Highlight Compliance Tasks
            if (strpos($task['title'], 'Compliance:') === 0) {
                $color = '#9333ea'; // Purple
            }

            $allEvents[] = [
                'id' => 'd_' . $task['id'],
                'title' => 'Task: ' . $task['title'],
                'start' => $task['due_date'],
                'color' => $color,
                'extendedProps' => [
                    'type' => 'daily_task',
                    'status' => $task['status']
                ]
            ];
        }
    }

    // 3. Fetch Custom Events
    $sql = "SELECT * FROM events WHERE start_datetime BETWEEN ? AND ?";
    $params = [$start, $end];

    // Visibility Filter
    if ($userRole !== 'admin' && $userRole !== 'manager') {
        $sql .= " AND (
            created_by = ? 
            OR visibility_type = 'public' 
            OR (visibility_type = 'role' AND visibility_target LIKE ?) 
            OR (visibility_type = 'user' AND visibility_target LIKE ?)
        )";
        $params[] = $userId;
        $params[] = "%$userRole%";
        $params[] = "%$userId%";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($events as $event) {
        $allEvents[] = [
            'id' => 'e_' . $event['id'],
            'title' => $event['title'],
            'start' => $event['start_datetime'],
            'end' => $event['end_datetime'],
            'description' => $event['description'],
            'color' => '#8b5cf6', // Purple
            'extendedProps' => [
                'type' => 'event',
                'created_by' => $event['created_by'],
                'visibility' => $event['visibility_type']
            ]
        ];
    }

    echo json_encode($allEvents);
    exit;
}

// Handle POST requests (Create/Update/Delete Events)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }

    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        if (empty($input['title']) || empty($input['start'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        $title = $input['title'];
        $description = $input['description'] ?? '';
        $start = $input['start'];
        $end = $input['end'] ?? $start;
        $visibilityType = $input['visibility_type'] ?? 'private';
        $visibilityTarget = $input['visibility_target'] ?? '';

        if ($userRole !== 'admin' && $userRole !== 'manager') {
            $visibilityType = 'private';
        }

        $stmt = $pdo->prepare("INSERT INTO events (title, description, start_datetime, end_datetime, created_by, visibility_type, visibility_target) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $start, $end, $userId, $visibilityType, $visibilityTarget]);
        
        $eventId = $pdo->lastInsertId();

        // Send Notifications
        $notificationMsg = "New Calendar Event: " . $title;
        // Adjust link to point to frontend calendar
        $link = "../frontend/calendar.php"; 

        if ($visibilityType === 'public') {
            notifyAll($notificationMsg, 'info', $link);
        } elseif ($visibilityType === 'role') {
            $roles = explode(',', $visibilityTarget);
            foreach ($roles as $role) {
                notifyRole(trim($role), $notificationMsg, 'info', $link);
            }
        } elseif ($visibilityType === 'user') {
            $userIds = explode(',', $visibilityTarget);
            foreach ($userIds as $uid) {
                createNotification(trim($uid), $notificationMsg, 'info', $link);
            }
        }

        echo json_encode(['success' => true, 'id' => $eventId]);
        exit;
    }
    
    if ($action === 'delete') {
        $id = str_replace('e_', '', $input['id']);
        
        $stmt = $pdo->prepare("SELECT created_by FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $event = $stmt->fetch();

        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            exit;
        }

        if ($userRole !== 'admin' && $userRole !== 'manager' && $event['created_by'] != $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
