<?php
header('Content-Type: application/json');

// 1. Connect to DB
$conn = new mysqli("localhost", "root", "", "capstone");

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// 2. Join faculty_subjects, users (faculty), and subjects
$sql = "
SELECT 
    fs.id AS assignment_id,            -- assignment ID
    u.full_name AS faculty_name,       -- faculty name
    s.course_code,                     -- subject course number (e.g., IT310)
    s.descriptive_title,               -- subject full title
    fs.year_level,                     -- year level
    fs.section                         -- section
FROM faculty_subjects fs
JOIN users u ON fs.faculty_id = u.user_id
JOIN subjects s ON fs.subject_id = s.subject_id
ORDER BY u.full_name ASC, fs.year_level ASC, fs.section ASC
";

$result = $conn->query($sql);
$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>
