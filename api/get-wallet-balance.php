<?php
// api/get-wallet-balance.php
// Get current wallet balance for logged-in user

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
    // Check if wallet column exists, if not create it with default of 200 coins
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS wallet_balance INT DEFAULT 200");
    
    // Get wallet balance
    $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $balance = (int)$row['wallet_balance'];
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'wallet_balance' => $balance
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
