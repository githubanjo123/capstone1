<?php
/**
 * Database Setup Script for Faculty Dashboard
 * Run this script to ensure the database is properly set up
 */

// Database configuration
$host = 'localhost';
$dbname = 'capstone';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server (without specifying database)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "✓ Database '$dbname' created/verified\n";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables
    $sql = "
        -- Users table
        CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            school_id VARCHAR(50) NOT NULL UNIQUE,
            full_name VARCHAR(100) NOT NULL,
            password VARCHAR(255) DEFAULT 'password123',
            role ENUM('admin', 'faculty', 'student') NOT NULL,
            year_level INT NULL,
            section VARCHAR(10) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Subjects table
        CREATE TABLE IF NOT EXISTS subjects (
            subject_id INT AUTO_INCREMENT PRIMARY KEY,
            course_code VARCHAR(20) NOT NULL UNIQUE,
            descriptive_title VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Subject assignments table
        CREATE TABLE IF NOT EXISTS subject_assignments (
            assignment_id INT AUTO_INCREMENT PRIMARY KEY,
            faculty_id INT NOT NULL,
            subject_id INT NOT NULL,
            year_level INT NOT NULL,
            section VARCHAR(10) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (faculty_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
            UNIQUE KEY unique_assignment (faculty_id, subject_id, year_level, section)
        );

        -- Exams table
        CREATE TABLE IF NOT EXISTS exams (
            exam_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            instructions TEXT,
            subject_id INT NOT NULL,
            year_level INT NOT NULL,
            section VARCHAR(10) NOT NULL,
            created_by INT NOT NULL,
            status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
            time_limit INT DEFAULT 60,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
        );

        -- Questions table
        CREATE TABLE IF NOT EXISTS questions (
            question_id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            question_text TEXT NOT NULL,
            question_type ENUM('multiple_choice', 'true_false', 'essay') NOT NULL,
            option_a VARCHAR(500) NULL,
            option_b VARCHAR(500) NULL,
            option_c VARCHAR(500) NULL,
            option_d VARCHAR(500) NULL,
            correct_answer VARCHAR(500) NOT NULL,
            points INT DEFAULT 1,
            question_order INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
        );

        -- Student exam attempts table
        CREATE TABLE IF NOT EXISTS exam_attempts (
            attempt_id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            student_id INT NOT NULL,
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            submitted_at TIMESTAMP NULL,
            score DECIMAL(5,2) NULL,
            total_points INT NULL,
            status ENUM('in_progress', 'submitted', 'graded') DEFAULT 'in_progress',
            FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE KEY unique_attempt (exam_id, student_id)
        );

        -- Student answers table
        CREATE TABLE IF NOT EXISTS student_answers (
            answer_id INT AUTO_INCREMENT PRIMARY KEY,
            attempt_id INT NOT NULL,
            question_id INT NOT NULL,
            student_answer TEXT,
            is_correct BOOLEAN DEFAULT FALSE,
            points_earned DECIMAL(5,2) DEFAULT 0,
            answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (attempt_id) REFERENCES exam_attempts(attempt_id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
            UNIQUE KEY unique_answer (attempt_id, question_id)
        );
    ";
    
    $pdo->exec($sql);
    echo "✓ Tables created/verified\n";
    
    // Insert sample users (if they don't exist)
    $users = [
        ['ADMIN001', 'Admin User', 'admin', NULL, NULL],
        ['FAC001', 'Dr. John Smith', 'faculty', NULL, NULL],
        ['FAC002', 'Dr. Jane Doe', 'faculty', NULL, NULL],
        ['2020-001', 'Student One', 'student', 2, 'A'],
        ['2020-002', 'Student Two', 'student', 2, 'A'],
        ['2021-001', 'Student Three', 'student', 3, 'A'],
        ['2021-002', 'Student Four', 'student', 3, 'A']
    ];
    
    $user_stmt = $pdo->prepare("INSERT IGNORE INTO users (school_id, full_name, role, year_level, section) VALUES (?, ?, ?, ?, ?)");
    foreach ($users as $user) {
        $user_stmt->execute($user);
    }
    echo "✓ Sample users inserted\n";
    
    // Insert sample subjects (if they don't exist)
    $subjects = [
        ['MATH101', 'Mathematics'],
        ['PHYS101', 'Physics'],
        ['CHEM101', 'Chemistry'],
        ['ENG101', 'English'],
        ['IT101', 'Introduction to Information Technology']
    ];
    
    $subject_stmt = $pdo->prepare("INSERT IGNORE INTO subjects (course_code, descriptive_title) VALUES (?, ?)");
    foreach ($subjects as $subject) {
        $subject_stmt->execute($subject);
    }
    echo "✓ Sample subjects inserted\n";
    
    // Insert sample subject assignments (if they don't exist)
    $assignments = [
        [2, 1, 3, 'A'],  // Dr. John Smith teaches Math to 3rd Year A
        [2, 2, 2, 'B'],  // Dr. John Smith teaches Physics to 2nd Year B
        [3, 3, 1, 'A'],  // Dr. Jane Doe teaches Chemistry to 1st Year A
        [3, 4, 2, 'A']   // Dr. Jane Doe teaches English to 2nd Year A
    ];
    
    $assignment_stmt = $pdo->prepare("INSERT IGNORE INTO subject_assignments (faculty_id, subject_id, year_level, section) VALUES (?, ?, ?, ?)");
    foreach ($assignments as $assignment) {
        $assignment_stmt->execute($assignment);
    }
    echo "✓ Sample subject assignments inserted\n";
    
    // Verify data
    $faculty_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'faculty'")->fetchColumn();
    $subject_count = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $assignment_count = $pdo->query("SELECT COUNT(*) FROM subject_assignments")->fetchColumn();
    
    echo "\n=== Database Status ===\n";
    echo "Faculty users: $faculty_count\n";
    echo "Subjects: $subject_count\n";
    echo "Subject assignments: $assignment_count\n";
    
    // Show faculty assignments
    echo "\n=== Faculty Assignments ===\n";
    $assignments_query = "
        SELECT u.full_name, s.course_code, s.descriptive_title, sa.year_level, sa.section
        FROM subject_assignments sa
        JOIN users u ON sa.faculty_id = u.user_id
        JOIN subjects s ON sa.subject_id = s.subject_id
        ORDER BY u.full_name, s.course_code
    ";
    
    $assignments_result = $pdo->query($assignments_query)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($assignments_result as $assignment) {
        echo "- {$assignment['full_name']}: {$assignment['course_code']} ({$assignment['descriptive_title']}) - Year {$assignment['year_level']} Section {$assignment['section']}\n";
    }
    
    echo "\n✅ Database setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>