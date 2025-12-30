<?php
session_start();
header('Content-Type: application/json');

require_once '../db_config.php';

if (isset($db_error)) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $db_error]);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 1;

$result = $conn->query("SELECT id, title, user_id FROM materials WHERE user_id = $user_id LIMIT 5");

if (!$result) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

$materials = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode(['success' => true, 'user_id' => $user_id, 'materials' => $materials]);
$conn->close();
?>
