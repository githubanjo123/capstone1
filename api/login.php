<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

require_once 'config.php';

$school_id = $_POST['school_id'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($school_id) || empty($password)) {
    echo json_encode(["status" => "fail", "message" => "School ID and password required"]);
    exit();
}

try {
    // Get user by school_id, ensuring they are not deleted
    $stmt = $pdo->prepare("SELECT * FROM users WHERE school_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
    $stmt->execute([$school_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $stored_password = $user['password'];
        $role = $user['role'];

        $password_is_valid = false;

        if ($role === 'admin') {
            // Admin uses plain text comparison
            $password_is_valid = ($password === $stored_password);
        } else {
            // Students and faculty use hashed password verification
            $password_is_valid = password_verify($password, $stored_password);
        }

        if ($password_is_valid) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $role;
            $_SESSION['full_name'] = $user['full_name'];
            echo json_encode([
                "status" => "success",
                "message" => "Welcome " . $user['full_name'],
                "role" => $role
            ]);
        } else {
            echo json_encode(["status" => "fail", "message" => "Invalid credentials"]);
        }
    } else {
        echo json_encode(["status" => "fail", "message" => "Invalid credentials or user is deleted"]);
    }
} catch (PDOException $e) {
    error_log("Database error in login.php: " . $e->getMessage());
    echo json_encode(["status" => "fail", "message" => "Database error occurred"]);
}
?>
