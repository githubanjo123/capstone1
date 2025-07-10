<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $school_id = $_POST['school_id'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($school_id) || empty($password)) {
        echo json_encode(["status" => "fail", "message" => "School ID and password required"]);
        exit();
    }

    // Get user by school_id
    $stmt = $pdo->prepare("SELECT * FROM users WHERE school_id = ?");
    $stmt->execute([$school_id]);
    $user = $stmt->fetch();

    if ($user) {
        $password_is_valid = false;
        
        // Check password - for now, using plain text comparison for all users
        // In production, you should hash passwords
        if ($password === $user['password']) {
            $password_is_valid = true;
        }

        if ($password_is_valid) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['school_id'] = $user['school_id'];
            
            if ($user['role'] === 'student') {
                $_SESSION['year_level'] = $user['year_level'];
                $_SESSION['section'] = $user['section'];
            }
            
            echo json_encode([
                "status" => "success",
                "message" => "Welcome " . $user['full_name'],
                "role" => $user['role']
            ]);
        } else {
            echo json_encode(["status" => "fail", "message" => "Invalid credentials"]);
        }
    } else {
        echo json_encode(["status" => "fail", "message" => "Invalid credentials"]);
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(["status" => "fail", "message" => "Login failed. Please try again."]);
}
?>
