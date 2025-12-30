<?php
require_once __DIR__ . '/../db_config.php';
header('Content-Type: text/plain');

$id = 8; // test with material 8

// Get material
$stmt = $conn->prepare('SELECT id, file_path, file_type, pages_count FROM materials WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo "Material not found\n";
    exit;
}
$row = $res->fetch_assoc();
echo "Material found:\n";
echo "  ID: " . $row['id'] . "\n";
echo "  File: " . $row['file_path'] . "\n";
echo "  Type: " . $row['file_type'] . "\n";
echo "  Pages: " . $row['pages_count'] . "\n";

$uploadsDir = dirname(__DIR__) . '/uploads';
$thumbDir = $uploadsDir . '/thumbnails';

echo "\nDirectory check:\n";
echo "  Uploads dir: $uploadsDir\n";
echo "  Exists: " . (is_dir($uploadsDir) ? 'YES' : 'NO') . "\n";
echo "  Thumb dir: $thumbDir\n";
echo "  Exists: " . (is_dir($thumbDir) ? 'YES' : 'NO') . "\n";

$file = $row['file_path'];
$localFile = dirname(__DIR__) . '/' . ltrim($file, '/');
// Normalize path separators for Windows
$localFile = str_replace('/', '\\', $localFile);

echo "\nFile check:\n";
echo "  Full path: $localFile\n";
echo "  Exists: " . (file_exists($localFile) ? 'YES' : 'NO') . "\n";

$thumbDir = dirname(__DIR__) . '/uploads/thumbnails';
$thumbDir = str_replace('/', '\\', $thumbDir);
echo "  Thumb dir: $thumbDir\n";

if (file_exists($localFile)) {
    echo "\nTrying ImageMagick convert...\n";
    $outName = $thumbDir . '\\' . $id . '_page_1.jpg';
    $cmd = "convert " . escapeshellarg($localFile . "[0]") . " -quality 85 " . escapeshellarg($outName) . " 2>&1";
    echo "Command: $cmd\n";
    exec($cmd, $output, $rc);
    echo "Return code: $rc\n";
    echo "Output:\n";
    print_r($output);
    
    echo "\nResult file: $outName\n";
    echo "Exists: " . (file_exists($outName) ? 'YES' : 'NO') . "\n";
    if (file_exists($outName)) {
        echo "Size: " . filesize($outName) . " bytes\n";
    }
}

$stmt->close();
$conn->close();
?>
