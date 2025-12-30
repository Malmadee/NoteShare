<?php
// api/get-user-purchases.php
// Get all purchases for logged-in user with details

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get purchase statistics - count total purchased files (sum of purchase_items.qty) and total amount spent
    $statsStmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(pi.qty), 0) as total_purchases,
            COALESCE(SUM(pi.price * pi.qty), 0) as total_spent
        FROM purchases p
        LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
        WHERE p.user_id = ?
    ");
    $statsStmt->bind_param('i', $user_id);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $statsRow = $statsResult->fetch_assoc();
    $statsStmt->close();

    $total_purchases = (int)$statsRow['total_purchases'];
    $total_spent = (float)$statsRow['total_spent'];
    
    // Get purchase items with material details
    $itemsStmt = $conn->prepare("
        SELECT 
            pi.id as purchase_item_id,
            p.id as purchase_id,
            p.created_at,
            m.id as material_id,
            m.title,
            m.file_path,
            m.file_size,
            c.name as category,
            pi.price
        FROM purchase_items pi
        JOIN purchases p ON pi.purchase_id = p.id
        JOIN materials m ON pi.material_id = m.id
        JOIN upload_categories c ON m.category_id = c.id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $itemsStmt->bind_param('i', $user_id);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
    $items = [];
    while ($row = $itemsResult->fetch_assoc()) {
        $items[] = [
            'purchase_item_id' => (int)$row['purchase_item_id'],
            'purchase_id' => (int)$row['purchase_id'],
            'material_id' => (int)$row['material_id'],
            'title' => $row['title'],
            'category' => $row['category'],
            'price' => (float)$row['price'],
            'file_path' => $row['file_path'],
            'file_size' => $row['file_size'] ? (int)$row['file_size'] : 0,
            'purchased_at' => $row['created_at']
        ];
    }
    $itemsStmt->close();
    
    echo json_encode([
        'success' => true,
        'total_purchases' => $total_purchases,
        'total_spent' => $total_spent,
        'items' => $items
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
