<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session to check if user is logged in
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Admin privileges required.'
    ]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'capstone';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Get and validate input
$subject_id = isset($_POST['subject_id']) ? trim($_POST['subject_id']) : '';

if (empty($subject_id) || !is_numeric($subject_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid subject ID provided'
    ]);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // First, check if subject exists and get its details
    $checkStmt = $pdo->prepare("SELECT subject_id, course_code, descriptive_title FROM subjects WHERE subject_id = ?");
    $checkStmt->execute([$subject_id]);
    $subject = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subject) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Subject not found'
        ]);
        exit;
    }
    
    // Check if subject is assigned to any faculty
    $assignmentStmt = $pdo->prepare("SELECT COUNT(*) as count FROM faculty_subjects WHERE subject_id = ?");
    $assignmentStmt->execute([$subject_id]);
    $assignmentCount = $assignmentStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($assignmentCount > 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete subject. It is currently assigned to faculty members. Please remove all assignments first.'
        ]);
        exit;
    }
    
    // Delete the subject
    $deleteStmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
    $deleteResult = $deleteStmt->execute([$subject_id]);
    
    if ($deleteResult && $deleteStmt->rowCount() > 0) {
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Subject '{$subject['course_code']}' has been successfully deleted"
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete subject. Please try again.'
        ]);
    }
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Check for foreign key constraint errors
    if ($e->getCode() == '23000') {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete subject. It is referenced by other records in the system.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred: ' . $e->getMessage()
    ]);
}
?>