<?php
require_once '../db_config.php';

if (isset($db_error)) {
    die("DB Connection Error: " . $db_error);
}

echo "Database connection: OK<br>";
echo "Database: noteshare_db<br>";

// Test 1: Count materials
$count_result = $conn->query("SELECT COUNT(*) as cnt FROM materials");
if (!$count_result) {
    die("Query failed: " . $conn->error);
}
$row = $count_result->fetch_assoc();
echo "Total materials in DB: " . $row['cnt'] . "<br><br>";

// Test 2: List all materials
$list_result = $conn->query("SELECT id, title, user_id FROM materials LIMIT 5");
if (!$list_result) {
    die("Query failed: " . $conn->error);
}
echo "Sample materials:<br>";
while ($material = $list_result->fetch_assoc()) {
    echo "  - ID: " . $material['id'] . ", Title: " . $material['title'] . ", User: " . $material['user_id'] . "<br>";
}

// Test 3: Try a simple DELETE
$test_id = 999; // Non-existent ID
$test_stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
$test_stmt->bind_param('i', $test_id);
$test_stmt->execute();
echo "<br>Test DELETE query executed successfully<br>";
echo "Affected rows: " . $test_stmt->affected_rows . "<br>";
$test_stmt->close();

$conn->close();
echo "<br>All tests passed!";
?>
