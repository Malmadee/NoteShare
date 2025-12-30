<?php
require_once '../db_config.php';
$conn = new mysqli($host, $user, $password, $database);
$result = $conn->query("SELECT id, file_path FROM materials LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Path: " . $row['file_path'] . "\n";
}
?>
