<?php
// api/checkout-with-wallet.php
// Process purchase using wallet balance
// Deducts coins from wallet and creates purchase record

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$user_id = $_SESSION['user_id'];
$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
$total = isset($data['total']) ? (float)$data['total'] : 0.0;

if (count($items) === 0 || $total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid items or total']);
    exit;
}

try {
    // Ensure wallet_balance column exists
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS wallet_balance INT DEFAULT 200");
    
    // Start transaction
    $conn->begin_transaction();
    
    // Get current wallet balance
    $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $row = $result->fetch_assoc();
    $current_balance = (int)$row['wallet_balance'];
    $stmt->close();
    
    // Check if balance is sufficient
    if ($current_balance < $total) {
        throw new Exception('Insufficient wallet balance.');
    }
    
    // Deduct from wallet
    $new_balance = $current_balance - $total;
    $updateStmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $updateStmt->bind_param('ii', $new_balance, $user_id);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Create purchase record
    $purchaseStmt = $conn->prepare("INSERT INTO purchases (user_id, total_amount, created_at) VALUES (?, ?, NOW())");
    $purchaseStmt->bind_param('id', $user_id, $total);
    $purchaseStmt->execute();
    $purchase_id = $purchaseStmt->insert_id;
    $purchaseStmt->close();
    
    // Insert purchase items and update materials purchases_count
    $itemStmt = $conn->prepare("INSERT INTO purchase_items (purchase_id, material_id, price, qty) VALUES (?, ?, ?, ?)");
    $incStmt = $conn->prepare("UPDATE materials SET purchases_count = purchases_count + ? WHERE id = ?");
    
    foreach ($items as $item) {
        $mid = isset($item['id']) ? (int)$item['id'] : 0;
        $price = isset($item['price']) ? (float)$item['price'] : 0.0;
        $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
        
        if ($mid <= 0) continue;
        
        $itemStmt->bind_param('iidi', $purchase_id, $mid, $price, $qty);
        $itemStmt->execute();
        
        $incStmt->bind_param('ii', $qty, $mid);
        $incStmt->execute();
    }
    $itemStmt->close();
    $incStmt->close();
    
    // Clear cart for this user
    $clearStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clearStmt->bind_param('i', $user_id);
    $clearStmt->execute();
    $clearStmt->close();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Purchase has been made.',
        'purchase_id' => $purchase_id,
        'new_balance' => $new_balance
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
