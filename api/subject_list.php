<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

try {
    // Get all subjects
    $stmt = $pdo->prepare("SELECT subject_id, course_code, descriptive_title FROM subjects ORDER BY course_code");
    $stmt->execute();
    $subjects = $stmt->fetchAll();

    echo json_encode($subjects);

} catch (Exception $e) {
    error_log("Subject list error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch subjects']);
}
?>
