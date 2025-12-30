<?php
// Proxy endpoint to serve PDF files with proper CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Content-Type: application/pdf');
header('Cache-Control: public, max-age=86400');

$filePath = isset($_GET['file']) ? $_GET['file'] : '';

// Security: only allow files from uploads directory
if (!$filePath || strpos($filePath, '..') !== false) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

$fullPath = dirname(__DIR__) . '/' . $filePath;

// Verify file exists and is in uploads
if (!file_exists($fullPath) || strpos(realpath($fullPath), realpath(dirname(__DIR__) . '/uploads')) !== 0) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Stream the file
readfile($fullPath);
?>
