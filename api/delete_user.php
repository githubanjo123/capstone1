<?php
header('Content-Type: application/json');
session_start();

// Ensure only admins can delete users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database configuration
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

// Ensure the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get user_id from POST
$user_id = trim($_POST['user_id'] ?? '');

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_deleted = 0");
    $stmt->execute([$user_id]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User not found or already deleted']);
        exit;
    }

    // Soft delete the user
    $deleteStmt = $pdo->prepare("UPDATE users SET is_deleted = 1 WHERE user_id = ?");
    $deleteStmt->execute([$user_id]);

    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
