<?php
require_once 'db_config.php';

// Get the most recent material
$result = $conn->query("SELECT id, file_path, file_type FROM materials ORDER BY id DESC LIMIT 1");
$row = $result->fetch_assoc();

echo "ID: " . $row['id'] . "\n";
echo "File: " . $row['file_path'] . "\n";
echo "Type: " . $row['file_type'] . "\n";

$thumbPath = "uploads/thumbnails/" . $row['id'] . "_page_1.jpg";
$fullThumbPath = __DIR__ . "/" . $thumbPath;
echo "Thumbnail path: " . $thumbPath . "\n";
echo "Exists: " . (file_exists($fullThumbPath) ? "YES (" . filesize($fullThumbPath) . " bytes)" : "NO") . "\n";
?>
