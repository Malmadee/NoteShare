<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../db_config.php';

$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "Users table columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
