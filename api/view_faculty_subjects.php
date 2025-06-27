<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");

$sql = "SELECT u.full_name AS faculty_name, s.subject_name, fs.year_level
        FROM faculty_subjects fs
        JOIN users u ON fs.faculty_id = u.user_id
        JOIN subjects s ON fs.subject_id = s.subject_id";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
