<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_id = $_POST['exam_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;
    $answers = $_POST['answers'] ?? [];

    if (!$exam_id || !$student_id) {
        echo json_encode(['success' => false, 'message' => 'Exam ID and Student ID are required']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Create or update exam attempt
        $stmt = $pdo->prepare("INSERT INTO exam_attempts (exam_id, student_id, submitted_at, status) VALUES (?, ?, NOW(), 'submitted') ON DUPLICATE KEY UPDATE submitted_at = NOW(), status = 'submitted'");
        $stmt->execute([$exam_id, $student_id]);

        // Get attempt ID
        $attempt_id = $pdo->lastInsertId();
        if (!$attempt_id) {
            $stmt = $pdo->prepare("SELECT attempt_id FROM exam_attempts WHERE exam_id = ? AND student_id = ?");
            $stmt->execute([$exam_id, $student_id]);
            $attempt_id = $stmt->fetchColumn();
        }

        // Process answers
        $score = 0;
        $total_points = 0;

        foreach ($answers as $question_id => $answer) {
            // Get correct answer and points
            $stmt = $pdo->prepare("SELECT correct_answer, points FROM questions WHERE question_id = ?");
            $stmt->execute([$question_id]);
            $question = $stmt->fetch();

            if ($question) {
                $is_correct = ($answer == $question['correct_answer']);
                $points_earned = $is_correct ? $question['points'] : 0;
                $score += $points_earned;
                $total_points += $question['points'];

                // Save student answer
                $stmt = $pdo->prepare("INSERT INTO student_answers (attempt_id, question_id, student_answer, is_correct, points_earned) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE student_answer = ?, is_correct = ?, points_earned = ?");
                $stmt->execute([$attempt_id, $question_id, $answer, $is_correct, $points_earned, $answer, $is_correct, $points_earned]);
            }
        }

        // Update attempt with final score
        $stmt = $pdo->prepare("UPDATE exam_attempts SET score = ?, total_points = ?, status = 'graded' WHERE attempt_id = ?");
        $stmt->execute([$score, $total_points, $attempt_id]);

        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Exam submitted successfully',
            'score' => $score,
            'total_points' => $total_points,
            'percentage' => $total_points > 0 ? round(($score / $total_points) * 100, 2) : 0
        ]);

    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Database error in submit_exam.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>