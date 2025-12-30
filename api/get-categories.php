<?php
session_start();
header('Content-Type: application/json');

// Include database config
require_once '../db_config.php';

try {
  $stmt = $conn->prepare("SELECT id, name FROM upload_categories ORDER BY name ASC");
  $stmt->execute();
  $result = $stmt->get_result();
  
  $categories = [];
  while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
  }
  
  $stmt->close();
  
  http_response_code(200);
  echo json_encode([
    'success' => true,
    'categories' => $categories
  ]);
  
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
