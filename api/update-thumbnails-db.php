<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../db_config.php';

// Update all materials to point to their thumbnail_page_1.jpg
$result = $conn->query("SELECT id FROM materials");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $thumbPath = 'uploads/thumbnails/' . $id . '_page_1.jpg';
        $stmt = $conn->prepare('UPDATE materials SET thumbnail_path = ? WHERE id = ?');
        $stmt->bind_param('si', $thumbPath, $id);
        $stmt->execute();
        echo "Updated material $id with thumbnail: $thumbPath\n";
        $stmt->close();
    }
}

echo "\nAll materials updated with thumbnail paths!\n";

$conn->close();
?>
