<?php
// Create real red JPEG thumbnails using GD or fallback
header('Content-Type: text/plain');

$thumbDir = dirname(__DIR__) . '/uploads/thumbnails';
if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

$materials = [8, 9, 10, 11];

// Try to create with GD library
if (function_exists('imagecreatetruecolor')) {
    foreach ($materials as $id) {
        $image = imagecreatetruecolor(400, 300);
        $red = imagecolorallocate($image, 220, 50, 50);
        imagefill($image, 0, 0, $red);
        $thumbFile = $thumbDir . '/' . $id . '_page_1.jpg';
        imagejpeg($image, $thumbFile, 85);
        imagedestroy($image);
        echo "Created (GD): $id\n";
    }
} else {
    // Fallback: create minimal PPM, convert to JPEG
    foreach ($materials as $id) {
        // Create a simple red PPM file
        $ppm = "P6\n400 300\n255\n";
        for ($i = 0; $i < 400 * 300; $i++) {
            $ppm .= chr(220) . chr(50) . chr(50);
        }
        
        $thumbFile = $thumbDir . '/' . $id . '_page_1.jpg';
        
        // Try to use ImageMagick convert
        $ppmFile = $thumbDir . '/' . $id . '_temp.ppm';
        file_put_contents($ppmFile, $ppm);
        
        @exec('convert ' . escapeshellarg($ppmFile) . ' ' . escapeshellarg($thumbFile), $output, $code);
        if ($code === 0) {
            unlink($ppmFile);
            echo "Created (convert): $id\n";
        } else {
            // If convert fails, at least save PPM as fallback
            echo "Created (PPM): $id\n";
        }
    }
}

echo "Done!\n";
?>
