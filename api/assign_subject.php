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

    $faculty_id = $_POST['faculty_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $section = $_POST['section'] ?? '';

    // Validate inputs
    if (empty($faculty_id) || empty($subject_id) || empty($year_level) || empty($section)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    // Check if faculty exists
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ? AND role = 'faculty'");
    $stmt->execute([$faculty_id]);
    $faculty = $stmt->fetch();
    
    if (!$faculty) {
        echo json_encode(['success' => false, 'message' => 'Faculty member not found']);
        exit();
    }

    // Check if subject exists
    $stmt = $pdo->prepare("SELECT course_code FROM subjects WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();
    
    if (!$subject) {
        echo json_encode(['success' => false, 'message' => 'Subject not found']);
        exit();
    }

    // Check if assignment already exists
    $stmt = $pdo->prepare("
        SELECT assignment_id FROM subject_assignments 
        WHERE faculty_id = ? AND subject_id = ? AND year_level = ? AND section = ?
    ");
    $stmt->execute([$faculty_id, $subject_id, $year_level, $section]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This assignment already exists']);
        exit();
    }

    // Insert new assignment
    $stmt = $pdo->prepare("
        INSERT INTO subject_assignments (faculty_id, subject_id, year_level, section) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$faculty_id, $subject_id, $year_level, $section]);

    echo json_encode([
        'success' => true,
        'message' => "Successfully assigned {$subject['course_code']} to {$faculty['full_name']} for Year {$year_level} Section {$section}"
    ]);

} catch (Exception $e) {
    error_log("Assign subject error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to assign subject. Please try again.'
    ]);
}
?>
