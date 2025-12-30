<?php
require 'db_config.php';

echo "UPLOAD_CATEGORIES TABLE COLUMNS:\n";
$result = $conn->query('DESCRIBE upload_categories');
while($row = $result->fetch_assoc()) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nSAMPLE CATEGORIES:\n";
$result = $conn->query('SELECT * FROM upload_categories LIMIT 5');
while($row = $result->fetch_assoc()) {
    print_r($row);
}

$conn->close();
?>
