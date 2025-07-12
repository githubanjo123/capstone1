<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database configuration
require_once '../db.php';

try {
    $subject_id = $_GET['subject_id'] ?? '';
    
    if (empty($subject_id)) {
        echo json_encode(['success' => false, 'message' => 'Subject ID is required']);
        exit();
    }
    
    // Get subject details
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();
    
    if (!$subject) {
        echo json_encode(['success' => false, 'message' => 'Subject not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'subject' => $subject
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-subject.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching subject details'
    ]);
}
?>