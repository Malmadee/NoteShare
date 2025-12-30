<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_config.php';

// Get material ID from POST request
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? (int)$data['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if (!$id) {
    echo json_encode(['error' => 'Material ID is required']);
    exit;
}

// Increment views count
$sql = "UPDATE materials SET views = views + 1 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'View count incremented']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to increment view count']);
}

$stmt->close();
$conn->close();
?>
