<?php
require_once __DIR__ . '/db_config.php';

$result = $conn->query('SELECT id, file_path, file_type FROM materials WHERE id IN (33, 34)');
while ($row = $result->fetch_assoc()) {
    $fullPath = __DIR__ . '/' . $row['file_path'];
    $exists = file_exists($fullPath) ? 'YES' : 'NO';
    $size = file_exists($fullPath) ? filesize($fullPath) : 0;
    echo "ID: {$row['id']}, File: {$row['file_path']}, Exists: $exists, Size: $size bytes" . PHP_EOL;
}
?>
