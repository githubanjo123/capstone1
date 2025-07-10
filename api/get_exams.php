<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $student_id = $_SESSION['user_id'];
    $year_level = $_SESSION['year_level'];
    $section = $_SESSION['section'];

    // Get exams available for this student's year and section
    $stmt = $pdo->prepare("
        SELECT 
            e.exam_id,
            e.title,
            e.instructions,
            e.time_limit,
            e.status,
            s.descriptive_title as subject_name,
            s.course_code,
            CASE 
                WHEN ea.attempt_id IS NOT NULL THEN 'taken'
                ELSE 'available'
            END as attempt_status,
            ea.score,
            ea.submitted_at
        FROM exams e
        JOIN subjects s ON e.subject_id = s.subject_id
        LEFT JOIN exam_attempts ea ON e.exam_id = ea.exam_id AND ea.student_id = ?
        WHERE e.year_level = ? 
        AND e.section = ? 
        AND e.status = 'active'
        ORDER BY e.created_at DESC
    ");
    
    $stmt->execute([$student_id, $year_level, $section]);
    $exams = $stmt->fetchAll();

    echo json_encode($exams);

} catch (Exception $e) {
    error_log("Get exams error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch exams']);
}
?>