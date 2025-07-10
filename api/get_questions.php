<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

$exam_id = $_GET['exam_id'] ?? null;

if (!$exam_id) {
    echo json_encode(['error' => 'Exam ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_order, question_id");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($questions);
} catch (PDOException $e) {
    error_log("Database error in get_questions.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>