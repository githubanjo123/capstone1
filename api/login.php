<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "capstone");

if ($conn->connect_error) {
    echo json_encode(["status" => "fail", "message" => "DB error: " . $conn->connect_error]);
    exit();
}

$school_id = $_POST['school_id'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($school_id) || empty($password)) {
    echo json_encode(["status" => "fail", "message" => "School ID and password required"]);
    exit();
}

// Get user by school_id, ensuring they are not deleted
$stmt = $conn->prepare("SELECT * FROM users WHERE school_id = ? AND is_deleted = 0");
$stmt->bind_param("s", $school_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
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
?>
