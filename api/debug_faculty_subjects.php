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
    $debug = [];
    
    // Check if faculty_id is provided
    $faculty_id = isset($_GET['faculty_id']) ? $_GET['faculty_id'] : null;
    $debug['faculty_id_provided'] = $faculty_id;
    
    if (!$faculty_id) {
        echo json_encode(['error' => 'Faculty ID is required', 'debug' => $debug]);
        exit;
    }

    // Check if users table exists and has data
    $debug['checking_users_table'] = true;
    $users_check = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'faculty'");
    $faculty_count = $users_check->fetch(PDO::FETCH_ASSOC);
    $debug['total_faculty_users'] = $faculty_count['count'];
    
    // Get all faculty users
    $faculty_users = $pdo->query("SELECT user_id, school_id, full_name FROM users WHERE role = 'faculty'")->fetchAll(PDO::FETCH_ASSOC);
    $debug['faculty_users'] = $faculty_users;
    
    // Check if specific faculty exists
    $faculty_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'faculty'");
    $faculty_stmt->execute([$faculty_id]);
    $faculty_exists = $faculty_stmt->fetch(PDO::FETCH_ASSOC);
    $debug['faculty_exists'] = $faculty_exists ? true : false;
    $debug['faculty_data'] = $faculty_exists;

    // Check if subjects table exists and has data
    $subjects_check = $pdo->query("SELECT COUNT(*) as count FROM subjects");
    $subjects_count = $subjects_check->fetch(PDO::FETCH_ASSOC);
    $debug['total_subjects'] = $subjects_count['count'];
    
    // Get all subjects
    $subjects = $pdo->query("SELECT * FROM subjects")->fetchAll(PDO::FETCH_ASSOC);
    $debug['subjects'] = $subjects;

    // Check if subject_assignments table exists and has data
    $assignments_check = $pdo->query("SELECT COUNT(*) as count FROM subject_assignments");
    $assignments_count = $assignments_check->fetch(PDO::FETCH_ASSOC);
    $debug['total_assignments'] = $assignments_count['count'];
    
    // Get all subject assignments
    $assignments = $pdo->query("SELECT * FROM subject_assignments")->fetchAll(PDO::FETCH_ASSOC);
    $debug['all_assignments'] = $assignments;
    
    // Check assignments for specific faculty
    $faculty_assignments_stmt = $pdo->prepare("SELECT * FROM subject_assignments WHERE faculty_id = ?");
    $faculty_assignments_stmt->execute([$faculty_id]);
    $faculty_assignments = $faculty_assignments_stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug['faculty_assignments'] = $faculty_assignments;

    // Run the actual query to get faculty subjects
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

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$faculty_id, $faculty_id]);
    $subjects_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects_result,
        'debug' => $debug
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => $debug ?? []
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'General error: ' . $e->getMessage(),
        'debug' => $debug ?? []
    ]);
}
?>