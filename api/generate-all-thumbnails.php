<?php
require_once __DIR__ . '/../db_config.php';

echo "=== Bulk Thumbnail Generation ===\n\n";

// Get all materials that don't have thumbnails
$result = $conn->query('SELECT id, file_path, file_type FROM materials WHERE file_type LIKE "%pdf%" ORDER BY id DESC');

if ($result->num_rows === 0) {
    echo "No PDF materials found\n";
    exit;
}

$thumbDir = dirname(__DIR__) . '/uploads/thumbnails';
if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

$magickPath = 'C:\\Program Files (x86)\\ImageMagick-7.1.2-Q16-HDRI\\magick.exe';
$gsPath = 'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe';
$count = 0;
$success = 0;
$failed = 0;

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $filePath = $row['file_path'];
    $fullPath = dirname(__DIR__) . '/' . $filePath;
    $thumbPath = $thumbDir . '/' . $id . '_page_1.jpg';
    
    if (!file_exists($fullPath)) {
        echo "[$id] SKIP: File not found at $filePath\n";
        continue;
    }
    
    if (file_exists($thumbPath)) {
        echo "[$id] SKIP: Thumbnail already exists\n";
        continue;
    }
    
    $count++;
    
    // Use Ghostscript for PDF to JPEG conversion (more reliable than ImageMagick)
    if (file_exists($gsPath)) {
        $cmd = escapeshellarg($gsPath) . ' -q -dBATCH -dNOPAUSE -dFirstPage=1 -dLastPage=1 -sDEVICE=jpeg -dJPEGQ=85 -r150 -g400x300 -sOutputFile=' . escapeshellarg($thumbPath) . ' ' . escapeshellarg($fullPath) . ' 2>&1';
        $output = shell_exec($cmd);
        
        if (file_exists($thumbPath) && filesize($thumbPath) > 1000) {
            echo "[$id] SUCCESS: Thumbnail generated (" . filesize($thumbPath) . " bytes)\n";
            $success++;
        } else {
            echo "[$id] FAILED: " . trim($output) . "\n";
            $failed++;
        }
    } else {
        echo "Ghostscript not found at $gsPath\n";
        break;
    }
}

echo "\n=== Summary ===\n";
echo "Total processed: $count\n";
echo "Successful: $success\n";
echo "Failed: $failed\n";

$conn->close();
?>
