<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Start session
session_start();

try {
    // Destroy all session data
    session_unset();
    session_destroy();
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Logout failed']);
}
?>