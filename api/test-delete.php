<?php
// Direct database test - delete material with ID from GET parameter
require_once '../db_config.php';

if (isset($db_error)) {
    echo "DB Error: " . $db_error;
    exit;
}

$material_id = $_GET['id'] ?? 1;
$user_id = $_GET['user'] ?? 1;

echo "Attempting to delete material_id=$material_id for user_id=$user_id<br>";

// First, check if material exists
$check_stmt = $conn->prepare("SELECT id, title, user_id FROM materials WHERE id = ?");
$check_stmt->bind_param('i', $material_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$existing = $check_result->fetch_assoc();
$check_stmt->close();

echo "Before delete: ";
if ($existing) {
    echo "Material found: " . json_encode($existing) . "<br>";
} else {
    echo "Material NOT found<br>";
}

// Try to delete
$del_stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
$del_stmt->bind_param('i', $material_id);
$delete_result = $del_stmt->execute();
$affected = $del_stmt->affected_rows;
$del_stmt->close();

echo "Delete executed: " . ($delete_result ? "YES" : "NO") . "<br>";
echo "Affected rows: $affected<br>";

// Check if still exists
$check_stmt = $conn->prepare("SELECT id, title FROM materials WHERE id = ?");
$check_stmt->bind_param('i', $material_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$after = $check_result->fetch_assoc();
$check_stmt->close();

echo "After delete: ";
if ($after) {
    echo "Material STILL EXISTS - DELETE FAILED!<br>";
} else {
    echo "Material deleted successfully<br>";
}

// List all materials
$all_stmt = $conn->query("SELECT id, title, user_id FROM materials LIMIT 10");
$all = $all_stmt->fetch_all(MYSQLI_ASSOC);
echo "<br>All remaining materials:<br>";
echo json_encode($all, JSON_PRETTY_PRINT);

$conn->close();
?>
