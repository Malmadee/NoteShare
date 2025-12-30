<?php
// api/delete-purchase.php
// Delete a purchase item for logged-in user

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$purchase_item_id = isset($_POST['purchase_item_id']) ? (int)$_POST['purchase_item_id'] : 0;

if ($purchase_item_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid purchase_item_id']);
    exit;
}

try {
    // Verify this purchase item belongs to the current user
    $verifyStmt = $conn->prepare("
        SELECT pi.id FROM purchase_items pi
        JOIN purchases p ON pi.purchase_id = p.id
        WHERE pi.id = ? AND p.user_id = ?
    ");
    $verifyStmt->bind_param('ii', $purchase_item_id, $user_id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $verifyStmt->close();
    
    // Delete the purchase item
    $deleteStmt = $conn->prepare("DELETE FROM purchase_items WHERE id = ?");
    $deleteStmt->bind_param('i', $purchase_item_id);
    $deleteStmt->execute();
    $deleteStmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Purchase deleted successfully.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
