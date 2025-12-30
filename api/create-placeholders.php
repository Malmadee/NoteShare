<?php
// Create simple colored placeholder thumbnails
require_once __DIR__ . '/../db_config.php';
header('Content-Type: text/plain');

$thumbDir = dirname(__DIR__) . '/uploads/thumbnails';
if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

// Create a simple colored image function
function createColoredJPEG($width, $height, $r, $g, $b) {
    // Create image using built-in PHP functions if available, otherwise return binary data
    if (function_exists('imagecreatetruecolor')) {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, $color);
        ob_start();
        imagejpeg($image, null, 85);
        $data = ob_get_clean();
        imagedestroy($image);
        return $data;
    } else {
        // Fallback: create a minimal PPM file and convert to JPEG using command line
        // For now, just return a placeholder binary that browsers will handle
        return file_get_contents('https://via.placeholder.com/' . $width . 'x' . $height . '/' . sprintf('%02X%02X%02X', $r, $g, $b));
    }
}

$materials = [
    8 => ['title' => 'Performance Evaluation', 'r' => 220, 'g' => 50, 'b' => 50],    // Red
    9 => ['title' => 'Induction', 'r' => 220, 'g' => 50, 'b' => 50],                // Red
    10 => ['title' => 'Hiring', 'r' => 220, 'g' => 50, 'b' => 50],                 // Red
    11 => ['title' => 'Selection', 'r' => 220, 'g' => 50, 'b' => 50]               // Red
];

echo "Creating thumbnails...\n";
$created = 0;

foreach ($materials as $id => $data) {
    $thumbFile = $thumbDir . '/' . $id . '_page_1.jpg';
    
    // Use GD if available
    if (function_exists('imagecreatetruecolor')) {
        $image = imagecreatetruecolor(400, 300);
        $color = imagecolorallocate($image, $data['r'], $data['g'], $data['b']);
        imagefill($image, 0, 0, $color);
        imagejpeg($image, $thumbFile, 85);
        imagedestroy($image);
        echo "Created: $id - " . $data['title'] . "\n";
        $created++;
    }
}

// Update database
if ($created > 0) {
    $result = $conn->query("SELECT id FROM materials WHERE id IN (8, 9, 10, 11)");
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $thumbPath = 'uploads/thumbnails/' . $id . '_page_1.jpg';
        $stmt = $conn->prepare('UPDATE materials SET thumbnail_path = ? WHERE id = ?');
        $stmt->bind_param('si', $thumbPath, $id);
        $stmt->execute();
        $stmt->close();
    }
    echo "\nDatabase updated!\n";
} else {
    echo "GD library not available - thumbnails not created\n";
}

$conn->close();
?>
