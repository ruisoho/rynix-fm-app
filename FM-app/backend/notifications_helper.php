<?php
require_once 'db.php';

/**
 * Create a notification for a specific user
 */
function createNotification($user_id, $message, $type = 'info', $link = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type, related_link) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $message, $type, $link]);
        return true;
    } catch (PDOException $e) {
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send a notification to all users with a specific role
 */
function notifyRole($role, $message, $type = 'info', $link = null) {
    global $pdo;
    try {
        // If role is 'admin', we might want to include 'manager' too, or handle multiple roles
        // For now, simple exact match
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ?");
        $stmt->execute([$role]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($users as $user_id) {
            createNotification($user_id, $message, $type, $link);
        }
        return true;
    } catch (PDOException $e) {
        error_log("Notify Role Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send a notification to all users
 */
function notifyAll($message, $type = 'info', $link = null) {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT id FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($users as $user_id) {
            createNotification($user_id, $message, $type, $link);
        }
        return true;
    } catch (PDOException $e) {
        error_log("Notify All Error: " . $e->getMessage());
        return false;
    }
}
