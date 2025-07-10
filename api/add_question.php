<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST data (this would typically come from a form)
    // For now, just return success
    echo json_encode(['success' => true, 'message' => 'Question functionality to be implemented']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>