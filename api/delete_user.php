<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once 'config.php';

try {
    // Check if user is logged in as admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $user_id = $_POST['user_id'] ?? '';

    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit();
    }

    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        exit();
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Delete the user (this will cascade delete related records)
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);

    echo json_encode([
        'success' => true,
        'message' => ucfirst($user['role']) . ' "' . $user['full_name'] . '" deleted successfully'
    ]);

} catch (Exception $e) {
    error_log("Delete user error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete user. Please try again.'
    ]);
}
?>
