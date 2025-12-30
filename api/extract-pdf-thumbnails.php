<?php
// Extract first page thumbnails from PDFs without DB dependency
header('Content-Type: text/plain');

$uploadsDir = dirname(__DIR__) . '/uploads';
$thumbDir = $uploadsDir . '/thumbnails';
if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

echo "Scanning for PDFs...\n\n";

// Find all PDFs recursively
$pdfFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir));
foreach ($iterator as $file) {
    if (strtolower($file->getExtension()) === 'pdf') {
        $pdfFiles[] = $file->getPathname();
    }
}

echo "Found " . count($pdfFiles) . " PDFs\n\n";

$processed = 0;
foreach ($pdfFiles as $filePath) {
    $fileName = basename($filePath);
    $thumbName = basename($filePath, '.pdf');
    $thumbFile = $thumbDir . '/' . $thumbName . '_page_1.jpg';
    
    echo "Processing: $fileName\n";
    
    // Skip if already has thumbnail
    if (file_exists($thumbFile)) {
        echo "  Already has thumbnail\n";
        continue;
    }
    
    // Try Imagick first
    if (extension_loaded('imagick')) {
        try {
            $imagick = new Imagick($filePath . '[0]');
            $imagick->setImageFormat('jpg');
            $imagick->scaleImage(400, 0);
            $imagick->writeImage($thumbFile);
            $imagick->destroy();
            
            echo "  Created with Imagick\n";
            $processed++;
            continue;
        } catch (Exception $e) {
            echo "  Imagick failed: " . $e->getMessage() . "\n";
        }
    }
    
    // Try pdftoppm
    $cmd = 'pdftoppm -singlefile -jpeg -f 1 -l 1 -scale-to 400 ' . 
           escapeshellarg($filePath) . ' ' . escapeshellarg($thumbDir . '/' . $thumbName . '_page_1');
    
    @exec($cmd, $output, $code);
    if ($code === 0 && file_exists($thumbFile)) {
        echo "  Created with pdftoppm\n";
        $processed++;
        continue;
    }
    
    // Try GhostScript (gs)
    $gsCmd = 'gs -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=jpeg -r150 -dTextAlphaBits=4 ' .
             '-sOutputFile=' . escapeshellarg($thumbFile) . ' -dFirstPage=1 -dLastPage=1 ' .
             escapeshellarg($filePath);
    
    @exec($gsCmd, $output, $code);
    if ($code === 0 && file_exists($thumbFile)) {
        echo "  Created with GhostScript\n";
        $processed++;
        continue;
    }
    
    echo "  FAILED: No PDF extraction tools available\n";
}

echo "\nProcessed: $processed thumbnails\n";
echo "Now update database by visiting: http://localhost/NoteShare/api/update-thumbnail-db.php\n";
?>
