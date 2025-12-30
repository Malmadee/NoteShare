<?php
/**
 * Asynchronous Thumbnail Generation
 * 
 * This script generates a thumbnail for a specific material by ID.
 * Can be called via AJAX without blocking the main request.
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in (optional - remove if you want to allow unauthenticated requests)
// if (!isset($_SESSION['user_id'])) {
//   http_response_code(401);
//   echo json_encode(['success' => false, 'message' => 'User not logged in']);
//   exit;
// }

require_once '../db_config.php';

$materialId = intval($_POST['material_id'] ?? $_GET['material_id'] ?? 0);

if (!$materialId) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Material ID is required']);
  exit;
}

try {
  // Get material details
  $stmt = $conn->prepare("SELECT file_path, file_type FROM materials WHERE id = ?");
  $stmt->bind_param('i', $materialId);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Material not found']);
    $stmt->close();
    exit;
  }
  
  $row = $result->fetch_assoc();
  $filePath = $row['file_path'];
  $fileType = $row['file_type'];
  $stmt->close();
  
  // Only generate thumbnails for PDFs
  if (stripos($fileType, 'pdf') === false) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Thumbnail generation skipped (not a PDF)']);
    exit;
  }
  
  // Generate thumbnail
  $fullPath = '../' . $filePath;
  
  if (!file_exists($fullPath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
  }
  
  // Create thumbnails directory
  $thumbDir = dirname(__DIR__) . '/uploads/thumbnails';
  if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
  }
  
  $thumbPath = $thumbDir . '/' . $materialId . '_page_1.jpg';
  
  // Skip if thumbnail already exists and is valid
  if (file_exists($thumbPath) && filesize($thumbPath) > 1000) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Thumbnail already exists']);
    exit;
  }
  
  // Use Ghostscript to generate thumbnail
  $gsPath = 'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe';
  
  if (!file_exists($gsPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ghostscript not found on server']);
    exit;
  }
  
  // Convert the PDF to JPEG
  $fullPath = str_replace('/', '\\', $fullPath);
  $cmd = escapeshellarg($gsPath) . ' -q -dBATCH -dNOPAUSE -dFirstPage=1 -dLastPage=1 -sDEVICE=jpeg -dJPEGQ=85 -r150 -g300x400 -sOutputFile=' . escapeshellarg($thumbPath) . ' ' . escapeshellarg($fullPath) . ' 2>&1';
  
  $output = shell_exec($cmd);
  
  // Check if successful
  if (file_exists($thumbPath) && filesize($thumbPath) > 1000) {
    // Update database with thumbnail path
    $thumbWebPath = 'uploads/thumbnails/' . $materialId . '_page_1.jpg';
    $updateStmt = $conn->prepare('UPDATE materials SET thumbnail_path = ? WHERE id = ?');
    $updateStmt->bind_param('si', $thumbWebPath, $materialId);
    $updateStmt->execute();
    $updateStmt->close();
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Thumbnail generated successfully']);
  } else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate thumbnail', 'debug_output' => $output]);
  }
  
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
