<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $stmt = $pdo->prepare("SELECT subject_id, course_code, descriptive_title FROM subjects ORDER BY course_code");
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($subjects);
} catch (PDOException $e) {
    error_log("Database error in subject_list.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
