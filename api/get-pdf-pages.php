<?php
// get-pdf-pages.php - Get total page count from PDF file

require_once 'check-session.php';

// Get file path from query parameter
$file = isset($_GET['file']) ? $_GET['file'] : '';

if (empty($file)) {
    http_response_code(400);
    echo json_encode(['error' => 'File path not provided']);
    exit;
}

// Validate and sanitize file path
$file = str_replace('..', '', $file);
$file = str_replace('\\', '/', $file);

// Define valid upload directory
$uploadDir = realpath(dirname(__FILE__) . '/../uploads');
$filePath = realpath($uploadDir . '/' . $file);

// Verify file is within upload directory
if (!$filePath || strpos($filePath, $uploadDir) !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid file path']);
    exit;
}

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

// Check if it's a PDF
if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'pdf') {
    http_response_code(400);
    echo json_encode(['error' => 'File is not a PDF']);
    exit;
}

// Get page count from PDF
$pageCount = getPdfPageCount($filePath);

header('Content-Type: application/json');
echo json_encode(['pages' => $pageCount]);

/**
 * Get page count from PDF file
 */
function getPdfPageCount($filePath) {
    $pageCount = 1;
    
    try {
        // Method 1: Using ImageMagick identify command
        if (extension_loaded('imagick')) {
            try {
                $imagick = new Imagick($filePath);
                $pageCount = $imagick->getNumberImages();
                return $pageCount;
            } catch (Exception $e) {
                // Fall through to next method
            }
        }
        
        // Method 2: Parse PDF file directly
        $file = file_get_contents($filePath);
        
        // Look for /Pages and /Count in PDF
        if (preg_match('/\/Type\s*\/Pages\s*\/Kids\s*\[\s*(.*?)\s*\]\s*\/Count\s*(\d+)/s', $file, $matches)) {
            $pageCount = (int)$matches[2];
        } else {
            // Fallback: count /Page objects
            $pageCount = substr_count($file, '/Type /Page') + 1;
        }
        
        return max(1, $pageCount);
        
    } catch (Exception $e) {
        // Return 1 as default if we can't determine
        return 1;
    }
}
?>
