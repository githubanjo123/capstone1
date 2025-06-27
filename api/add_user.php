<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Connect to database
$conn = new mysqli("localhost", "root", "", "capstone");

// Get data from POST
$school_id = $_POST['school_id'] ?? '';
$full_name = $_POST['full_name'] ?? '';
$role = $_POST['role'] ?? '';
$year_level = !empty($_POST['year_level']) ? (int)$_POST['year_level'] : null;
$section = !empty($_POST['section']) ? $_POST['section'] : null;

// Validate required fields
if (!$school_id || !$full_name || !$role) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit();
}

// Generate default password: [school_id][year_level][section]
$password_plain = $school_id . $year_level . $section;

// Hash the password for secure storage
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

// If email isn't provided, leave it blank
$email = "";

// Prepare SQL statement
$query = "INSERT INTO users (school_id, full_name, email, password, role, year_level, section)
          VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssssis", $school_id, $full_name, $email, $password_hashed, $role, $year_level, $section);

// Execute and respond
if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => ucfirst($role) . " added successfully.",
        "default_password" => $password_plain  // For admin viewing only
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Error adding user: " . $conn->error]);
}
?>
