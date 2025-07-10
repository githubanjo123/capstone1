<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once 'config.php';

try {
    // Check if user is logged in as faculty
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $faculty_id = $_SESSION['user_id'];
    $title = $_POST['title'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    $time_limit = $_POST['time_limit'] ?? 60;
    $subject_id = $_POST['subject_id'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $section = $_POST['section'] ?? '';
    $questions = json_decode($_POST['questions'] ?? '[]', true);

    // Validate required fields
    if (empty($title) || empty($subject_id) || empty($year_level) || empty($section)) {
        echo json_encode(['success' => false, 'message' => 'Title, subject, year level, and section are required']);
        exit();
    }

    if (empty($questions) || !is_array($questions)) {
        echo json_encode(['success' => false, 'message' => 'At least one question is required']);
        exit();
    }

    // Verify faculty has permission to create exam for this subject/class
    $stmt = $pdo->prepare("
        SELECT * FROM subject_assignments 
        WHERE faculty_id = ? AND subject_id = ? AND year_level = ? AND section = ?
    ");
    $stmt->execute([$faculty_id, $subject_id, $year_level, $section]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to teach this subject and class']);
        exit();
    }

    // Start transaction
    $pdo->beginTransaction();

    // Create exam
    $stmt = $pdo->prepare("
        INSERT INTO exams (title, instructions, subject_id, year_level, section, created_by, time_limit, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$title, $instructions, $subject_id, $year_level, $section, $faculty_id, $time_limit]);
    $exam_id = $pdo->lastInsertId();

    // Insert questions
    $question_order = 1;
    foreach ($questions as $question) {
        $question_text = $question['text'] ?? '';
        $question_type = $question['type'] ?? 'multiple_choice';
        $points = $question['points'] ?? 1;
        $correct_answer = $question['correct_answer'] ?? '';
        
        $option_a = null;
        $option_b = null;
        $option_c = null;
        $option_d = null;
        
        if ($question_type === 'multiple_choice' && isset($question['options'])) {
            $option_a = $question['options']['A'] ?? '';
            $option_b = $question['options']['B'] ?? '';
            $option_c = $question['options']['C'] ?? '';
            $option_d = $question['options']['D'] ?? '';
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO questions (
                exam_id, question_text, question_type, option_a, option_b, option_c, option_d, 
                correct_answer, points, question_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $exam_id, $question_text, $question_type, $option_a, $option_b, $option_c, $option_d,
            $correct_answer, $points, $question_order
        ]);
        
        $question_order++;
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Exam created successfully with ' . count($questions) . ' questions',
        'exam_id' => $exam_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Create exam error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create exam. Please try again.'
    ]);
}
?>