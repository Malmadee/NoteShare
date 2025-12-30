<?php
/**
 * Generate thumbnail for a single material by ID
 * Called automatically after upload by handle-upload.php
 * Uses Ghostscript to convert PDF first page to JPEG
 */

require_once __DIR__ . '/../db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing id']);
    exit;
}

$id = (int)$_GET['id'];

if (!isset($conn)) {
    echo json_encode(['error' => 'DB connection not available']);
    exit;
}

// Get material by ID
$stmt = $conn->prepare('SELECT id, file_path, file_type FROM materials WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    echo json_encode(['error' => 'Material not found']);
    exit;
}

$row = $res->fetch_assoc();
$file = $row['file_path'];
$fileType = $row['file_type'];

// Only process PDFs
if (stripos($fileType, 'pdf') === false) {
    echo json_encode(['message' => 'Skipped: not a PDF']);
    exit;
}

$uploadsDir = dirname(__DIR__) . '/uploads';
$thumbDir = $uploadsDir . '/thumbnails';
if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

$localFile = dirname(__DIR__) . '/' . ltrim($file, '/');
$localFile = str_replace('/', '\\', $localFile);

// Check if file exists
if (!file_exists($localFile)) {
    echo json_encode(['error' => 'File not found: ' . $localFile]);
    exit;
}

$thumbPath = $thumbDir . '/' . $id . '_page_1.jpg';

// Skip if thumbnail already exists and is valid
if (file_exists($thumbPath) && filesize($thumbPath) > 1000) {
    echo json_encode(['message' => 'Thumbnail already exists']);
    exit;
}

// Use Ghostscript to generate thumbnail
$gsPath = 'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe';

if (!file_exists($gsPath)) {
    echo json_encode(['error' => 'Ghostscript not found at: ' . $gsPath]);
    exit;
}

// Generate JPEG thumbnail from PDF first page
$cmd = escapeshellarg($gsPath) . ' -q -dBATCH -dNOPAUSE -dFirstPage=1 -dLastPage=1 -sDEVICE=jpeg -dJPEGQ=85 -r150 -g300x400 -sOutputFile=' . escapeshellarg($thumbPath) . ' ' . escapeshellarg($localFile) . ' 2>&1';

$output = shell_exec($cmd);

// Check if generation was successful
if (file_exists($thumbPath) && filesize($thumbPath) > 1000) {
    // Update database with thumbnail path
    $thumbWebPath = 'uploads/thumbnails/' . $id . '_page_1.jpg';
    $uStmt = $conn->prepare('UPDATE materials SET thumbnail_path = ? WHERE id = ?');
    $uStmt->bind_param('si', $thumbWebPath, $id);
    $uStmt->execute();
    $uStmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Thumbnail generated successfully',
        'id' => $id,
        'size' => filesize($thumbPath),
        'path' => $thumbWebPath
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate thumbnail',
        'error' => trim($output),
        'id' => $id
    ]);
}

$stmt->close();
$conn->close();
?>
