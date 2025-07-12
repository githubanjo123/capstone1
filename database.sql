-- Faculty Dashboard Exam System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS exam_system;
USE exam_system;

-- Users table (faculty, admin, students)
CREATE TABLE users (
    user_id VARCHAR(20) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    user_type ENUM('admin', 'faculty', 'student') NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Subjects table
CREATE TABLE subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    section VARCHAR(10) NOT NULL,
    faculty_id VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Exams table
CREATE TABLE exams (
    exam_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    exam_date DATE NOT NULL,
    exam_time TIME NOT NULL,
    duration INT NOT NULL, -- in minutes
    total_marks INT NOT NULL,
    instructions TEXT,
    status ENUM('draft', 'published', 'completed') DEFAULT 'draft',
    created_by VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Questions table
CREATE TABLE questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'essay') NOT NULL,
    options JSON, -- For multiple choice questions
    correct_answer TEXT,
    marks INT NOT NULL,
    question_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);

-- Student enrollments table
CREATE TABLE enrollments (
    enrollment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) NOT NULL,
    subject_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, subject_id)
);

-- Exam attempts table
CREATE TABLE exam_attempts (
    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    submitted_at TIMESTAMP NULL,
    score INT DEFAULT 0,
    total_marks INT NOT NULL,
    status ENUM('in_progress', 'completed', 'submitted') DEFAULT 'in_progress',
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Student answers table
CREATE TABLE student_answers (
    answer_id INT PRIMARY KEY AUTO_INCREMENT,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_text TEXT,
    is_correct BOOLEAN DEFAULT FALSE,
    marks_awarded INT DEFAULT 0,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(attempt_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
    UNIQUE KEY unique_answer (attempt_id, question_id)
);

-- Insert sample data

-- Admin user
INSERT INTO users (user_id, username, password_hash, user_name, user_type, email) VALUES
('ADMIN001', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin', 'admin@example.com');

-- Faculty users
INSERT INTO users (user_id, username, password_hash, user_name, user_type, email) VALUES
('FAC001', 'FAC001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Smith', 'faculty', 'john.smith@example.com'),
('FAC002', 'FAC002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Jane Doe', 'faculty', 'jane.doe@example.com');

-- Student users
INSERT INTO users (user_id, username, password_hash, user_name, user_type, email) VALUES
('2020-001', '2020-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Johnson', 'student', 'alice.johnson@example.com'),
('2020-002', '2020-002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Wilson', 'student', 'bob.wilson@example.com'),
('2020-003', '2020-003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Charlie Brown', 'student', 'charlie.brown@example.com'),
('2020-004', '2020-004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Diana Prince', 'student', 'diana.prince@example.com');

-- Subjects
INSERT INTO subjects (subject_code, subject_name, year, section, faculty_id) VALUES
('MATH101', 'Mathematics', 3, 'A', 'FAC001'),
('PHYS101', 'Physics', 2, 'B', 'FAC001'),
('CHEM101', 'Chemistry', 1, 'A', 'FAC002'),
('ENG101', 'English', 2, 'A', 'FAC002');

-- Enrollments
INSERT INTO enrollments (student_id, subject_id) VALUES
('2020-001', 1), ('2020-001', 2),
('2020-002', 1), ('2020-002', 3),
('2020-003', 2), ('2020-003', 4),
('2020-004', 3), ('2020-004', 4);

-- Sample exam (for demonstration)
INSERT INTO exams (subject_id, title, exam_date, exam_time, duration, total_marks, instructions, status, created_by) VALUES
(1, 'Mathematics Mid-Term Exam', '2024-02-15', '10:00:00', 120, 100, 'Read all questions carefully. Show your work for partial credit.', 'published', 'FAC001');

-- Sample questions
INSERT INTO questions (exam_id, question_text, question_type, options, correct_answer, marks, question_order) VALUES
(1, 'What is 2 + 2?', 'multiple_choice', '["2", "3", "4", "5"]', '3', 10, 1),
(1, 'Is the square root of 16 equal to 4?', 'true_false', NULL, 'true', 10, 2),
(1, 'Solve for x: 2x + 5 = 15', 'multiple_choice', '["5", "10", "15", "20"]', '1', 20, 3);

-- Create indexes for better performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_type ON users(user_type);
CREATE INDEX idx_subjects_faculty ON subjects(faculty_id);
CREATE INDEX idx_exams_subject ON exams(subject_id);
CREATE INDEX idx_exams_date ON exams(exam_date);
CREATE INDEX idx_questions_exam ON questions(exam_id);
CREATE INDEX idx_enrollments_student ON enrollments(student_id);
CREATE INDEX idx_enrollments_subject ON enrollments(subject_id);
CREATE INDEX idx_attempts_exam ON exam_attempts(exam_id);
CREATE INDEX idx_attempts_student ON exam_attempts(student_id);
CREATE INDEX idx_answers_attempt ON student_answers(attempt_id);
CREATE INDEX idx_answers_question ON student_answers(question_id);

-- Note: Default password for all users is 'password123'
-- Password hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi