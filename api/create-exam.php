<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as faculty
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database configuration
require_once '../db.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit();
    }
    
    // Validate required fields
    $required_fields = ['subject_id', 'title', 'exam_date', 'exam_time', 'duration', 'total_marks', 'questions'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit();
        }
    }
    
    // Validate questions
    if (!is_array($input['questions']) || count($input['questions']) === 0) {
        echo json_encode(['success' => false, 'message' => 'At least one question is required']);
        exit();
    }
    
    // Validate question marks sum
    $question_marks_sum = 0;
    foreach ($input['questions'] as $question) {
        if (!isset($question['marks']) || !is_numeric($question['marks'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid question marks']);
            exit();
        }
        $question_marks_sum += (int)$question['marks'];
    }
    
    if ($question_marks_sum !== (int)$input['total_marks']) {
        echo json_encode(['success' => false, 'message' => 'Total marks must equal sum of question marks']);
        exit();
    }
    
    $faculty_id = $_SESSION['user_id'];
    
    // Verify faculty has access to this subject
    $subject_check_sql = "SELECT subject_id FROM subjects WHERE subject_id = ? AND faculty_id = ?";
    $subject_check_stmt = $pdo->prepare($subject_check_sql);
    $subject_check_stmt->execute([$input['subject_id'], $faculty_id]);
    
    if (!$subject_check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You do not have access to this subject']);
        exit();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert exam
        $exam_sql = "INSERT INTO exams (subject_id, title, exam_date, exam_time, duration, total_marks, instructions, created_by, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $exam_stmt = $pdo->prepare($exam_sql);
        $exam_stmt->execute([
            $input['subject_id'],
            $input['title'],
            $input['exam_date'],
            $input['exam_time'],
            $input['duration'],
            $input['total_marks'],
            $input['instructions'] ?? '',
            $faculty_id
        ]);
        
        $exam_id = $pdo->lastInsertId();
        
        // Insert questions
        $question_sql = "INSERT INTO questions (exam_id, question_text, question_type, options, correct_answer, marks, question_order) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        $question_stmt = $pdo->prepare($question_sql);
        
        foreach ($input['questions'] as $index => $question) {
            // Validate question data
            if (!isset($question['question_text']) || empty($question['question_text'])) {
                throw new Exception('Question text is required');
            }
            
            if (!isset($question['question_type']) || !in_array($question['question_type'], ['multiple_choice', 'true_false'])) {
                throw new Exception('Invalid question type');
            }
            
            if (!isset($question['correct_answer']) || empty($question['correct_answer'])) {
                throw new Exception('Correct answer is required');
            }
            
            // Prepare options JSON
            $options_json = null;
            if ($question['question_type'] === 'multiple_choice') {
                if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) !== 4) {
                    throw new Exception('Multiple choice questions must have exactly 4 options');
                }
                
                // Validate that all options are non-empty
                foreach ($question['options'] as $option) {
                    if (empty(trim($option))) {
                        throw new Exception('All options must be non-empty');
                    }
                }
                
                $options_json = json_encode($question['options']);
                
                // Validate correct answer for multiple choice (should be 1-4)
                if (!in_array($question['correct_answer'], ['1', '2', '3', '4'])) {
                    throw new Exception('Correct answer for multiple choice must be 1, 2, 3, or 4');
                }
            } else if ($question['question_type'] === 'true_false') {
                // Validate correct answer for true/false
                if (!in_array($question['correct_answer'], ['true', 'false'])) {
                    throw new Exception('Correct answer for true/false must be true or false');
                }
            }
            
            $question_stmt->execute([
                $exam_id,
                $question['question_text'],
                $question['question_type'],
                $options_json,
                $question['correct_answer'],
                $question['marks'],
                $index + 1
            ]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Exam created successfully',
            'exam_id' => $exam_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database error in create-exam.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Error in create-exam.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>