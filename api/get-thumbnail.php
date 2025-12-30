<?php
// Serve pre-generated PDF thumbnails
header('Content-Type: image/jpeg');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    exit;
}

$thumbDir = dirname(__DIR__) . '/uploads/thumbnails';
$thumbFile = $thumbDir . '/' . $id . '_page_1.jpg';

// If thumbnail exists, serve it
if (file_exists($thumbFile) && filesize($thumbFile) > 0) {
    header('Content-Length: ' . filesize($thumbFile));
    readfile($thumbFile);
    exit;
}

// If thumbnail doesn't exist, create a placeholder using GD
if (function_exists('imagecreatetruecolor')) {
    $image = imagecreatetruecolor(400, 300);
    
    // Green color (#80c157)
    $greenColor = imagecolorallocate($image, 128, 193, 87);
    
    // Fill image with green
    imagefill($image, 0, 0, $greenColor);
    
    // Add text
    $whiteColor = imagecolorallocate($image, 255, 255, 255);
    imagestring($image, 5, 140, 130, 'PDF Document', $whiteColor);
    
    // Output image
    imagejpeg($image, null, 85);
    imagedestroy($image);
} else {
    // Fallback: return error if GD not available
    http_response_code(500);
    echo 'Image library not available';
}
?>
