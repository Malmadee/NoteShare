<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../db_config.php';

$result = $conn->query("SELECT m.id, m.title, m.category_id, c.name, c.slug FROM materials m LEFT JOIN upload_categories c ON m.category_id = c.id ORDER BY m.id DESC LIMIT 10");
if ($result) {
    echo "Materials and their categories:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  ID: " . $row['id'] . " | Title: " . $row['title'] . " | Category ID: " . $row['category_id'] . " | Category Name: " . $row['name'] . " | Slug: " . $row['slug'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
