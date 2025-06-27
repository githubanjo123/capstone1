<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");

$result = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'faculty' AND is_deleted = 0");

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
