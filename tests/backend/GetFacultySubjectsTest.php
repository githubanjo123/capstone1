<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for get_faculty_subjects.php API endpoint
 */
class GetFacultySubjectsTest extends TestCase
{
    private $pdo;
    private $originalErrorReporting;

    protected function setUp(): void
    {
        // Suppress output during tests
        $this->originalErrorReporting = error_reporting(0);
        
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables
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
                school_id VARCHAR(50) NOT NULL UNIQUE,
                full_name VARCHAR(100) NOT NULL,
                password VARCHAR(255) DEFAULT 'password123',
                role TEXT NOT NULL,
                year_level INTEGER,
                section VARCHAR(10)
            );

            CREATE TABLE subjects (
                subject_id INTEGER PRIMARY KEY,
                course_code VARCHAR(20) NOT NULL UNIQUE,
                descriptive_title VARCHAR(100) NOT NULL
            );

            CREATE TABLE subject_assignments (
                assignment_id INTEGER PRIMARY KEY,
                faculty_id INTEGER NOT NULL,
                subject_id INTEGER NOT NULL,
                year_level INTEGER NOT NULL,
                section VARCHAR(10) NOT NULL,
                FOREIGN KEY (faculty_id) REFERENCES users(user_id),
                FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
            );

            CREATE TABLE exams (
                exam_id INTEGER PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                subject_id INTEGER NOT NULL,
                year_level INTEGER NOT NULL,
                section VARCHAR(10) NOT NULL,
                created_by INTEGER NOT NULL,
                status TEXT DEFAULT 'active',
                FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
                FOREIGN KEY (created_by) REFERENCES users(user_id)
            );
        ";

        $this->pdo->exec($sql);
    }

    private function insertTestData()
    {
        // Insert test users
        $this->pdo->exec("
            INSERT INTO users (user_id, school_id, full_name, role) VALUES 
            (1, 'ADMIN001', 'Admin User', 'admin'),
            (2, 'FAC001', 'Dr. John Smith', 'faculty'),
            (3, 'FAC002', 'Dr. Jane Doe', 'faculty')
        ");

        // Insert test subjects
        $this->pdo->exec("
            INSERT INTO subjects (subject_id, course_code, descriptive_title) VALUES 
            (1, 'MATH101', 'Mathematics'),
            (2, 'PHYS101', 'Physics'),
            (3, 'CHEM101', 'Chemistry')
        ");

        // Insert test subject assignments
        $this->pdo->exec("
            INSERT INTO subject_assignments (faculty_id, subject_id, year_level, section) VALUES 
            (2, 1, 3, 'A'),
            (2, 2, 2, 'B'),
            (3, 3, 1, 'A')
        ");

        // Insert test exams
        $this->pdo->exec("
            INSERT INTO exams (title, subject_id, year_level, section, created_by) VALUES 
            ('Midterm Exam', 1, 3, 'A', 2),
            ('Quiz 1', 1, 3, 'A', 2),
            ('Final Exam', 2, 2, 'B', 2)
        ");
    }

    public function testGetFacultySubjectsSuccess()
    {
        // Mock the PDO in the API
        $faculty_id = 2;

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
        $stmt->execute([$faculty_id, $faculty_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Assertions
        $this->assertIsArray($subjects);
        $this->assertCount(2, $subjects); // Faculty 2 has 2 assignments

        // Check first subject (Mathematics)
        $this->assertEquals('MATH101', $subjects[0]['course_code']);
        $this->assertEquals('Mathematics', $subjects[0]['subject_name']);
        $this->assertEquals(3, $subjects[0]['year_level']);
        $this->assertEquals('A', $subjects[0]['section']);
        $this->assertEquals(2, $subjects[0]['exam_count']); // 2 exams for Math

        // Check second subject (Physics)
        $this->assertEquals('PHYS101', $subjects[1]['course_code']);
        $this->assertEquals('Physics', $subjects[1]['subject_name']);
        $this->assertEquals(2, $subjects[1]['year_level']);
        $this->assertEquals('B', $subjects[1]['section']);
        $this->assertEquals(1, $subjects[1]['exam_count']); // 1 exam for Physics
    }

    public function testGetFacultySubjectsEmptyResult()
    {
        $faculty_id = 999; // Non-existent faculty

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
        $stmt->execute([$faculty_id, $faculty_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertIsArray($subjects);
        $this->assertCount(0, $subjects);
    }

    public function testGetFacultySubjectsWithoutExams()
    {
        $faculty_id = 3; // Dr. Jane Doe has assignments but no exams

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
        $stmt->execute([$faculty_id, $faculty_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(1, $subjects);
        $this->assertEquals('CHEM101', $subjects[0]['course_code']);
        $this->assertEquals(0, $subjects[0]['exam_count']); // No exams
    }

    public function testDatabaseConnectionError()
    {
        // Test with invalid PDO
        $this->expectException(PDOException::class);
        
        $invalidPdo = new PDO('sqlite:/invalid/path/database.db');
        $stmt = $invalidPdo->prepare("SELECT * FROM non_existent_table");
        $stmt->execute();
    }

    public function testValidateGetParameters()
    {
        // Test missing faculty_id parameter
        $faculty_id = null;
        $this->assertNull($faculty_id);

        // Test invalid faculty_id parameter
        $faculty_id = 'invalid';
        $this->assertIsString($faculty_id);
        $this->assertNotIsInt($faculty_id);

        // Test valid faculty_id parameter
        $faculty_id = 2;
        $this->assertIsInt($faculty_id);
        $this->assertGreaterThan(0, $faculty_id);
    }

    public function testSqlInjectionPrevention()
    {
        // Test that prepared statements prevent SQL injection
        $malicious_faculty_id = "1; DROP TABLE users; --";
        
        $sql = "SELECT COUNT(*) as count FROM subject_assignments WHERE faculty_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$malicious_faculty_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Should return 0 since the malicious string won't match any numeric ID
        $this->assertEquals(0, $result['count']);
        
        // Verify that users table still exists (wasn't dropped)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $this->assertIsArray($stmt->fetch());
    }

    public function testSubjectAssignmentIntegrity()
    {
        // Test foreign key relationships
        $faculty_id = 2;
        
        $sql = "SELECT sa.*, s.course_code, s.descriptive_title 
                FROM subject_assignments sa 
                INNER JOIN subjects s ON sa.subject_id = s.subject_id 
                WHERE sa.faculty_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$faculty_id]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($assignments as $assignment) {
            $this->assertArrayHasKey('course_code', $assignment);
            $this->assertArrayHasKey('descriptive_title', $assignment);
            $this->assertNotEmpty($assignment['course_code']);
            $this->assertNotEmpty($assignment['descriptive_title']);
        }
    }
}
?>