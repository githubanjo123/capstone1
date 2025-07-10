<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

$faculty_id = $_GET['faculty_id'] ?? null;

if (!$faculty_id) {
    echo json_encode(['error' => 'Faculty ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            sa.assignment_id,
            sa.subject_id,
            sa.year_level,
            sa.section,
            s.course_code,
            s.descriptive_title
        FROM subject_assignments sa
        JOIN subjects s ON sa.subject_id = s.subject_id
        WHERE sa.faculty_id = ?
        ORDER BY s.course_code, sa.year_level, sa.section
    ");
    $stmt->execute([$faculty_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($subjects);
} catch (PDOException $e) {
    error_log("Database error in get_subjects_by_faculty.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
