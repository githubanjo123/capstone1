<?php
header('Content-Type: application/json');
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'capstone';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get input data
$user_id = trim($_POST['user_id'] ?? '');
$school_id = trim($_POST['school_id'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$role = trim($_POST['role'] ?? '');
$year_level = trim($_POST['year_level'] ?? '') ?: null;
$section = trim($_POST['section'] ?? '') ?: null;

// Validate required fields
if (!$user_id || !$school_id || !$full_name || !in_array($role, ['student', 'faculty', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing or invalid role']);
    exit;
}

// Student validation
if ($role === 'student' && (!$year_level || $year_level < 1 || $year_level > 4 || !$section)) {
    echo json_encode(['success' => false, 'message' => 'Year level (1-4) and section required for students']);
    exit;
}

// Set null for non-students
if ($role !== 'student') {
    $year_level = null;
    $section = null;
}

try {
    // Check if user exists
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $checkStmt->execute([$user_id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Check duplicate school_id
    $duplicateStmt = $pdo->prepare("SELECT user_id FROM users WHERE school_id = ? AND user_id != ?");
    $duplicateStmt->execute([$school_id, $user_id]);
    if ($duplicateStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'School ID already exists']);
        exit;
    }
    
    // Update user
    $updateStmt = $pdo->prepare("UPDATE users SET school_id = ?, full_name = ?, role = ?, year_level = ?, section = ? WHERE user_id = ?");
    $result = $updateStmt->execute([$school_id, $full_name, $role, $year_level, $section, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>