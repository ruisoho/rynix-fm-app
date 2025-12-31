<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a CSRF token and store it in the session.
 * @return string The generated token.
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the CSRF token from the request.
 * @param string|null $token The token to verify (optional, defaults to $_POST['csrf_token'] or header).
 * @return bool True if valid, False otherwise.
 */
function verifyCsrfToken($token = null) {
    if (!$token) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require a valid CSRF token, otherwise terminate the request.
 */
function requireCsrf() {
    if (!verifyCsrfToken()) {
        http_response_code(403);
        if (isset($_SERVER['HTTP_HX_REQUEST']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['error' => 'CSRF Token Validation Failed']);
        } else {
            die('CSRF Token Validation Failed');
        }
        exit;
    }
}
