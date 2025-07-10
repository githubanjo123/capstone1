<?php
require_once 'config.php';

try {
    $stmt = $pdo->prepare("SELECT user_id, school_id, full_name FROM users WHERE role = 'faculty' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY full_name");
    $stmt->execute();
    $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($faculty);
} catch (PDOException $e) {
    error_log("Database error in get_faculty_list.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
