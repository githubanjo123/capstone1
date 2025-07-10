<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once 'config.php';

try {
    // Check if user is logged in as admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['status' => 'fail', 'message' => 'Unauthorized']);
        exit();
    }

    $course_code = $_POST['course_code'] ?? '';
    $descriptive_title = $_POST['descriptive_title'] ?? '';

    if (empty($course_code) || empty($descriptive_title)) {
        echo json_encode(['status' => 'fail', 'message' => 'Course code and descriptive title are required']);
        exit();
    }

    // Check if course code already exists
    $stmt = $pdo->prepare("SELECT course_code FROM subjects WHERE course_code = ?");
    $stmt->execute([$course_code]);
    
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'fail', 'message' => 'Course code already exists']);
        exit();
    }

    // Insert new subject
    $stmt = $pdo->prepare("INSERT INTO subjects (course_code, descriptive_title) VALUES (?, ?)");
    $stmt->execute([$course_code, $descriptive_title]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Subject added successfully'
    ]);

} catch (Exception $e) {
    error_log("Add subject error: " . $e->getMessage());
    echo json_encode([
        'status' => 'fail',
        'message' => 'Failed to add subject. Please try again.'
    ]);
}
?>
