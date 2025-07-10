<?php
require_once 'config.php';

try {
    $stmt = $pdo->prepare("SELECT user_id, school_id, full_name, role, year_level, section, created_at FROM users WHERE (is_deleted = 0 OR is_deleted IS NULL) ORDER BY role, full_name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($users);
} catch (PDOException $e) {
    error_log("Database error in view_all_users.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
