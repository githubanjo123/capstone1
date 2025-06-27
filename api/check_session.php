<?php
session_start();
header('Content-Type: application/json');
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "loggedIn" => true,
        "user_id" => $_SESSION['user_id'],
        "role" => $_SESSION['role'],
        "full_name" => $_SESSION['full_name']
    ]);
} else {
    echo json_encode(["loggedIn" => false]);
}
?>