<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $school_id = $_POST['school_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? '';
    $year_level = $_POST['year_level'] ?? null;
    $section = $_POST['section'] ?? null;
    $password = 'password123'; // Default password

    if ($role !== 'admin' && $role !== 'student') {
        $password = password_hash($password, PASSWORD_DEFAULT);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (school_id, full_name, password, role, year_level, section) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$school_id, $full_name, $password, $role, $year_level, $section]);
        
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            echo json_encode(['success' => false, 'message' => 'School ID already exists']);
        } else {
            error_log("Database error in add_user.php: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
