<?php
require_once 'db.php';
require_once 'csrf_helper.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // Return empty if not logged in
    exit('');
}

$action = $_GET['action'] ?? 'list';

if ($action === 'count') {
    // Return unread count badge
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo '<span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full">' . $count . '</span>';
    } else {
        echo ''; // Empty if no unread
    }
    exit;
}

if ($action === 'list') {
    // Return list of notifications (HTML)
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo '<div class="p-4 text-gray-500 text-center text-sm">No notifications</div>';
    } else {
        echo '<div class="max-h-96 overflow-y-auto">';
        echo '<ul class="divide-y divide-gray-100">';
        foreach ($notifications as $n) {
            $bgColor = $n['is_read'] ? 'bg-white' : 'bg-blue-50';
            $iconClass = match($n['type']) {
                'success' => 'text-green-500 fa-check-circle',
                'warning' => 'text-yellow-500 fa-exclamation-triangle',
                'error' => 'text-red-500 fa-times-circle',
                default => 'text-blue-500 fa-info-circle'
            };
            
            echo '<li class="' . $bgColor . ' hover:bg-gray-50 transition-colors duration-150">';
            echo '<div class="flex items-start p-3">';
            // Icon
            echo '<div class="flex-shrink-0 mt-1 ' . str_replace('fa-', '', $iconClass) . '">'; // Hack to get color class separate if needed, but here simple
            echo '<i class="fas ' . $iconClass . '"></i>';
            echo '</div>';
            
            // Content
            echo '<div class="ml-3 w-0 flex-1">';
            echo '<p class="text-sm text-gray-800">' . htmlspecialchars($n['message']) . '</p>';
            echo '<p class="text-xs text-gray-400 mt-1">' . date('M j, H:i', strtotime($n['created_at'])) . '</p>';
            if ($n['related_link']) {
                echo '<a href="' . htmlspecialchars($n['related_link']) . '" class="text-xs text-blue-600 hover:text-blue-800 mt-1 block font-medium">View Details</a>';
            }
            echo '</div>';
            
            // Actions (Mark read)
            if (!$n['is_read']) {
                echo '<button hx-post="../backend/notifications.php?action=mark_read&id=' . $n['id'] . '" 
                              hx-vals=\'{"csrf_token": "' . generateCsrfToken() . '"}\'
                              hx-swap="outerHTML" 
                              hx-target="closest li"
                              class="ml-2 text-gray-400 hover:text-blue-500" title="Mark as read">';
                echo '<i class="fas fa-check"></i>';
                echo '</button>';
            }
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
        
        // Mark all read button at bottom
        echo '<div class="p-2 bg-gray-50 border-t text-center sticky bottom-0">';
        echo '<button hx-post="../backend/notifications.php?action=mark_all_read" 
                      hx-vals=\'{"csrf_token": "' . generateCsrfToken() . '"}\'
                      hx-target="closest .notification-container" 
                      hx-swap="innerHTML" 
                      class="text-xs text-blue-600 hover:text-blue-800 font-medium w-full py-1">Mark all as read</button>';
        echo '</div>';
    }
    exit;
}

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        
        // Return nothing to remove the element from the list (since hx-swap="outerHTML" on the button targets the li?)
        // Wait, the button targets "closest li". If we return empty, the li is replaced by empty.
        // But the previous code returned the UPDATED li.
        // User wants to CLEAR it. So we return empty string.
        echo ""; 
    }
    exit;
}

if ($action === 'mark_all_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Redirect to list to refresh (re-render list)
    header("Location: notifications.php?action=list");
    exit;
}
