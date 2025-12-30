<?php
// Quick test to verify API and DB setup
header('Content-Type: application/json; charset=utf-8');

// Include config
require_once __DIR__ . '/../db_config.php';

// Test DB connection
if (!isset($conn) || !$conn) {
    echo json_encode(['error' => 'DB connection failed', 'details' => $GLOBALS['conn_error'] ?? 'Unknown']);
    exit;
}

// Test 1: Check materials table exists and has data
$test1 = $conn->query("SELECT COUNT(*) as cnt FROM materials");
$materials_count = 0;
if ($test1) {
    $row = $test1->fetch_assoc();
    $materials_count = $row['cnt'] ?? 0;
}

// Test 2: Check categories table
$test2 = $conn->query("SELECT * FROM upload_categories");
$categories = [];
if ($test2) {
    while ($row = $test2->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Test 3: Check users table
$test3 = $conn->query("SELECT COUNT(*) as cnt FROM users");
$users_count = 0;
if ($test3) {
    $row = $test3->fetch_assoc();
    $users_count = $row['cnt'] ?? 0;
}

// Test 4: Show table structure of materials
$test4 = $conn->query("DESCRIBE materials");
$columns = [];
if ($test4) {
    while ($row = $test4->fetch_assoc()) {
        $columns[] = $row;
    }
}

// Test 5: Try get-uploads API
$get_uploads = json_decode(file_get_contents('get-uploads.php'), true);

echo json_encode([
    'db_connection' => 'OK',
    'materials_count' => $materials_count,
    'categories' => $categories,
    'users_count' => $users_count,
    'materials_columns' => $columns,
    'api_response_sample' => $get_uploads
]);

$conn->close();
?>
