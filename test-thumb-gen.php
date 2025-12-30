<?php
require_once 'db_config.php';

$id = 35;

// Get material info
$result = $conn->query("SELECT file_path FROM materials WHERE id = $id");
if ($result->num_rows === 0) {
    echo "Material not found";
    exit;
}

$row = $result->fetch_assoc();
$fullPath = __DIR__ . "/" . $row['file_path'];

// Create thumbnails directory
$thumbDir = __DIR__ . '/uploads/thumbnails';
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

$thumbPath = $thumbDir . '/' . $id . '_page_1.jpg';

// Use Ghostscript
$gsPath = 'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe';

if (!file_exists($gsPath)) {
    echo "Ghostscript not found";
    exit;
}

$fullPath = str_replace('/', '\\', $fullPath);
$cmd = escapeshellarg($gsPath) . ' -q -dBATCH -dNOPAUSE -dFirstPage=1 -dLastPage=1 -sDEVICE=jpeg -dJPEGQ=85 -r150 -g400x300 -sOutputFile=' . escapeshellarg($thumbPath) . ' ' . escapeshellarg($fullPath) . ' 2>&1';

echo "Running: " . $cmd . "\n\n";
$output = shell_exec($cmd);

if ($output) {
    echo "Ghostscript output: " . $output . "\n";
}

if (file_exists($thumbPath)) {
    echo "Thumbnail created: " . $thumbPath . "\n";
    echo "Size: " . filesize($thumbPath) . " bytes\n";
} else {
    echo "Failed to create thumbnail\n";
}
?>
