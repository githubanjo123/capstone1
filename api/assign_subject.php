<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $faculty_id = $_POST['faculty_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $section = $_POST['section'] ?? '';

    if (empty($faculty_id) || empty($subject_id) || empty($year_level) || empty($section)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO subject_assignments (faculty_id, subject_id, year_level, section) VALUES (?, ?, ?, ?)");
        $stmt->execute([$faculty_id, $subject_id, $year_level, $section]);
        
        echo json_encode(['success' => true, 'message' => 'Subject assigned successfully']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            echo json_encode(['success' => false, 'message' => 'This assignment already exists']);
        } else {
            error_log("Database error in assign_subject.php: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
