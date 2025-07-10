<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration Tests for Faculty Dashboard
 * Tests the complete workflow from frontend to backend
 */
class FacultyDashboardIntegrationTest extends TestCase
{
    private $pdo;
    private $facultyId;

    protected function setUp(): void
    {
        $this->pdo = TestHelpers::createTestPDO();
        TestHelpers::createTestTables($this->pdo);
        TestHelpers::insertTestData($this->pdo);
        $this->facultyId = 2; // Dr. John Smith
    }

    protected function tearDown(): void
    {
        TestHelpers::cleanupTestData($this->pdo);
        $this->pdo = null;
    }

    public function testCompleteExamCreationWorkflow()
    {
        // Step 1: Faculty loads their classes
        $classes = $this->getFacultySubjects($this->facultyId);
        
        $this->assertIsArray($classes);
        $this->assertGreaterThan(0, count($classes));
        $this->assertArrayHasKey('subject_name', $classes[0]);
        $this->assertArrayHasKey('course_code', $classes[0]);

        // Step 2: Faculty selects a class
        $selectedClass = $classes[0];
        $this->assertEquals('Mathematics', $selectedClass['subject_name']);
        $this->assertEquals('MATH101', $selectedClass['course_code']);

        // Step 3: Faculty creates an exam with questions
        $examData = [
            'title' => 'Integration Test Exam',
            'instructions' => 'Complete integration test instructions.',
            'subject_id' => $selectedClass['subject_id'],
            'year_level' => $selectedClass['year_level'],
            'section' => $selectedClass['section'],
            'created_by' => $this->facultyId,
            'questions' => [
                [
                    'text' => 'What is the integration of x?',
                    'type' => 'multiple_choice',
                    'options' => [
                        'A' => 'x²/2',
                        'B' => 'x²/2 + C',
                        'C' => 'x',
                        'D' => '1'
                    ],
                    'correct_answer' => 'B',
                    'points' => 2
                ],
                [
                    'text' => 'Is calculus a branch of mathematics?',
                    'type' => 'true_false',
                    'correct_answer' => 'True',
                    'points' => 1
                ]
            ]
        ];

        // Step 4: Create the exam
        $examResult = $this->createExam($examData);
        
        $this->assertTrue($examResult['success']);
        $this->assertIsNumeric($examResult['exam_id']);
        $this->assertEquals(2, $examResult['question_count']);

        // Step 5: Verify exam was created in database
        $examId = $examResult['exam_id'];
        $savedExam = $this->getExamFromDatabase($examId);
        
        $this->assertNotFalse($savedExam);
        $this->assertEquals($examData['title'], $savedExam['title']);
        $this->assertEquals($examData['subject_id'], $savedExam['subject_id']);

        // Step 6: Verify questions were saved correctly
        $savedQuestions = $this->getQuestionsFromDatabase($examId);
        
        $this->assertCount(2, $savedQuestions);
        
        // Check multiple choice question
        $mcQuestion = $savedQuestions[0];
        $this->assertEquals('What is the integration of x?', $mcQuestion['question_text']);
        $this->assertEquals('multiple_choice', $mcQuestion['question_type']);
        $this->assertEquals('B', $mcQuestion['correct_answer']);
        $this->assertEquals(2, $mcQuestion['points']);

        // Check true/false question
        $tfQuestion = $savedQuestions[1];
        $this->assertEquals('Is calculus a branch of mathematics?', $tfQuestion['question_text']);
        $this->assertEquals('true_false', $tfQuestion['question_type']);
        $this->assertEquals('True', $tfQuestion['correct_answer']);
        $this->assertEquals(1, $tfQuestion['points']);

        // Step 7: Verify updated exam count for faculty
        $updatedClasses = $this->getFacultySubjects($this->facultyId);
        $mathClass = array_filter($updatedClasses, function($class) {
            return $class['course_code'] === 'MATH101';
        });
        $mathClass = array_values($mathClass)[0];
        
        $this->assertEquals(1, $mathClass['exam_count']);
    }

    public function testMultipleExamCreationWorkflow()
    {
        // Create multiple exams for the same class
        $classes = $this->getFacultySubjects($this->facultyId);
        $mathClass = $classes[0];

        // Create first exam
        $exam1Data = [
            'title' => 'Midterm Exam',
            'instructions' => 'Midterm examination.',
            'subject_id' => $mathClass['subject_id'],
            'year_level' => $mathClass['year_level'],
            'section' => $mathClass['section'],
            'created_by' => $this->facultyId,
            'questions' => [
                [
                    'text' => 'What is 1 + 1?',
                    'type' => 'multiple_choice',
                    'options' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'],
                    'correct_answer' => 'B',
                    'points' => 1
                ]
            ]
        ];

        $exam1Result = $this->createExam($exam1Data);
        $this->assertTrue($exam1Result['success']);

        // Create second exam
        $exam2Data = [
            'title' => 'Final Exam',
            'instructions' => 'Final examination.',
            'subject_id' => $mathClass['subject_id'],
            'year_level' => $mathClass['year_level'],
            'section' => $mathClass['section'],
            'created_by' => $this->facultyId,
            'questions' => [
                [
                    'text' => 'What is 2 + 2?',
                    'type' => 'multiple_choice',
                    'options' => ['A' => '2', 'B' => '3', 'C' => '4', 'D' => '5'],
                    'correct_answer' => 'C',
                    'points' => 1
                ]
            ]
        ];

        $exam2Result = $this->createExam($exam2Data);
        $this->assertTrue($exam2Result['success']);

        // Verify both exams exist
        $exam1 = $this->getExamFromDatabase($exam1Result['exam_id']);
        $exam2 = $this->getExamFromDatabase($exam2Result['exam_id']);

        $this->assertNotFalse($exam1);
        $this->assertNotFalse($exam2);
        $this->assertEquals('Midterm Exam', $exam1['title']);
        $this->assertEquals('Final Exam', $exam2['title']);

        // Verify exam count updated correctly
        $updatedClasses = $this->getFacultySubjects($this->facultyId);
        $updatedMathClass = array_filter($updatedClasses, function($class) {
            return $class['course_code'] === 'MATH101';
        });
        $updatedMathClass = array_values($updatedMathClass)[0];
        
        $this->assertEquals(2, $updatedMathClass['exam_count']);
    }

    public function testErrorHandlingWorkflow()
    {
        // Test validation errors
        $invalidExamData = [
            'title' => '', // Empty title
            'subject_id' => 999, // Invalid subject
            'year_level' => 3,
            'section' => 'A',
            'created_by' => $this->facultyId,
            'questions' => [] // No questions
        ];

        // This should fail validation
        $errors = $this->validateExamData($invalidExamData);
        $this->assertNotEmpty($errors);
        $this->assertContains('Title is required', $errors);
        $this->assertContains('At least one question is required', $errors);
    }

    public function testDifferentQuestionTypesWorkflow()
    {
        $classes = $this->getFacultySubjects($this->facultyId);
        $selectedClass = $classes[0];

        $examData = [
            'title' => 'Mixed Question Types Exam',
            'instructions' => 'Various question types.',
            'subject_id' => $selectedClass['subject_id'],
            'year_level' => $selectedClass['year_level'],
            'section' => $selectedClass['section'],
            'created_by' => $this->facultyId,
            'questions' => [
                // Multiple choice with different point values
                [
                    'text' => 'Complex question worth 5 points',
                    'type' => 'multiple_choice',
                    'options' => ['A' => 'Option 1', 'B' => 'Option 2', 'C' => 'Option 3', 'D' => 'Option 4'],
                    'correct_answer' => 'A',
                    'points' => 5
                ],
                // True/False with default points
                [
                    'text' => 'Simple true/false question',
                    'type' => 'true_false',
                    'correct_answer' => 'False',
                    'points' => 1
                ],
                // Another multiple choice
                [
                    'text' => 'Another multiple choice',
                    'type' => 'multiple_choice',
                    'options' => ['A' => 'Wrong', 'B' => 'Correct', 'C' => 'Wrong', 'D' => 'Wrong'],
                    'correct_answer' => 'B',
                    'points' => 3
                ]
            ]
        ];

        $result = $this->createExam($examData);
        $this->assertTrue($result['success']);

        $questions = $this->getQuestionsFromDatabase($result['exam_id']);
        $this->assertCount(3, $questions);

        // Verify point values
        $this->assertEquals(5, $questions[0]['points']);
        $this->assertEquals(1, $questions[1]['points']);
        $this->assertEquals(3, $questions[2]['points']);

        // Verify total possible points
        $totalPoints = array_sum(array_column($questions, 'points'));
        $this->assertEquals(9, $totalPoints);
    }

    public function testConcurrentExamCreation()
    {
        // Simulate multiple faculty members creating exams simultaneously
        $faculty1 = 2; // Dr. John Smith
        $faculty2 = 3; // Dr. Jane Doe

        $classes1 = $this->getFacultySubjects($faculty1);
        $classes2 = $this->getFacultySubjects($faculty2);

        // Faculty 1 creates exam
        $exam1Data = [
            'title' => 'Math Exam by Faculty 1',
            'subject_id' => $classes1[0]['subject_id'],
            'year_level' => $classes1[0]['year_level'],
            'section' => $classes1[0]['section'],
            'created_by' => $faculty1,
            'questions' => [
                [
                    'text' => 'Math question',
                    'type' => 'true_false',
                    'correct_answer' => 'True',
                    'points' => 1
                ]
            ]
        ];

        // Faculty 2 creates exam
        $exam2Data = [
            'title' => 'Chemistry Exam by Faculty 2',
            'subject_id' => $classes2[0]['subject_id'],
            'year_level' => $classes2[0]['year_level'],
            'section' => $classes2[0]['section'],
            'created_by' => $faculty2,
            'questions' => [
                [
                    'text' => 'Chemistry question',
                    'type' => 'true_false',
                    'correct_answer' => 'False',
                    'points' => 1
                ]
            ]
        ];

        // Both should succeed
        $result1 = $this->createExam($exam1Data);
        $result2 = $this->createExam($exam2Data);

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertNotEquals($result1['exam_id'], $result2['exam_id']);

        // Verify both exams exist independently
        $exam1 = $this->getExamFromDatabase($result1['exam_id']);
        $exam2 = $this->getExamFromDatabase($result2['exam_id']);

        $this->assertEquals($faculty1, $exam1['created_by']);
        $this->assertEquals($faculty2, $exam2['created_by']);
    }

    // Helper methods that simulate API calls

    private function getFacultySubjects($facultyId)
    {
        $sql = "SELECT 
                    sa.assignment_id,
                    sa.subject_id,
                    sa.year_level,
                    sa.section,
                    s.course_code,
                    s.descriptive_title as subject_name,
                    COUNT(e.exam_id) as exam_count
                FROM subject_assignments sa
                INNER JOIN subjects s ON sa.subject_id = s.subject_id
                LEFT JOIN exams e ON (sa.subject_id = e.subject_id 
                                     AND sa.year_level = e.year_level 
                                     AND sa.section = e.section
                                     AND e.created_by = ?)
                WHERE sa.faculty_id = ?
                GROUP BY sa.assignment_id, sa.subject_id, sa.year_level, sa.section, s.course_code, s.descriptive_title
                ORDER BY s.course_code, sa.year_level, sa.section";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$facultyId, $facultyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function createExam($examData)
    {
        try {
            $this->pdo->beginTransaction();

            // Insert exam
            $exam_sql = "INSERT INTO exams (title, instructions, subject_id, year_level, section, created_by, created_at, status) 
                         VALUES (?, ?, ?, ?, ?, ?, datetime('now'), 'active')";
            
            $exam_stmt = $this->pdo->prepare($exam_sql);
            $exam_stmt->execute([
                $examData['title'],
                $examData['instructions'] ?? '',
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

            foreach ($examData['questions'] as $question) {
                $question_stmt->execute([
                    $exam_id,
                    $question['text'],
                    $question['type'],
                    $question['options']['A'] ?? null,
                    $question['options']['B'] ?? null,
                    $question['options']['C'] ?? null,
                    $question['options']['D'] ?? null,
                    $question['correct_answer'],
                    $question['points'] ?? 1
                ]);
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Exam created successfully!',
                'exam_id' => $exam_id,
                'question_count' => count($examData['questions'])
            ];

        } catch (Exception $e) {
            $this->pdo->rollback();
            return [
                'success' => false,
                'message' => 'Failed to create exam: ' . $e->getMessage()
            ];
        }
    }

    private function getExamFromDatabase($examId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM exams WHERE exam_id = ?");
        $stmt->execute([$examId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getQuestionsFromDatabase($examId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_id");
        $stmt->execute([$examId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validateExamData($examData)
    {
        $errors = [];
        
        if (empty(trim($examData['title'] ?? ''))) {
            $errors[] = 'Title is required';
        }
        
        if (empty($examData['questions'] ?? [])) {
            $errors[] = 'At least one question is required';
        }
        
        return $errors;
    }
}
?>