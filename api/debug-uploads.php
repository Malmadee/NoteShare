<?php
/**
 * Test script to verify uploads are being inserted correctly
 */

session_start();
$_SESSION['user_id'] = 1; // Test with user ID 1

require_once 'db_config.php';

echo "=== DATABASE DEBUG INFO ===\n\n";

// Check if materials table has any data
$result = $conn->query("SELECT COUNT(*) as total FROM materials");
$row = $result->fetch_assoc();
echo "Total materials in database: " . $row['total'] . "\n\n";

// Show latest materials
echo "=== LATEST 5 MATERIALS ===\n";
$result = $conn->query("
  SELECT m.id, m.user_id, m.title, m.file_path
  FROM materials m 
  ORDER BY m.id DESC 
  LIMIT 5
");

while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | User: " . $row['user_id'] . " | Title: " . $row['title'] . "\n";
    echo "  File: " . $row['file_path'] . "\n\n";
}

// Check uploads for current user (ID 1)
echo "=== UPLOADS FOR USER ID 1 ===\n";
$user_id = 1;
$stmt = $conn->prepare("
  SELECT m.id, m.title, m.price, m.file_path
  FROM materials m
  WHERE m.user_id = ?
  ORDER BY m.id DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "ID: " . $row['id'] . " | Title: " . $row['title'] . " | Price: " . $row['price'] . "\n";
}

echo "Total uploads for user 1: " . $count . "\n\n";

// Check if materials table has required columns
echo "=== TABLE STRUCTURE ===\n";
$result = $conn->query("DESCRIBE materials");
echo "Materials table columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

$conn->close();
?>
