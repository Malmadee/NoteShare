<?php
require_once 'db_config.php';

if (isset($db_error)) {
    echo "Error: $db_error\n";
    exit;
}

// Get all table names
$result = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'noteshare_db'");
$tables = [];
while ($row = $result->fetch_assoc()) {
    $tables[] = $row['TABLE_NAME'];
}

echo "=== NoteShare Database Schema ===\n\n";

foreach ($tables as $table) {
    echo "Table: $table\n";
    echo "Columns:\n";
    $cols = $conn->query("DESCRIBE $table");
    while ($col = $cols->fetch_assoc()) {
        echo "  - {$col['Field']} ({$col['Type']}) " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . ($col['Key'] === 'PRI' ? ' PRIMARY KEY' : '') . ($col['Key'] === 'UNI' ? ' UNIQUE' : '') . ($col['Key'] === 'MUL' ? ' FOREIGN KEY' : '') . "\n";
    }
    echo "\n";
}

$conn->close();
?>
