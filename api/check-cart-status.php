<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false, 'user_id' => null]);
    exit;
}

require_once __DIR__ . '/db_config.php';

$user_id = $_SESSION['user_id'];

// Check if cart table exists
$result = $conn->query("SHOW TABLES LIKE 'cart'");
$table_exists = $result && $result->num_rows > 0;

// Get cart items count
$cartCount = 0;
if ($table_exists) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cartCount = (int)$row['count'];
    $stmt->close();
}

echo json_encode([
    'logged_in' => true,
    'user_id' => $user_id,
    'cart_table_exists' => $table_exists,
    'cart_items_count' => $cartCount
]);

$conn->close();
?>
