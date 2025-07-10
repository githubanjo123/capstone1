<?php
require_once 'config.php';

try {
    $stmt = $pdo->prepare("SELECT user_id, school_id, full_name, year_level, section FROM users WHERE role = 'student' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY year_level, section, full_name");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($students);
} catch (PDOException $e) {
    error_log("Database error in view_students.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>
