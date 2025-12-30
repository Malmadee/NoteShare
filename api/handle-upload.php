<?php
// Set error handling to catch all errors and return as JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'PHP Error: ' . $errstr . ' in ' . basename($errfile) . ':' . $errline
    ]);
    exit;
});

session_start();
header('Content-Type: application/json');

/**
 * Generate thumbnail for a PDF material
 */
function generateThumbnailForId($materialId, $fullPath, $conn) {
    // Create thumbnails directory
    $thumbDir = dirname(__DIR__) . '/uploads/thumbnails';
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }
    
    $thumbPath = $thumbDir . '/' . $materialId . '_page_1.jpg';
    
    // Skip if thumbnail already exists
    if (file_exists($thumbPath) && filesize($thumbPath) > 1000) {
        return true;
    }
    
    // Use Ghostscript to generate thumbnail
    $gsPath = 'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe';
    
    if (!file_exists($gsPath)) {
        return false;
    }
    
    // Convert the uploaded PDF to JPEG
    $fullPath = str_replace('/', '\\', $fullPath);
    $cmd = escapeshellarg($gsPath) . ' -q -dBATCH -dNOPAUSE -dFirstPage=1 -dLastPage=1 -sDEVICE=jpeg -dJPEGQ=85 -r150 -g300x400 -sOutputFile=' . escapeshellarg($thumbPath) . ' ' . escapeshellarg($fullPath) . ' 2>&1';
    
    $output = shell_exec($cmd);
    
    // Check if successful
    if (file_exists($thumbPath) && filesize($thumbPath) > 1000) {
        // Update database with thumbnail path
        $thumbWebPath = 'uploads/thumbnails/' . $materialId . '_page_1.jpg';
        $stmt = $conn->prepare('UPDATE materials SET thumbnail_path = ? WHERE id = ?');
        $stmt->bind_param('si', $thumbWebPath, $materialId);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    
    return false;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'User not logged in']);
  exit;
}

// Include database config
require_once '../db_config.php';

// Check for database connection errors
if (isset($db_error)) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $db_error]);
  exit;
}

$user_id = $_SESSION['user_id'];
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$category = $_POST['category'] ?? ''; // User selection (will be overridden by auto-detection)
$price = $_POST['price'] ?? 0;

// Function to determine category based on file type and extension
function determineCategoryByFileType($mimeType, $filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Video files
    if (in_array($ext, ['mp4', 'avi', 'mov', 'webm', 'mkv', 'flv', 'wmv'])) {
        return 'Videos';
    }
    
    // PDF files (Notes and Exam Papers - we'll use Notes as default for PDFs)
    if ($ext === 'pdf' || $mimeType === 'application/pdf') {
        return 'Notes';
    }
    
    // Document files (Notes)
    if (in_array($ext, ['doc', 'docx', 'txt', 'rtf', 'ppt', 'pptx'])) {
        return 'Notes';
    }
    
    // Spreadsheet files (Others)
    if (in_array($ext, ['xls', 'xlsx', 'csv'])) {
        return 'Others';
    }
    
    // Image files (Others)
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
        return 'Others';
    }
    
    // Archive files (Others)
    if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
        return 'Others';
    }
    
    // Default to Notes
    return 'Notes';
}

// Validate inputs
$hasFile = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;
if (empty($title) || empty($description) || empty($category) || empty($price) || !$hasFile) {
  http_response_code(400);
  $missing = [];
  if (empty($title)) $missing[] = 'title';
  if (empty($description)) $missing[] = 'description';
  if (empty($category)) $missing[] = 'category';
  if (empty($price)) $missing[] = 'price';
  if (!$hasFile) {
    $fileErr = $_FILES['file']['error'] ?? 'not set';
    $fileErrMsg = [
      UPLOAD_ERR_OK => 'OK',
      UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
      UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
      UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
      UPLOAD_ERR_NO_FILE => 'No file uploaded',
      UPLOAD_ERR_NO_TMP_DIR => 'Temporary folder missing',
      UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
      UPLOAD_ERR_EXTENSION => 'PHP extension blocked the file'
    ];
    $missing[] = 'file (error: ' . ($fileErrMsg[$fileErr] ?? $fileErr) . ')';
  }
  echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)]);
  exit;
}

// Validate price
$price = floatval($price);
if ($price < 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Price cannot be negative']);
  exit;
}

// File upload handling
$file = $_FILES['file'];
// Comprehensive MIME type list for allowed file types
$allowed_types = [
  'application/pdf',
  // Video MIME types (many browsers/OS combinations)
  'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/x-m4v',
  'video/x-matroska', 'video/webm', 'video/x-flv', 'video/x-ms-wmv',
  // Word documents
  'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  // Excel spreadsheets
  'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  // PowerPoint presentations
  'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  // Images
  'image/jpeg', 'image/png', 'image/jpg'
];

$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['pdf', 'mp4', 'avi', 'mov', 'mkv', 'flv', 'wmv', 'webm', 'm4v',
                       'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                       'jpg', 'jpeg', 'png', 'gif', 'bmp'];

// Validate file type: check both MIME type and extension for maximum compatibility
$mime_valid = in_array($file['type'], $allowed_types);
$ext_valid = in_array($file_ext, $allowed_extensions);

if (!$mime_valid && !$ext_valid) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, MP4, AVI, MOV, MKV, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG']);
  exit;
}

// Validate file size (max 100MB)
$max_size = 100 * 1024 * 1024;
if ($file['size'] > $max_size) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'File size exceeds 100MB limit']);
  exit;
}

// Create uploads directory if it doesn't exist
$uploads_dir = '../uploads/' . $user_id;
if (!is_dir($uploads_dir)) {
  mkdir($uploads_dir, 0755, true);
}

// Generate unique filename
$file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('material_') . '.' . $file_ext;
$file_path = 'uploads/' . $user_id . '/' . $filename;
$full_path = '../' . $file_path;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $full_path)) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
  exit;
}

// Get category_id from category name
try {
    // Determine category: prefer user-selected category if provided and valid,
    // otherwise fall back to automatic detection based on file type.
    $user_category = trim($category);
    $auto_category = determineCategoryByFileType($file['type'], $file['name']);
  
    // Choose which category name to use for lookup
    $categoryNameToLookup = $auto_category;
    if (!empty($user_category)) {
      // If user provided category matches one of the expected names, prefer it
      // Normalize common variants (e.g., 'exam-papers' or 'Exam Papers')
      $normalized = $user_category;
      // If user sent a slug like 'exam-papers', convert to readable name
      if (strpos($user_category, '-') !== false) {
        $normalized = str_replace('-', ' ', $user_category);
      }
      // Title-case common words to match DB entries
      $normalized = ucwords(strtolower($normalized));
      // Use normalized value as candidate
      $candidate = $normalized;
      // If candidate is valid, prefer it; otherwise fall back to auto
      $check_stmt = $conn->prepare("SELECT id FROM upload_categories WHERE name = ?");
      $check_stmt->bind_param('s', $candidate);
      $check_stmt->execute();
      $check_res = $check_stmt->get_result();
      if ($check_res && $check_res->num_rows > 0) {
        $categoryNameToLookup = $candidate;
      }
      $check_stmt->close();
    }
  
    $cat_stmt = $conn->prepare("SELECT id FROM upload_categories WHERE name = ?");
    $cat_stmt->bind_param('s', $categoryNameToLookup);
  $cat_stmt->execute();
  $cat_result = $cat_stmt->get_result();
  
  if ($cat_result->num_rows === 0) {
    unlink($full_path);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid category']);
    exit;
  }
  
  $cat_row = $cat_result->fetch_assoc();
  $category_id = $cat_row['id'];
  $cat_stmt->close();
  
  // Insert into database with category_id
  $stmt = $conn->prepare("
    INSERT INTO materials (user_id, category_id, title, description, price, file_path, file_type, file_size)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ");
  
  $stmt->bind_param('iissdssi', $user_id, $category_id, $title, $description, $price, $file_path, $file['type'], $file['size']);
  
  if ($stmt->execute()) {
    http_response_code(201);
    $insertId = $conn->insert_id;
    
    // NOTE: Thumbnail generation is now deferred to avoid blocking the upload response
    // The thumbnail will be generated asynchronously via a background queue
    // This allows uploads to appear immediately in the UI
    
    echo json_encode([
      'success' => true,
      'message' => 'Material uploaded successfully',
      'material_id' => $insertId
    ]);
  } else {
    throw new Exception($stmt->error);
  }
  
  $stmt->close();
} catch (Exception $e) {
  // Delete uploaded file if database insert fails
  if (file_exists($full_path)) {
    unlink($full_path);
  }
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
