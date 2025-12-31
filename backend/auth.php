<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'csrf_helper.php';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        
        // Generate CSRF Token
        generateCsrfToken();
        
        // Return success for HTMX or redirect
        $redirectUrl = ($user['role'] === 'staff') ? 'staff_portal.php' : 'index.php';
        
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            header("HX-Redirect: " . $redirectUrl);
            exit;
        } else {
            header("Location: ../frontend/" . $redirectUrl);
            exit;
        }
    } else {
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">Invalid username or password.</span>
                  </div>';
            exit;
        } else {
            header("Location: ../frontend/login.php?error=invalid");
            exit;
        }
    }
}

// Handle Logout
if ((isset($_GET['action']) && $_GET['action'] === 'logout') || (isset($_POST['action']) && $_POST['action'] === 'logout')) {
    // If it's a POST request, we can enforce CSRF
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrf();
    } 
    
    // Task 1: Clear all notifications for the user
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Failed to clear notifications on logout: " . $e->getMessage());
        }
    }

    session_destroy();
    
    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
        header("HX-Redirect: ../frontend/login.php");
        exit;
    }
    
    header("Location: ../frontend/login.php");
    exit;
}

// Check Authentication Function
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        // If it's an API request (HTMX, AJAX, or accessing backend directly), return 401
        if (isset($_SERVER['HTTP_HX_REQUEST']) || 
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strpos($_SERVER['SCRIPT_NAME'], '/backend/') !== false) {
            
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized', 'redirect' => '../frontend/login.php']);
            exit;
        }
        header("Location: login.php");
        exit;
    }

    // Enforce Staff Portal Separation
    if (strpos($_SERVER['SCRIPT_NAME'], '/frontend/') !== false) {
        $scriptName = basename($_SERVER['SCRIPT_NAME']);
        $role = $_SESSION['user']['role'] ?? 'user';
        
        if ($role === 'staff') {
            // Staff can ONLY see staff_portal.php and calendar.php
            if ($scriptName !== 'staff_portal.php' && $scriptName !== 'login.php' && $scriptName !== 'calendar.php') {
                header("Location: staff_portal.php");
                exit;
            }
        } else {
            // Non-staff cannot see staff_portal.php
            if ($scriptName === 'staff_portal.php') {
                header("Location: index.php");
                exit;
            }
        }
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

