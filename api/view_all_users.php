<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "capstone");

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed']));
}

// Get all users (students and faculty), excluding deleted users, with better sorting
$sql = "SELECT user_id, school_id, full_name, role, year_level, section 
        FROM users 
        WHERE is_deleted = 0
        ORDER BY 
            CASE WHEN role = 'faculty' THEN 1 ELSE 2 END,
            year_level ASC,
            section ASC,
            full_name ASC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

$users = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($users);

$conn->close();
?>
