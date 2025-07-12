<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database configuration
require_once '../db.php';

try {
    $student_id = $_GET['student_id'] ?? '';
    
    if (empty($student_id)) {
        echo json_encode(['success' => false, 'message' => 'Student ID is required']);
        exit();
    }
    
    // Get student enrollments with subject and faculty details
    $stmt = $pdo->prepare("
        SELECT s.subject_id, s.subject_name, s.subject_code, s.year, s.section,
               u.user_name as faculty_name, en.enrollment_date
        FROM enrollments en
        JOIN subjects s ON en.subject_id = s.subject_id
        LEFT JOIN users u ON s.faculty_id = u.user_id
        WHERE en.student_id = ?
        ORDER BY s.subject_name
    ");
    $stmt->execute([$student_id]);
    $enrollments = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'enrollments' => $enrollments
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-student-enrollments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching enrollments'
    ]);
}
?>