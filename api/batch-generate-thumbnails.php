<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../db_config.php';

// Get all materials
$result = $conn->query("SELECT id FROM materials");
$ids = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }
}

echo "Triggering thumbnail generation for " . count($ids) . " materials:\n\n";

foreach ($ids as $id) {
    echo "Processing material ID $id... ";
    $url = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/NoteShare/api/generate-thumbnails.php?id=' . $id;
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['thumbnail'])) {
            echo "✓ Success - " . $data['thumbnail'] . "\n";
        } else {
            echo "✗ Error: " . ($data['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "✗ Request failed\n";
    }
}

echo "\nThumbnail generation complete!\n";

$conn->close();
?>
