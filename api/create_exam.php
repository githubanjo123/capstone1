<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");
$title = $_POST['title'];
$subject_id = $_POST['subject_id'];
$year_level = $_POST['year_level'];
$created_by = $_POST['created_by'];
$stmt = $conn->prepare("INSERT INTO exams (title, subject_id, year_level, created_by) VALUES (?, ?, ?, ?)");
$stmt->bind_param("siii", $title, $subject_id, $year_level, $created_by);
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Exam created successfully"]);
} else {
    echo json_encode(["status" => "fail", "message" => $stmt->error]);
}
?>