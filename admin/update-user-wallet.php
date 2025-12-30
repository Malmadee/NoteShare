<?php
// admin/update-user-wallet.php
// Update a specific user's wallet balance

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_config.php';

// Basic security - in production you'd check for admin credentials
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['user_id']) || !isset($_POST['new_balance'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing user_id or new_balance']);
    exit;
}

$user_id = (int)$_POST['user_id'];
$new_balance = (int)$_POST['new_balance'];

if ($new_balance < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Balance cannot be negative']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->bind_param('ii', $new_balance, $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Wallet updated successfully',
                'user_id' => $user_id,
                'new_balance' => $new_balance
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } else {
        throw new Exception($stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
