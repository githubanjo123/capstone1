<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");

// Get inputs
$user_id     = $_POST['user_id'] ?? null;
$school_id   = $_POST['school_id'] ?? '';
$full_name   = $_POST['full_name'] ?? '';
$email       = $_POST['email'] ?? '';
$password    = $_POST['password'] ?? ''; // Optional; only updated if provided
$role        = $_POST['role'] ?? '';
$year_level  = $_POST['year_level'] ?? null;
$section     = $_POST['section'] ?? null;

if (!$user_id || !$school_id || !$full_name || !$role) {
    echo json_encode(["status" => "fail", "message" => "Missing required fields"]);
    exit();
}

// Build dynamic query
if (!empty($password)) {
    $query = "UPDATE users SET school_id=?, full_name=?, email=?, password=?, role=?, year_level=?, section=? WHERE user_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssi", $school_id, $full_name, $email, $password, $role, $year_level, $section, $user_id);
} else {
    $query = "UPDATE users SET school_id=?, full_name=?, email=?, role=?, year_level=?, section=? WHERE user_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssi", $school_id, $full_name, $email, $role, $year_level, $section, $user_id);
}

// Execute and respond
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => ucfirst($role) . " updated successfully."]);
} else {
    echo json_encode(["status" => "fail", "message" => "Error updating user: " . $conn->error]);
}
?>
