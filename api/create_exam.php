<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
        exit;
    }

    // Validate required fields
    $required_fields = ['title', 'subject_id', 'year_level', 'section', 'created_by'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            exit;
        }
    }

    $title = trim($_POST['title']);
    $instructions = isset($_POST['instructions']) ? trim($_POST['instructions']) : '';
    $subject_id = (int)$_POST['subject_id'];
    $year_level = (int)$_POST['year_level'];
    $section = trim($_POST['section']);
    $created_by = (int)$_POST['created_by'];
    
    // Parse questions
    $questions = [];
    if (isset($_POST['questions']) && !empty($_POST['questions'])) {
        $questions = json_decode($_POST['questions'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'Invalid questions format']);
            exit;
        }
    }

    if (empty($questions)) {
        echo json_encode(['success' => false, 'message' => 'At least one question is required']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Insert exam
        $exam_sql = "INSERT INTO exams (title, instructions, subject_id, year_level, section, created_by, created_at, status) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), 'active')";
        
        $exam_stmt = $pdo->prepare($exam_sql);
        $exam_stmt->execute([$title, $instructions, $subject_id, $year_level, $section, $created_by]);
        
        $exam_id = $pdo->lastInsertId();

        // Insert questions
        $question_sql = "INSERT INTO questions (exam_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer, points, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $question_stmt = $pdo->prepare($question_sql);

        foreach ($questions as $question) {
            $question_text = $question['text'];
            $question_type = $question['type'];
            $points = isset($question['points']) ? (int)$question['points'] : 1;
            $correct_answer = $question['correct_answer'];

            $option_a = null;
            $option_b = null;
            $option_c = null;
            $option_d = null;

            if ($question_type === 'multiple_choice' && isset($question['options'])) {
                $option_a = $question['options']['A'] ?? null;
                $option_b = $question['options']['B'] ?? null;
                $option_c = $question['options']['C'] ?? null;
                $option_d = $question['options']['D'] ?? null;
            }

            $question_stmt->execute([
                $exam_id,
                $question_text,
                $question_type,
                $option_a,
                $option_b,
                $option_c,
                $option_d,
                $correct_answer,
                $points
            ]);
        }

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Exam created successfully!',
            'exam_id' => $exam_id,
            'question_count' => count($questions)
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Database error in create_exam.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in create_exam.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>