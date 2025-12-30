<?php
require_once 'db_config.php';

if (isset($db_error)) {
    echo "DB Error: $db_error\n";
    exit;
}

echo "=== Materials by Category ===\n\n";

$result = $conn->query("
    SELECT c.name, COUNT(m.id) as cnt 
    FROM upload_categories c
    LEFT JOIN materials m ON m.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY c.id
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Category: {$row['name']} - Materials: {$row['cnt']}\n";
    }
} else {
    echo "No categories found\n";
}

$conn->close();
?>
