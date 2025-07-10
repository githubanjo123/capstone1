<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    $subject_id = $_POST['subject_id'] ?? $_GET['subject_id'] ?? null;

    if (!$subject_id) {
        echo json_encode(['success' => false, 'message' => 'Subject ID is required']);
        exit;
    }

    // Check if subject exists
    $stmt = $pdo->prepare("SELECT subject_id, course_code, descriptive_title FROM subjects WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();

    if (!$subject) {
        echo json_encode(['success' => false, 'message' => 'Subject not found']);
        exit;
    }

    // Check if subject is assigned to any faculty
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subject_assignments WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $assignment_count = $stmt->fetchColumn();

    if ($assignment_count > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete subject. It is assigned to faculty members.']);
        exit;
    }

    // Check if subject has any exams
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $exam_count = $stmt->fetchColumn();

    if ($exam_count > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete subject. It has associated exams.']);
        exit;
    }

    // Delete the subject
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
    $stmt->execute([$subject_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete subject']);
    }

} catch (PDOException $e) {
    error_log("Database error in delete_subject.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in delete_subject.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>