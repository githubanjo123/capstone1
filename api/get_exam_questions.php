<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

try {
    // Check if user is logged in as student
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $exam_id = $_GET['exam_id'] ?? '';
    $student_id = $_SESSION['user_id'];
    
    if (empty($exam_id)) {
        echo json_encode(['error' => 'Exam ID required']);
        exit();
    }

    // First check if exam exists and is active
    $stmt = $pdo->prepare("
        SELECT e.*, s.descriptive_title as subject_name, s.course_code
        FROM exams e
        JOIN subjects s ON e.subject_id = s.subject_id
        WHERE e.exam_id = ? AND e.status = 'active'
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    if (!$exam) {
        echo json_encode(['error' => 'Exam not found or not available']);
        exit();
    }

    // Check if student already took this exam
    $stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ?");
    $stmt->execute([$exam_id, $student_id]);
    $attempt = $stmt->fetch();

    if ($attempt && $attempt['status'] === 'submitted') {
        echo json_encode(['error' => 'You have already submitted this exam']);
        exit();
    }

    // Get exam questions
    $stmt = $pdo->prepare("
        SELECT question_id, question_text, question_type, option_a, option_b, option_c, option_d, points
        FROM questions 
        WHERE exam_id = ? 
        ORDER BY question_order, question_id
    ");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll();

    // Create or update exam attempt
    if (!$attempt) {
        $stmt = $pdo->prepare("
            INSERT INTO exam_attempts (exam_id, student_id, status) 
            VALUES (?, ?, 'in_progress')
        ");
        $stmt->execute([$exam_id, $student_id]);
    }

    echo json_encode([
        'exam_title' => $exam['title'],
        'exam_instructions' => $exam['instructions'],
        'time_limit' => $exam['time_limit'],
        'subject_name' => $exam['subject_name'],
        'course_code' => $exam['course_code'],
        'questions' => $questions
    ]);

} catch (Exception $e) {
    error_log("Get exam questions error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch exam questions']);
}
?>