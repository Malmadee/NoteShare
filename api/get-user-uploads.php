<?php
session_start();
header('Content-Type: application/json');
// Prevent caching of this API response
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'User not logged in']);
  exit;
}

// Include database config
require_once '../db_config.php';

$user_id = $_SESSION['user_id'];

try {
  // Check which timestamp column exists
  $columnCheck = $conn->query("SHOW COLUMNS FROM materials LIKE 'created_at'");
  $hasCreatedAt = $columnCheck && $columnCheck->num_rows > 0;
  
  $columnCheck2 = $conn->query("SHOW COLUMNS FROM materials LIKE 'upload_timestamp'");
  $hasUploadTimestamp = $columnCheck2 && $columnCheck2->num_rows > 0;
  
  // Determine which column to use for timestamps
  if ($hasCreatedAt) {
    $timestampColumn = 'm.created_at';
  } elseif ($hasUploadTimestamp) {
    $timestampColumn = 'm.upload_timestamp';
  } else {
    $timestampColumn = 'NOW()';
  }
  
  // Use category_id with LEFT JOIN to upload_categories if it exists
  $stmt = $conn->prepare("
    SELECT m.id, m.title, m.description, 
           COALESCE(uc.name, m.category, 'Others') as category, 
           m.price, m.file_path, 
           COALESCE(" . $timestampColumn . ", NOW()) as created_at
    FROM materials m
    LEFT JOIN upload_categories uc ON m.category_id = uc.id
    WHERE m.user_id = ?
    ORDER BY m.id DESC
  ");
  
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $uploads = [];
  while ($row = $result->fetch_assoc()) {
    $uploads[] = $row;
  }
  
  $stmt->close();
  
  http_response_code(200);
  echo json_encode([
    'success' => true,
    'uploads' => $uploads,
    'total_count' => count($uploads),
    'timestamp' => time()
  ]);
  
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
