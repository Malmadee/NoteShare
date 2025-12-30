<?php
// api/test-get-popular.php - Debug script

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

// Check if table exists
$tables = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='noteshare_db'")->fetch_all(MYSQLI_ASSOC);
$tableNames = array_column($tables, 'TABLE_NAME');

echo json_encode([
    'tables' => $tableNames,
    'materials_exists' => in_array('materials', $tableNames),
    'purchase_items_exists' => in_array('purchase_items', $tableNames),
    'upload_categories_exists' => in_array('upload_categories', $tableNames)
]);

// Try to get materials count
if (in_array('materials', $tableNames)) {
    $result = $conn->query("SELECT COUNT(*) as count FROM materials");
    $row = $result->fetch_assoc();
    echo json_encode(['materials_count' => $row['count']]);
}

$conn->close();
?>
