-- Database schema for the Exam System
-- Execute this SQL to set up the required tables

CREATE DATABASE IF NOT EXISTS capstone;
USE capstone;

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

-- Insert sample data
INSERT IGNORE INTO users (school_id, full_name, role, year_level, section) VALUES
('ADMIN001', 'Admin User', 'admin', NULL, NULL),
('FAC001', 'Dr. John Smith', 'faculty', NULL, NULL),
('FAC002', 'Dr. Jane Doe', 'faculty', NULL, NULL),
('2020-001', 'Student One', 'student', 2, 'A'),
('2020-002', 'Student Two', 'student', 2, 'A'),
('2021-001', 'Student Three', 'student', 3, 'A'),
('2021-002', 'Student Four', 'student', 3, 'A');

INSERT IGNORE INTO subjects (course_code, descriptive_title) VALUES
('MATH101', 'Mathematics'),
('PHYS101', 'Physics'),
('CHEM101', 'Chemistry'),
('ENG101', 'English'),
('IT101', 'Introduction to Information Technology');

-- Sample subject assignments
INSERT IGNORE INTO subject_assignments (faculty_id, subject_id, year_level, section) VALUES
(2, 1, 3, 'A'),  -- Dr. John Smith teaches Math to 3rd Year A
(2, 2, 2, 'B'),  -- Dr. John Smith teaches Physics to 2nd Year B
(3, 3, 1, 'A'),  -- Dr. Jane Doe teaches Chemistry to 1st Year A
(3, 4, 2, 'A');  -- Dr. Jane Doe teaches English to 2nd Year A