<?php
require_once __DIR__ . '/../db_config.php';

echo "=== Thumbnail Check ===\n\n";

// Get all materials
$result = $conn->query('SELECT id, title, file_path, file_type FROM materials ORDER BY id DESC LIMIT 10');

if ($result->num_rows === 0) {
    echo "No materials found\n";
    exit;
}

echo "Materials in database:\n";
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $title = $row['title'];
    $filePath = $row['file_path'];
    $fileType = $row['file_type'];
    
    $fullPath = dirname(__DIR__) . '/' . $filePath;
    $fileExists = file_exists($fullPath);
    $thumbPath = dirname(__DIR__) . '/uploads/thumbnails/' . $id . '_page_1.jpg';
    $thumbExists = file_exists($thumbPath);
    
    echo "\nID: $id\n";
    echo "Title: $title\n";
    echo "File Path: $filePath\n";
    echo "File Type: $fileType\n";
    echo "File Exists: " . ($fileExists ? "YES" : "NO") . "\n";
    echo "Thumbnail Exists: " . ($thumbExists ? "YES" : "NO") . "\n";
    
    // Attempt to generate thumbnail if missing
    if ($fileExists && !$thumbExists && strpos($fileType, 'pdf') !== false) {
        echo "Generating thumbnail...\n";
        
        $gspath = 'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe';
        $magickPath = 'C:\\Program Files (x86)\\ImageMagick-7.1.2-Q16-HDRI\\magick.exe';
        $thumbDir = dirname(__DIR__) . '/uploads/thumbnails';
        
        if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);
        
        if (file_exists($magickPath)) {
            $cmd = escapeshellarg($magickPath) . ' convert ' . escapeshellarg($fullPath . '[0]') . ' -resize 400x300 ' . escapeshellarg($thumbPath) . ' 2>&1';
            $output = shell_exec($cmd);
            
            if (file_exists($thumbPath)) {
                echo "SUCCESS: Thumbnail generated\n";
            } else {
                echo "FAILED: " . trim($output) . "\n";
            }
        } else {
            echo "ImageMagick not found at: $magickPath\n";
        }
    }
}

$conn->close();
?>
