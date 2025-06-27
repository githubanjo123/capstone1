<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");

$faculty_id = $_POST['faculty_id'] ?? '';
$subject_id = $_POST['subject_id'] ?? '';
$year_level = $_POST['year_level'] ?? '';
$section = $_POST['section'] ?? '';

// Validate inputs
if (!$faculty_id || !$subject_id || !$year_level || !$section) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit();
}

// Check if the same subject is already assigned to the same year level and section
$check = $conn->prepare("SELECT id FROM faculty_subjects WHERE subject_id = ? AND year_level = ? AND section = ?");
$check->bind_param("iis", $subject_id, $year_level, $section);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "This subject is already assigned to that year and section."]);
    exit();
}

// Insert assignment
$stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_id, year_level, section) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $faculty_id, $subject_id, $year_level, $section);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Subject assigned successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Assignment failed."]);
}
?>
