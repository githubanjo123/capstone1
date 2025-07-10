<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

try {
    // Check if user is logged in as admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    // Get all users except the current admin
    $stmt = $pdo->prepare("
        SELECT user_id, school_id, full_name, role, year_level, section, created_at, updated_at
        FROM users 
        WHERE user_id != ?
        ORDER BY role DESC, created_at DESC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll();

    echo json_encode($users);

} catch (Exception $e) {
    error_log("View all users error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch users']);
}
?>
