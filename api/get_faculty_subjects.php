<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    if (!isset($_GET['faculty_id'])) {
        echo json_encode(['error' => 'Faculty ID is required']);
        exit;
    }

    $faculty_id = $_GET['faculty_id'];

    // Get subjects assigned to faculty with exam counts
    $sql = "SELECT 
                sa.assignment_id,
                sa.subject_id,
                sa.year_level,
                sa.section,
                s.course_code,
                s.descriptive_title as subject_name,
                COUNT(e.exam_id) as exam_count
            FROM subject_assignments sa
            INNER JOIN subjects s ON sa.subject_id = s.subject_id
            LEFT JOIN exams e ON (sa.subject_id = e.subject_id 
                                 AND sa.year_level = e.year_level 
                                 AND sa.section = e.section
                                 AND e.created_by = ?)
            WHERE sa.faculty_id = ?
            GROUP BY sa.assignment_id, sa.subject_id, sa.year_level, sa.section, s.course_code, s.descriptive_title
            ORDER BY s.course_code, sa.year_level, sa.section";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$faculty_id, $faculty_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($subjects);
    
} catch (PDOException $e) {
    error_log("Database error in get_faculty_subjects.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in get_faculty_subjects.php: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred']);
}
?>