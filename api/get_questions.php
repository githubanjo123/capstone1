<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");
$exam_id = $_GET['exam_id'];
$sql = "SELECT q.question_id, q.question_text, q.question_type FROM exam_questions eq JOIN questions q ON eq.question_id = q.question_id WHERE eq.exam_id = $exam_id";
$result = $conn->query($sql);
$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}
echo json_encode($questions);
?>