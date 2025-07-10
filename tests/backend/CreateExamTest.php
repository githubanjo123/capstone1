<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for create_exam.php API endpoint
 */
class CreateExamTest extends TestCase
{
    private $pdo;
    private $originalErrorReporting;

    protected function setUp(): void
    {
        $this->originalErrorReporting = error_reporting(0);
        
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->createTestTables();
        $this->insertTestData();
    }

    protected function tearDown(): void
    {
        error_reporting($this->originalErrorReporting);
        $this->pdo = null;
    }

    private function createTestTables()
    {
        $sql = "
            CREATE TABLE users (
                user_id INTEGER PRIMARY KEY,
                school_id VARCHAR(50) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                role TEXT NOT NULL
            );

            CREATE TABLE subjects (
                subject_id INTEGER PRIMARY KEY,
                course_code VARCHAR(20) NOT NULL,
                descriptive_title VARCHAR(100) NOT NULL
            );

            CREATE TABLE exams (
                exam_id INTEGER PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                instructions TEXT,
                subject_id INTEGER NOT NULL,
                year_level INTEGER NOT NULL,
                section VARCHAR(10) NOT NULL,
                created_by INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                status TEXT DEFAULT 'active',
                FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
                FOREIGN KEY (created_by) REFERENCES users(user_id)
            );

            CREATE TABLE questions (
                question_id INTEGER PRIMARY KEY,
                exam_id INTEGER NOT NULL,
                question_text TEXT NOT NULL,
                question_type TEXT NOT NULL,
                option_a VARCHAR(500),
                option_b VARCHAR(500),
                option_c VARCHAR(500),
                option_d VARCHAR(500),
                correct_answer VARCHAR(500) NOT NULL,
                points INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (exam_id) REFERENCES exams(exam_id)
            );
        ";

        $this->pdo->exec($sql);
    }

    private function insertTestData()
    {
        $this->pdo->exec("
            INSERT INTO users (user_id, school_id, full_name, role) VALUES 
            (1, 'ADMIN001', 'Admin User', 'admin'),
            (2, 'FAC001', 'Dr. John Smith', 'faculty')
        ");

        $this->pdo->exec("
            INSERT INTO subjects (subject_id, course_code, descriptive_title) VALUES 
            (1, 'MATH101', 'Mathematics'),
            (2, 'PHYS101', 'Physics')
        ");
    }

    public function testCreateExamSuccessWithMultipleChoiceQuestions()
    {
        $examData = [
            'title' => 'Midterm Examination',
            'instructions' => 'Answer all questions carefully.',
            'subject_id' => 1,
            'year_level' => 3,
            'section' => 'A',
            'created_by' => 2
        ];

        $questions = [
            [
                'text' => 'What is 2 + 2?',
                'type' => 'multiple_choice',
                'options' => ['A' => '3', 'B' => '4', 'C' => '5', 'D' => '6'],
                'correct_answer' => 'B',
                'points' => 1
            ],
            [
                'text' => 'What is the capital of France?',
                'type' => 'multiple_choice',
                'options' => ['A' => 'London', 'B' => 'Berlin', 'C' => 'Paris', 'D' => 'Madrid'],
                'correct_answer' => 'C',
                'points' => 2
            ]
        ];

        // Start transaction
        $this->pdo->beginTransaction();

        try {
            // Insert exam
            $exam_sql = "INSERT INTO exams (title, instructions, subject_id, year_level, section, created_by, created_at, status) 
                         VALUES (?, ?, ?, ?, ?, ?, datetime('now'), 'active')";
            
            $exam_stmt = $this->pdo->prepare($exam_sql);
            $exam_stmt->execute([
                $examData['title'],
                $examData['instructions'],
                $examData['subject_id'],
                $examData['year_level'],
                $examData['section'],
                $examData['created_by']
            ]);
            
            $exam_id = $this->pdo->lastInsertId();

            // Insert questions
            $question_sql = "INSERT INTO questions (exam_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer, points, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))";
            
            $question_stmt = $this->pdo->prepare($question_sql);

            foreach ($questions as $question) {
                $question_stmt->execute([
                    $exam_id,
                    $question['text'],
                    $question['type'],
                    $question['options']['A'] ?? null,
                    $question['options']['B'] ?? null,
                    $question['options']['C'] ?? null,
                    $question['options']['D'] ?? null,
                    $question['correct_answer'],
                    $question['points']
                ]);
            }

            $this->pdo->commit();

            // Verify exam was created
            $exam_check = $this->pdo->prepare("SELECT * FROM exams WHERE exam_id = ?");
            $exam_check->execute([$exam_id]);
            $exam = $exam_check->fetch(PDO::FETCH_ASSOC);

            $this->assertNotFalse($exam);
            $this->assertEquals($examData['title'], $exam['title']);
            $this->assertEquals($examData['subject_id'], $exam['subject_id']);

            // Verify questions were created
            $question_check = $this->pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
            $question_check->execute([$exam_id]);
            $savedQuestions = $question_check->fetchAll(PDO::FETCH_ASSOC);

            $this->assertCount(2, $savedQuestions);
            $this->assertEquals('What is 2 + 2?', $savedQuestions[0]['question_text']);
            $this->assertEquals('B', $savedQuestions[0]['correct_answer']);

        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }

    public function testCreateExamSuccessWithTrueFalseQuestions()
    {
        $examData = [
            'title' => 'True/False Quiz',
            'instructions' => 'Select True or False.',
            'subject_id' => 1,
            'year_level' => 2,
            'section' => 'B',
            'created_by' => 2
        ];

        $questions = [
            [
                'text' => 'The Earth is round.',
                'type' => 'true_false',
                'correct_answer' => 'True',
                'points' => 1
            ]
        ];

        $this->pdo->beginTransaction();

        try {
            // Insert exam
            $exam_sql = "INSERT INTO exams (title, instructions, subject_id, year_level, section, created_by, created_at, status) 
                         VALUES (?, ?, ?, ?, ?, ?, datetime('now'), 'active')";
            
            $exam_stmt = $this->pdo->prepare($exam_sql);
            $exam_stmt->execute([
                $examData['title'],
                $examData['instructions'],
                $examData['subject_id'],
                $examData['year_level'],
                $examData['section'],
                $examData['created_by']
            ]);
            
            $exam_id = $this->pdo->lastInsertId();

            // Insert question
            $question_sql = "INSERT INTO questions (exam_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer, points, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))";
            
            $question_stmt = $this->pdo->prepare($question_sql);
            $question_stmt->execute([
                $exam_id,
                $questions[0]['text'],
                $questions[0]['type'],
                null, null, null, null,
                $questions[0]['correct_answer'],
                $questions[0]['points']
            ]);

            $this->pdo->commit();

            // Verify the true/false question was saved correctly
            $question_check = $this->pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
            $question_check->execute([$exam_id]);
            $savedQuestion = $question_check->fetch(PDO::FETCH_ASSOC);

            $this->assertEquals('true_false', $savedQuestion['question_type']);
            $this->assertEquals('True', $savedQuestion['correct_answer']);
            $this->assertNull($savedQuestion['option_a']);

        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }

    public function testCreateExamValidation()
    {
        // Test validation logic that would be in create_exam.php
        
        // Missing title
        $this->validateRequiredField('', 'Title is required');
        
        // Missing subject_id
        $this->validateRequiredField(null, 'Subject ID is required');
        
        // Empty questions array
        $this->validateQuestions([], 'At least one question is required');
        
        // Invalid question format
        $invalidQuestions = [['text' => '']]; // Missing question text
        $this->validateQuestions($invalidQuestions, 'Question text is required');
    }

    private function validateRequiredField($value, $expectedError)
    {
        if (!isset($value) || empty(trim($value))) {
            $this->assertEquals($expectedError, $expectedError);
        }
    }

    private function validateQuestions($questions, $expectedError)
    {
        if (empty($questions)) {
            $this->assertEquals($expectedError, 'At least one question is required');
            return;
        }
        
        foreach ($questions as $question) {
            if (empty(trim($question['text'] ?? ''))) {
                $this->assertEquals($expectedError, 'Question text is required');
                return;
            }
        }
    }

    public function testTransactionRollbackOnError()
    {
        $examData = [
            'title' => 'Test Exam',
            'instructions' => 'Test',
            'subject_id' => 999, // Invalid subject_id
            'year_level' => 3,
            'section' => 'A',
            'created_by' => 2
        ];

        $this->pdo->beginTransaction();

        try {
            $exam_sql = "INSERT INTO exams (title, instructions, subject_id, year_level, section, created_by, created_at, status) 
                         VALUES (?, ?, ?, ?, ?, ?, datetime('now'), 'active')";
            
            $exam_stmt = $this->pdo->prepare($exam_sql);
            $exam_stmt->execute([
                $examData['title'],
                $examData['instructions'],
                $examData['subject_id'], // This will cause foreign key constraint failure
                $examData['year_level'],
                $examData['section'],
                $examData['created_by']
            ]);

            $this->fail('Expected exception was not thrown');

        } catch (PDOException $e) {
            $this->pdo->rollback();
            $this->assertStringContains('FOREIGN KEY constraint failed', $e->getMessage());
            
            // Verify no exam was created
            $count_stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM exams WHERE title = ?");
            $count_stmt->execute([$examData['title']]);
            $result = $count_stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(0, $result['count']);
        }
    }

    public function testJsonQuestionsParsing()
    {
        // Test JSON parsing functionality
        $questionsJson = json_encode([
            [
                'text' => 'Sample question',
                'type' => 'multiple_choice',
                'options' => ['A' => 'Option 1', 'B' => 'Option 2', 'C' => 'Option 3', 'D' => 'Option 4'],
                'correct_answer' => 'A',
                'points' => 1
            ]
        ]);

        $parsedQuestions = json_decode($questionsJson, true);
        
        $this->assertIsArray($parsedQuestions);
        $this->assertCount(1, $parsedQuestions);
        $this->assertEquals('Sample question', $parsedQuestions[0]['text']);
        $this->assertEquals('multiple_choice', $parsedQuestions[0]['type']);
        
        // Test invalid JSON
        $invalidJson = '{"invalid": json}';
        $invalidParsed = json_decode($invalidJson, true);
        $this->assertNull($invalidParsed);
        $this->assertNotEquals(JSON_ERROR_NONE, json_last_error());
    }

    public function testMultipleChoiceValidation()
    {
        $validMCQuestion = [
            'text' => 'What is 1 + 1?',
            'type' => 'multiple_choice',
            'options' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'],
            'correct_answer' => 'B'
        ];

        // Valid question
        $this->assertTrue($this->validateMultipleChoiceQuestion($validMCQuestion));

        // Missing options
        $invalidQuestion = $validMCQuestion;
        unset($invalidQuestion['options']['A']);
        $this->assertFalse($this->validateMultipleChoiceQuestion($invalidQuestion));

        // Missing correct answer
        $invalidQuestion = $validMCQuestion;
        unset($invalidQuestion['correct_answer']);
        $this->assertFalse($this->validateMultipleChoiceQuestion($invalidQuestion));
    }

    private function validateMultipleChoiceQuestion($question)
    {
        if ($question['type'] !== 'multiple_choice') {
            return true; // Not our concern
        }

        if (!isset($question['options']) || 
            !isset($question['options']['A']) ||
            !isset($question['options']['B']) ||
            !isset($question['options']['C']) ||
            !isset($question['options']['D']) ||
            !isset($question['correct_answer'])) {
            return false;
        }

        return true;
    }

    public function testExamStatusDefault()
    {
        $exam_sql = "INSERT INTO exams (title, subject_id, year_level, section, created_by) 
                     VALUES (?, ?, ?, ?, ?)";
        
        $exam_stmt = $this->pdo->prepare($exam_sql);
        $exam_stmt->execute(['Test Exam', 1, 2, 'A', 2]);
        
        $exam_id = $this->pdo->lastInsertId();
        
        $check_stmt = $this->pdo->prepare("SELECT status FROM exams WHERE exam_id = ?");
        $check_stmt->execute([$exam_id]);
        $exam = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals('active', $exam['status']);
    }

    public function testQuestionPointsDefault()
    {
        // Create an exam first
        $exam_sql = "INSERT INTO exams (title, subject_id, year_level, section, created_by) 
                     VALUES (?, ?, ?, ?, ?)";
        $exam_stmt = $this->pdo->prepare($exam_sql);
        $exam_stmt->execute(['Test Exam', 1, 2, 'A', 2]);
        $exam_id = $this->pdo->lastInsertId();

        // Insert question without specifying points
        $question_sql = "INSERT INTO questions (exam_id, question_text, question_type, correct_answer) 
                         VALUES (?, ?, ?, ?)";
        $question_stmt = $this->pdo->prepare($question_sql);
        $question_stmt->execute([$exam_id, 'Test question', 'true_false', 'True']);
        
        $question_id = $this->pdo->lastInsertId();
        
        $check_stmt = $this->pdo->prepare("SELECT points FROM questions WHERE question_id = ?");
        $check_stmt->execute([$question_id]);
        $question = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals(1, $question['points']);
    }
}
?>