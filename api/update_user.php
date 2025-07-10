<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

try {
    $user_id = $_POST['user_id'] ?? null;
    $full_name = $_POST['full_name'] ?? '';
    $school_id = $_POST['school_id'] ?? '';
    $role = $_POST['role'] ?? '';
    $year_level = $_POST['year_level'] ?? null;
    $section = $_POST['section'] ?? null;

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    // Validate required fields
    if (empty($full_name) || empty($school_id) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Full name, school ID, and role are required']);
        exit;
    }

    // Update user
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, school_id = ?, role = ?, year_level = ?, section = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->execute([$full_name, $school_id, $role, $year_level, $section, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or user not found']);
    }

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'School ID already exists']);
    } else {
        error_log("Database error in update_user.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} catch (Exception $e) {
    error_log("General error in update_user.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>