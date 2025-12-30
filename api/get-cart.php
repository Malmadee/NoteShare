<?php
// api/get-cart.php
// Accepts GET request to retrieve user's cart
// Returns: {success: boolean, items: [{id, title, price, file_path, ...}], total: number}

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get cart items with material details
    $stmt = $conn->prepare("
        SELECT m.id, m.title, m.description, m.price, m.file_path, m.thumbnail_path, c.name AS category
        FROM cart
        JOIN materials m ON cart.material_id = m.id
        JOIN upload_categories c ON m.category_id = c.id
        WHERE cart.user_id = ?
        ORDER BY cart.added_at DESC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $price = (float)$row['price'];
        $items[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'price' => $price,
            'category' => $row['category'],
            'file_path' => $row['file_path'],
            'thumbnail' => $row['thumbnail_path']
        ];
        $total += $price;
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => round($total, 2),
        'count' => count($items)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
