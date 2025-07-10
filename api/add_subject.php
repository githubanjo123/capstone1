<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_code = $_POST['course_code'] ?? '';
    $descriptive_title = $_POST['descriptive_title'] ?? '';

    if (empty($course_code) || empty($descriptive_title)) {
        echo json_encode(['success' => false, 'message' => 'Course code and descriptive title are required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO subjects (course_code, descriptive_title) VALUES (?, ?)");
        $stmt->execute([$course_code, $descriptive_title]);
        
        echo json_encode(['success' => true, 'message' => 'Subject added successfully']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            echo json_encode(['success' => false, 'message' => 'Course code already exists']);
        } else {
            error_log("Database error in add_subject.php: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
