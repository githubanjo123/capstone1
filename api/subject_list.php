<?php
header('Content-Type: application/json');

// Connect to database
$conn = new mysqli("localhost", "root", "", "capstone");

// Check connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// Query updated subject fields
$result = $conn->query("SELECT subject_id, course_code, descriptive_title FROM subjects");

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Output JSON
echo json_encode($data);
$conn->close();
?>
