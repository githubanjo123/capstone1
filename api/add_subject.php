<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");

// Get POST data
$course_code = $_POST['course_code'] ?? '';
$descriptive_title = $_POST['descriptive_title'] ?? '';

// Validate input
if (!$course_code || !$descriptive_title) {
    echo json_encode(["status" => "fail", "message" => "Both Course Code and Descriptive Title are required"]);
    exit();
}

// Check for duplicate course code
$check = $conn->prepare("SELECT subject_id FROM subjects WHERE course_code = ?");
$check->bind_param("s", $course_code);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["status" => "fail", "message" => "Course code already exists"]);
    exit();
}

// Insert into database
$stmt = $conn->prepare("INSERT INTO subjects (course_code, descriptive_title) VALUES (?, ?)");
$stmt->bind_param("ss", $course_code, $descriptive_title);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Subject added successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
