<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode([
        'authenticated' => false,
        'message' => 'Please log in to continue'
    ]);
    exit();
}

echo json_encode([
    'authenticated' => true,
    'user_id' => $_SESSION['user_id'],
    'role' => $_SESSION['role'],
    'full_name' => $_SESSION['full_name'],
    'school_id' => $_SESSION['school_id'],
    'year_level' => $_SESSION['year_level'] ?? null,
    'section' => $_SESSION['section'] ?? null
]);
?>