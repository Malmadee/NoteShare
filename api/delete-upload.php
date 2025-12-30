<?php
session_start();
header('Content-Type: application/json');

// Log the request
error_log("DELETE: Request received");
error_log("DELETE: POST data: " . json_encode($_POST));
error_log("DELETE: Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  error_log('DELETE: No session user_id');
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'User not logged in']);
  exit;
}

// Include database config
require_once '../db_config.php';

// Check if database connection failed
if (isset($db_error)) {
  error_log("DELETE: Database connection error: $db_error");
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $db_error]);
  exit;
}

$user_id = intval($_SESSION['user_id']);
$material_id = intval($_POST['material_id'] ?? 0);

error_log("DELETE: user_id=$user_id, material_id=$material_id");

if (!$material_id) {
  error_log('DELETE: No material_id provided');
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Material ID required']);
  exit;
}

try {
  // Get file path before deleting
  $select_stmt = $conn->prepare("SELECT file_path FROM materials WHERE id = ? AND user_id = ?");
  if (!$select_stmt) {
    throw new Exception("Prepare SELECT failed: " . $conn->error);
  }
  
  $select_stmt->bind_param('ii', $material_id, $user_id);
  if (!$select_stmt->execute()) {
    throw new Exception("Execute SELECT failed: " . $select_stmt->error);
  }
  
  $result = $select_stmt->get_result();
  $row = $result->fetch_assoc();
  $select_stmt->close();
  
  if (!$row) {
    error_log("DELETE: Material not found: id=$material_id, user=$user_id");
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Material not found or unauthorized']);
    exit;
  }
  
  $file_path = $row['file_path'];
  error_log("DELETE: Found material with file_path=$file_path");
  
  // Now perform the DELETE
  $delete_stmt = $conn->prepare("DELETE FROM materials WHERE id = ? AND user_id = ?");
  if (!$delete_stmt) {
    throw new Exception("Prepare DELETE failed: " . $conn->error);
  }
  
  $delete_stmt->bind_param('ii', $material_id, $user_id);
  
  if (!$delete_stmt->execute()) {
    throw new Exception("Execute DELETE failed: " . $delete_stmt->error);
  }
  
  $affected = $delete_stmt->affected_rows;
  error_log("DELETE: Affected rows after DELETE statement: $affected");
  $delete_stmt->close();
  
  if ($affected <= 0) {
    throw new Exception("DELETE statement executed but no rows were affected");
  }
  
  // Delete the physical file
  $file_full_path = '../' . $file_path;
  if (file_exists($file_full_path)) {
    if (@unlink($file_full_path)) {
      error_log("DELETE: File successfully deleted: $file_full_path");
    } else {
      error_log("DELETE: Failed to delete file: $file_full_path");
    }
  } else {
    error_log("DELETE: File does not exist: $file_full_path");
  }
  
  http_response_code(200);
  echo json_encode([
    'success' => true, 
    'message' => 'Material deleted successfully',
    'id' => $material_id,
    'affected_rows' => $affected
  ]);
  
} catch (Exception $e) {
  error_log("DELETE: Exception caught: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// Close connection
if (isset($conn)) {
  $conn->close();
}
?>
