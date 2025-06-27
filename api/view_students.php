<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");

$year = $_GET['year_level'] ?? '';
$section = $_GET['section'] ?? '';

$stmt = $conn->prepare("SELECT full_name, school_id FROM users WHERE role='student' AND year_level=? AND section=?");
$stmt->bind_param("is", $year, $section);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
