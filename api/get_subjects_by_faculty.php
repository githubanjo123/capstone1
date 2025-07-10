<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

try {
    // Get all subject assignments with faculty and subject details
    $stmt = $pdo->prepare("
        SELECT 
            sa.assignment_id,
            u.full_name AS faculty_name,
            s.course_code,
            s.descriptive_title,
            sa.year_level,
            sa.section
        FROM subject_assignments sa
        JOIN users u ON sa.faculty_id = u.user_id
        JOIN subjects s ON sa.subject_id = s.subject_id
        ORDER BY u.full_name ASC, sa.year_level ASC, sa.section ASC
    ");
    
    $stmt->execute();
    $assignments = $stmt->fetchAll();

    echo json_encode($assignments);

} catch (Exception $e) {
    error_log("Get subjects by faculty error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch assignments']);
}
?>
