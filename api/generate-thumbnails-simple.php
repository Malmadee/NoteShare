<?php
// generate-thumbnails-simple.php
// Generates simple colored placeholder thumbnails using GD library (built-in PHP)
require_once __DIR__ . '/../db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing id']);
    exit;
}
$id = (int)$_GET['id'];

// Get material info
$stmt = $conn->prepare('SELECT id, title, file_path, file_type FROM materials WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo json_encode(['error' => 'Not found']);
    exit;
}
$row = $res->fetch_assoc();
$title = $row['title'];
$fileType = $row['file_type'];

$uploadsDir = dirname(__DIR__) . '/uploads';
$thumbDir = $uploadsDir . '/thumbnails';
if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

$results = ['thumbnail' => null, 'generated' => []];

// Create a simple thumbnail using GD
$width = 400;
$height = 300;
$image = imagecreatetruecolor($width, $height);

// Determine color based on file type
if (stripos($fileType, 'pdf') !== false) {
    $bgColor = imagecolorallocate($image, 220, 50, 50);  // Red for PDF
    $textColor = imagecolorallocate($image, 255, 255, 255); // White text
    $label = 'PDF Document';
} elseif (stripos($fileType, 'video') !== false) {
    $bgColor = imagecolorallocate($image, 100, 50, 200);  // Purple for Video
    $textColor = imagecolorallocate($image, 255, 255, 255); // White text
    $label = 'Video';
} else {
    $bgColor = imagecolorallocate($image, 100, 150, 200);  // Blue for other
    $textColor = imagecolorallocate($image, 255, 255, 255); // White text
    $label = 'Document';
}

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// Add text
$fontFile = __DIR__ . '/../assets/fonts/arial.ttf'; // Try to use Arial if available
$fontSize = 24;

// Draw label
if (file_exists($fontFile)) {
    imagettftext($image, $fontSize, 0, 50, 120, $textColor, $fontFile, $label);
    imagettftext($image, 16, 0, 50, 180, $textColor, $fontFile, substr($title, 0, 50));
} else {
    // Fallback to built-in font
    imagestring($image, 5, 50, 120, $label, $textColor);
    imagestring($image, 3, 50, 180, substr($title, 0, 50), $textColor);
}

// Save as JPEG
$outName = $thumbDir . '/' . $id . '_page_1.jpg';
imagejpeg($image, $outName, 85);
imagedestroy($image);

$results['thumbnail'] = 'uploads/thumbnails/' . $id . '_page_1.jpg';
$results['generated'][] = $outName;

// Update materials.thumbnail_path
if ($results['thumbnail']) {
    $thumbPath = $results['thumbnail'];
    $uStmt = $conn->prepare('UPDATE materials SET thumbnail_path = ? WHERE id = ?');
    $uStmt->bind_param('si', $thumbPath, $id);
    $uStmt->execute();
    $uStmt->close();
}

echo json_encode($results);

$stmt->close();
$conn->close();
?>
