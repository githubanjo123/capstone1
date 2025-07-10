<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once 'config.php';

try {
    // Check if user is logged in as admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $school_id = $_POST['school_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? '';
    $year_level = $_POST['year_level'] ?? null;
    $section = $_POST['section'] ?? null;

    // Validate required fields
    if (empty($school_id) || empty($full_name) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'School ID, full name, and role are required']);
        exit();
    }

    // Validate role
    if (!in_array($role, ['admin', 'faculty', 'student'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid role specified']);
        exit();
    }

    // For students, year level and section are required
    if ($role === 'student' && (empty($year_level) || empty($section))) {
        echo json_encode(['success' => false, 'message' => 'Year level and section are required for students']);
        exit();
    }

    // Check if school_id already exists
    $stmt = $pdo->prepare("SELECT school_id FROM users WHERE school_id = ?");
    $stmt->execute([$school_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'School ID already exists']);
        exit();
    }

    // Set year_level and section to null for non-students
    if ($role !== 'student') {
        $year_level = null;
        $section = null;
    }

    // Insert new user with default password
    $stmt = $pdo->prepare("
        INSERT INTO users (school_id, full_name, password, role, year_level, section) 
        VALUES (?, ?, 'password123', ?, ?, ?)
    ");
    
    $stmt->execute([$school_id, $full_name, $role, $year_level, $section]);

    echo json_encode([
        'success' => true,
        'message' => ucfirst($role) . ' added successfully'
    ]);

} catch (Exception $e) {
    error_log("Add user error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add user. Please try again.'
    ]);
}
?>
