<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");

$exam_id = $_POST['exam_id'];
$question_text = $_POST['question_text'];
$question_type = $_POST['question_type'];
$correct_answer = $_POST['correct_answer'];
$created_by = $_POST['created_by'];

$exam = $conn->query("SELECT subject_id, year_level FROM exams WHERE exam_id = $exam_id")->fetch_assoc();
$stmt = $conn->prepare("INSERT INTO questions (subject_id, year_level, question_text, question_type, correct_answer, created_by) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iisssi", $exam['subject_id'], $exam['year_level'], $question_text, $question_type, $correct_answer, $created_by);
$stmt->execute();
$question_id = $conn->insert_id;
$conn->query("INSERT INTO exam_questions (exam_id, question_id) VALUES ($exam_id, $question_id)");

echo json_encode(["status" => "success", "message" => "Question added and linked to exam"]);
?>