<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once 'config.php';

try {
    // Check if user is logged in as admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $assignment_id = $_POST['assignment_id'] ?? '';

    if (empty($assignment_id)) {
        echo json_encode(['success' => false, 'message' => 'Assignment ID is required']);
        exit();
    }

    // Check if assignment exists
    $stmt = $pdo->prepare("
        SELECT sa.*, u.full_name as faculty_name, s.course_code 
        FROM subject_assignments sa
        JOIN users u ON sa.faculty_id = u.user_id
        JOIN subjects s ON sa.subject_id = s.subject_id
        WHERE sa.assignment_id = ?
    ");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit();
    }

    // Delete the assignment
    $stmt = $pdo->prepare("DELETE FROM subject_assignments WHERE assignment_id = ?");
    $stmt->execute([$assignment_id]);

    echo json_encode([
        'success' => true,
        'message' => "Removed {$assignment['course_code']} from {$assignment['faculty_name']} successfully"
    ]);

} catch (Exception $e) {
    error_log("Delete assignment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to remove assignment. Please try again.'
    ]);
}
?>