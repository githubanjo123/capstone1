<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $stmt = $pdo->prepare("
        SELECT 
            ea.attempt_id,
            ea.exam_id,
            ea.score,
            ea.total_points,
            ea.submitted_at,
            u.full_name as student_name,
            u.school_id,
            e.title as exam_title
        FROM exam_attempts ea
        JOIN users u ON ea.student_id = u.user_id
        JOIN exams e ON ea.exam_id = e.exam_id
        WHERE ea.status = 'submitted'
        ORDER BY ea.submitted_at DESC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} catch (PDOException $e) {
    error_log("Database error in get_exam_results.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>