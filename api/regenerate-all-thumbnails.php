<?php
// Force regenerate all thumbnails with new 300x400 dimensions

require_once '../db_config.php';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get all materials
$result = $conn->query("SELECT id, file_path FROM materials WHERE file_path LIKE '%.pdf'");

if (!$result) {
    die(json_encode(['error' => 'Query failed: ' . $conn->error]));
}

$successCount = 0;
$failCount = 0;
$gsPath = 'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe';

if (!file_exists($gsPath)) {
    die(json_encode(['error' => 'Ghostscript not found']));
}

$uploadsDir = dirname(__DIR__) . '/uploads';
$thumbDir = $uploadsDir . '/thumbnails';

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $filePath = $row['file_path'];
    
    // Construct full path - file_path already includes 'uploads/' prefix
    $fullPath = dirname(__DIR__) . '/' . $filePath;
    
    if (!file_exists($fullPath)) {
        echo "Skipping $id: File not found<br>";
        $failCount++;
        continue;
    }
    
    $thumbPath = $thumbDir . '/' . $id . '_page_1.jpg';
    
    // Force delete existing thumbnail
    if (file_exists($thumbPath)) {
        unlink($thumbPath);
    }
    
    // Generate new thumbnail with 300x400 dimensions
    $fullPath = str_replace('/', '\\', $fullPath);
    $cmd = escapeshellarg($gsPath) . ' -q -dBATCH -dNOPAUSE -dFirstPage=1 -dLastPage=1 -sDEVICE=jpeg -dJPEGQ=85 -r150 -g300x400 -sOutputFile=' . escapeshellarg($thumbPath) . ' ' . escapeshellarg($fullPath) . ' 2>&1';
    
    $output = shell_exec($cmd);
    
    if (file_exists($thumbPath) && filesize($thumbPath) > 1000) {
        echo "✓ Regenerated thumbnail for ID $id<br>";
        $successCount++;
    } else {
        echo "✗ Failed to regenerate thumbnail for ID $id<br>";
        $failCount++;
    }
}

$result->close();
$conn->close();

echo "<br>Summary: $successCount successful, $failCount failed<br>";
?>
