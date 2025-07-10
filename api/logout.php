<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

try {
    // Destroy session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);

} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Logout failed'
    ]);
}
?>