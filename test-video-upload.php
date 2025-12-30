<?php
session_start();
header('Content-Type: application/json');

// Check what's being received
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1; // Fake user for testing

echo json_encode([
    'post_data' => $_POST,
    'files' => array_keys($_FILES),
    'file_type' => $_FILES['file']['type'] ?? 'N/A',
    'file_name' => $_FILES['file']['name'] ?? 'N/A',
    'file_size' => $_FILES['file']['size'] ?? 'N/A',
    'file_error' => $_FILES['file']['error'] ?? 'N/A',
    'session_user_id' => $_SESSION['user_id'] ?? 'N/A'
], JSON_PRETTY_PRINT);
?>
