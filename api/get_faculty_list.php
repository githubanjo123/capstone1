<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

try {
    // Get all faculty members
    $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE role = 'faculty' ORDER BY full_name");
    $stmt->execute();
    $faculty = $stmt->fetchAll();

    echo json_encode($faculty);

} catch (Exception $e) {
    error_log("Get faculty list error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch faculty list']);
}
?>
