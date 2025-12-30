<?php
require_once 'db_config.php';

// Check upload_categories structure
echo "=== upload_categories TABLE STRUCTURE ===\n";
$result = $conn->query("DESCRIBE upload_categories");
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n=== upload_categories DATA ===\n";
$result = $conn->query("SELECT * FROM upload_categories");
if ($result) {
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
