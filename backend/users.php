<?php
ob_start();
ini_set('display_errors', 0);
require_once 'db.php';
require_once 'auth.php';
require_once 'csrf_helper.php';

header('Content-Type: application/json');

// Ensure no previous output
ob_clean();

// Ensure user is logged in
requireLogin();

// Simple role check helper
function isAdmin() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list_users':
            listUsers();
            break;
        case 'add_user':
            requireCsrf();
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            addUser($_POST);
            break;
        case 'get_user':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            getUser($_GET['id']);
            break;
        case 'update_user':
            requireCsrf();
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            updateUser($_POST['id'], $_POST);
            break;
        case 'delete_user':
            requireCsrf();
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            deleteUser($_POST['id']);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function listUsers() {
    global $pdo;
    // Don't send passwords back
    $stmt = $pdo->query("SELECT id, username, full_name, email, role, created_at FROM users ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Clear buffer one last time before output
    if (ob_get_length()) ob_clean();
    
    echo json_encode($users);
}

function getUser($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username, full_name, email, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode($user);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
}

function addUser($data) {
    global $pdo;
    
    $password = $data['password'] ?? '';
    $generated = false;

    // Generate username if empty
    if (empty($data['username'])) {
        if (!empty($data['email'])) {
            $baseUsername = explode('@', $data['email'])[0];
        } elseif (!empty($data['full_name'])) {
            $baseUsername = strtolower(str_replace(' ', '.', $data['full_name']));
        } else {
            throw new Exception("Username, Email or Full Name is required to generate a login");
        }
        
        // Clean username (alphanumeric and dots only)
        $baseUsername = preg_replace('/[^a-z0-9.]/', '', strtolower($baseUsername));
        
        // Ensure uniqueness
        $username = $baseUsername;
        $counter = 1;
        while (true) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() == 0) {
                break;
            }
            $username = $baseUsername . $counter;
            $counter++;
        }
        $data['username'] = $username;
    }

    // Check for auto-generation
    if (isset($data['auto_generate_password']) && $data['auto_generate_password'] === 'true') {
        $password = generateRandomPassword();
        $generated = true;
    }
    
    // Validate required fields
    if (empty($data['username']) || empty($password)) {
        throw new Exception("Username and password are required");
    }

    // Check if username exists (double check if provided manually)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$data['username']]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Username already exists");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['username'],
        $hashed_password,
        $data['full_name'] ?? '',
        $data['email'] ?? '',
        $data['role'] ?? 'user'
    ]);

    $userId = $pdo->lastInsertId();
    $emailSent = false;

    // Send email if generated and email provided
    if ($generated && !empty($data['email'])) {
        $emailSent = sendWelcomeEmail($data['email'], $data['username'], $password);
    }

    $response = ['success' => true, 'id' => $userId];
    if ($generated) {
        $response['generated_password'] = $password;
        $response['email_sent'] = $emailSent;
    }

    echo json_encode($response);
}

function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function sendWelcomeEmail($to, $username, $password) {
    if (empty($to)) return false;

    $subject = "Welcome to FM App - Account Credentials";
    $message = "Hello,\n\n";
    $message .= "An account has been created for you in the FM App.\n\n";
    $message .= "Username: " . $username . "\n";
    $message .= "Password: " . $password . "\n\n";
    $message .= "Please log in and change your password immediately.\n";
    $message .= "Login here: http://localhost:8000/frontend/login.php\n"; // Assuming localhost
    
    $headers = 'From: noreply@fm-app.com' . "\r\n" .
        'Reply-To: noreply@fm-app.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    // Use @ to suppress warnings if mail server is not configured
    return @mail($to, $subject, $message, $headers);
}

function updateUser($id, $data) {
    global $pdo;
    
    // If password is provided, update it. Otherwise, keep existing.
    if (!empty($data['password'])) {
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name = ?, email = ?, role = ?, password = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['role'],
            $hashed_password,
            $id
        ]);
    } else {
        $sql = "UPDATE users SET full_name = ?, email = ?, role = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['role'],
            $id
        ]);
    }

    echo json_encode(['success' => true]);
}

function updateProfile($id, $data) {
    global $pdo;
    
    // Allow updating full_name, email, and password. Role cannot be changed here.
    
    if (!empty($data['password'])) {
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $hashed_password,
            $id
        ]);
    } else {
        $sql = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $id
        ]);
    }
    
    // Update session data
    $_SESSION['user']['full_name'] = $data['full_name'];
    $_SESSION['user']['email'] = $data['email'];

    echo json_encode(['success' => true]);
}

function deleteUser($id) {
    global $pdo;
    
    // Prevent deleting self
    if ($id == $_SESSION['user_id']) {
        throw new Exception("Cannot delete your own account");
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
}
