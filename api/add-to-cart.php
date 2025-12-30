<?php
// api/add-to-cart.php
// Accepts POST request to add item to user's cart
// Parameters: material_id (required), user_id (from session)
// Returns: {success: boolean, cart_count: int, message: string}

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
$material_id = isset($_POST['material_id']) ? (int)$_POST['material_id'] : 0;

if ($material_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid material_id']);
    exit;
}

try {
    // Check if cart table exists, if not create it
    $conn->query("CREATE TABLE IF NOT EXISTS cart (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        material_id INT NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_cart_item (user_id, material_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (material_id) REFERENCES materials(id)
    )");
    
    // Insert into cart or update if already exists
    $stmt = $conn->prepare("INSERT INTO cart (user_id, material_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE added_at = NOW()");
    $stmt->bind_param('ii', $user_id, $material_id);
    $stmt->execute();
    $stmt->close();
    
    // Get current cart count
    $countStmt = $conn->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ?");
    $countStmt->bind_param('i', $user_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult->fetch_assoc();
    $cartCount = (int)$countRow['cart_count'];
    $countStmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart',
        'cart_count' => $cartCount
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
