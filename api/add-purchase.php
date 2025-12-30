<?php
// add-purchase.php
// Accepts JSON POST { user_id: int (optional), items: [{id, price, qty}], total: number }
// Inserts a purchase and purchase_items rows, increments materials.purchases_count

require_once __DIR__ . '/../db_config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Use session user if available
$user_id = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    $user_id = (int)$_SESSION['user_id'];
} elseif (isset($data['user_id'])) {
    $user_id = (int)$data['user_id'];
}

$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
$total = isset($data['total']) ? (float)$data['total'] : 0.0;

if (!$user_id || count($items) === 0) {
    echo json_encode(['error' => 'Missing user_id or items']);
    exit;
}

if (!isset($conn)) {
    echo json_encode(['error' => 'DB connection not available']);
    exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare('INSERT INTO purchases (user_id, total_amount, created_at) VALUES (?, ?, NOW())');
    $stmt->bind_param('id', $user_id, $total);
    $stmt->execute();
    $purchase_id = $stmt->insert_id;
    $stmt->close();

    $piStmt = $conn->prepare('INSERT INTO purchase_items (purchase_id, material_id, price, qty) VALUES (?, ?, ?, ?)');
    $incStmt = $conn->prepare('UPDATE materials SET purchases_count = purchases_count + ? WHERE id = ?');
    foreach ($items as $it) {
        $mid = isset($it['id']) ? (int)$it['id'] : 0;
        $price = isset($it['price']) ? (float)$it['price'] : 0.0;
        $qty = isset($it['qty']) ? (int)$it['qty'] : 1;
        if ($mid <= 0) continue;
        $piStmt->bind_param('iidi', $purchase_id, $mid, $price, $qty);
        $piStmt->execute();
        // increment purchases_count by qty
        $incStmt->bind_param('ii', $qty, $mid);
        $incStmt->execute();
    }
    $piStmt->close();
    $incStmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'purchase_id' => $purchase_id]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}

$conn->close();
?>