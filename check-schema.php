<?php
require 'db_config.php';

echo "=== Materials Table Structure ===\n";
$result = $conn->query('DESCRIBE materials');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== Checking for view_count column ===\n";
$check = $conn->query("SHOW COLUMNS FROM materials LIKE 'view_count'");
if($check->num_rows > 0) {
    echo "view_count column EXISTS\n";
} else {
    echo "view_count column DOES NOT EXIST\n";
}

echo "\n=== Checking for open_count column ===\n";
$check = $conn->query("SHOW COLUMNS FROM materials LIKE 'open_count'");
if($check->num_rows > 0) {
    echo "open_count column EXISTS\n";
} else {
    echo "open_count column DOES NOT EXIST\n";
}

echo "\n=== Checking for views column ===\n";
$check = $conn->query("SHOW COLUMNS FROM materials LIKE 'views'");
if($check->num_rows > 0) {
    echo "views column EXISTS\n";
} else {
    echo "views column DOES NOT EXIST\n";
}
?>
