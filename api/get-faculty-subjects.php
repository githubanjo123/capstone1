<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as faculty
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database configuration
require_once '../db.php';

try {
    $faculty_id = $_SESSION['user_id'];
    
    // Get subjects assigned to this faculty with exam counts
    $sql = "SELECT 
                s.subject_id,
                s.subject_name,
                s.year,
                s.section,
                COUNT(e.exam_id) as exam_count
            FROM subjects s
            LEFT JOIN exams e ON s.subject_id = e.subject_id
            WHERE s.faculty_id = ?
            GROUP BY s.subject_id, s.subject_name, s.year, s.section
            ORDER BY s.subject_name, s.year, s.section";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$faculty_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get-faculty-subjects.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Error in get-faculty-subjects.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching subjects'
    ]);
}
?>