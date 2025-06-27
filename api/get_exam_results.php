<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => "fail", "message" => "Unauthorized"]);
    exit();
}
$conn = new mysqli("localhost", "root", "", "capstone");
$sql = "SELECT u.full_name, e.title, et.score, et.datetime_submitted FROM exam_taken et JOIN users u ON et.student_id = u.user_id JOIN exams e ON et.exam_id = e.exam_id ORDER BY et.datetime_submitted DESC";
$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}
echo json_encode($data);
?>