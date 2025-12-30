<?php
/**
 * Quick test to verify get-user-uploads.php works
 */

session_start();
$_SESSION['user_id'] = 1;

require_once 'db_config.php';

echo "=== TESTING get-user-uploads.php ===\n\n";

// Check which timestamp column exists
$columnCheck = $conn->query("SHOW COLUMNS FROM materials LIKE 'created_at'");
$hasCreatedAt = $columnCheck && $columnCheck->num_rows > 0;

$columnCheck2 = $conn->query("SHOW COLUMNS FROM materials LIKE 'upload_timestamp'");
$hasUploadTimestamp = $columnCheck2 && $columnCheck2->num_rows > 0;

echo "Has created_at: " . ($hasCreatedAt ? "YES" : "NO") . "\n";
echo "Has upload_timestamp: " . ($hasUploadTimestamp ? "YES" : "NO") . "\n\n";

// Now run the actual query from get-user-uploads.php
$user_id = 1;

if ($hasCreatedAt) {
  $timestampColumn = 'm.created_at';
} elseif ($hasUploadTimestamp) {
  $timestampColumn = 'm.upload_timestamp';
} else {
  $timestampColumn = 'NOW()';
}

echo "Using timestamp column: " . $timestampColumn . "\n\n";

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
if ($stmt->execute()) {
  $result = $stmt->get_result();
  
  $uploads = [];
  while ($row = $result->fetch_assoc()) {
    $uploads[] = $row;
  }
  
  $stmt->close();
  
  echo "SUCCESS! Found " . count($uploads) . " uploads for user 1\n\n";
  
  foreach ($uploads as $upload) {
    echo "ID: " . $upload['id'] . " | Title: " . $upload['title'] . " | Category: " . $upload['category'] . " | Price: " . $upload['price'] . "\n";
  }
  
  echo "\n\nJSON Response:\n";
  echo json_encode([
    'success' => true,
    'uploads' => $uploads,
    'total_count' => count($uploads),
    'timestamp' => time()
  ], JSON_PRETTY_PRINT);
  
} else {
  echo "ERROR: " . $stmt->error . "\n";
}

$conn->close();
?>
