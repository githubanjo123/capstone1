<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

try {
    // Check if user is logged in as faculty
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $faculty_id = $_SESSION['user_id'];

    // Get faculty's assigned subjects with exam counts
    $stmt = $pdo->prepare("
        SELECT 
            sa.subject_id,
            s.course_code,
            s.descriptive_title as subject_name,
            sa.year_level,
            sa.section,
            COUNT(e.exam_id) as exam_count
        FROM subject_assignments sa
        JOIN subjects s ON sa.subject_id = s.subject_id
        LEFT JOIN exams e ON sa.subject_id = e.subject_id 
            AND sa.year_level = e.year_level 
            AND sa.section = e.section
            AND e.created_by = sa.faculty_id
        WHERE sa.faculty_id = ?
        GROUP BY sa.assignment_id
        ORDER BY s.descriptive_title, sa.year_level, sa.section
    ");
    
    $stmt->execute([$faculty_id]);
    $subjects = $stmt->fetchAll();

    echo json_encode($subjects);

} catch (Exception $e) {
    error_log("Get faculty subjects error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch subjects']);
}
?>