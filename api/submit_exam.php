<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once 'config.php';

try {
    // Check if user is logged in as student
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $exam_id = $_POST['exam_id'] ?? '';
    $answers = json_decode($_POST['answers'] ?? '{}', true);
    $student_id = $_SESSION['user_id'];
    
    if (empty($exam_id)) {
        echo json_encode(['success' => false, 'message' => 'Exam ID required']);
        exit();
    }

    // Start transaction
    $pdo->beginTransaction();

    // Check if exam exists and is active
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_id = ? AND status = 'active'");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    if (!$exam) {
        throw new Exception('Exam not found or not available');
    }

    // Check if attempt exists
    $stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ?");
    $stmt->execute([$exam_id, $student_id]);
    $attempt = $stmt->fetch();

    if (!$attempt) {
        // Create new attempt
        $stmt = $pdo->prepare("
            INSERT INTO exam_attempts (exam_id, student_id, status) 
            VALUES (?, ?, 'in_progress')
        ");
        $stmt->execute([$exam_id, $student_id]);
        $attempt_id = $pdo->lastInsertId();
    } else {
        if ($attempt['status'] === 'submitted') {
            throw new Exception('Exam already submitted');
        }
        $attempt_id = $attempt['attempt_id'];
    }

    // Get all questions for this exam
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_order, question_id");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_points = 0;
    $earned_points = 0;

    // Process each question and answer
    foreach ($questions as $question) {
        $question_id = $question['question_id'];
        $points = $question['points'];
        $correct_answer = $question['correct_answer'];
        $student_answer = $answers[$question_id] ?? null;
        
        $total_points += $points;
        $is_correct = false;
        $points_earned = 0;
        
        if ($student_answer !== null) {
            // Check if answer is correct
            if (strtolower(trim($student_answer)) === strtolower(trim($correct_answer))) {
                $is_correct = true;
                $points_earned = $points;
                $earned_points += $points;
            }
            
            // Insert or update student answer
            $stmt = $pdo->prepare("
                INSERT INTO student_answers (attempt_id, question_id, student_answer, is_correct, points_earned)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                student_answer = VALUES(student_answer),
                is_correct = VALUES(is_correct),
                points_earned = VALUES(points_earned),
                answered_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$attempt_id, $question_id, $student_answer, $is_correct, $points_earned]);
        }
    }

    // Calculate score percentage
    $score_percentage = $total_points > 0 ? round(($earned_points / $total_points) * 100, 2) : 0;

    // Update exam attempt
    $stmt = $pdo->prepare("
        UPDATE exam_attempts 
        SET status = 'submitted', 
            submitted_at = CURRENT_TIMESTAMP,
            score = ?,
            total_points = ?
        WHERE attempt_id = ?
    ");
    $stmt->execute([$score_percentage, $total_points, $attempt_id]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Exam submitted successfully',
        'score' => $earned_points,
        'total_points' => $total_points,
        'percentage' => $score_percentage,
        'attempt_id' => $attempt_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Submit exam error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>