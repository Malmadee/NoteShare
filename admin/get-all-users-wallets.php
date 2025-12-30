<?php
// admin/get-all-users-wallets.php
// Get all users and their wallet balances for admin panel

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_config.php';

try {
    $result = $conn->query("
        SELECT 
            id,
            first_name,
            last_name,
            email,
            wallet_balance,
            created_at
        FROM users
        ORDER BY id DESC
    ");
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => (int)$row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'wallet_balance' => (int)$row['wallet_balance'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
