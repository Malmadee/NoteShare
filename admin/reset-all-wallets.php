<?php
// admin/reset-all-wallets.php
// Reset all users' wallets to 200 coins

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_config.php';

// Basic security - in production you'd check for admin credentials
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $result = $conn->query("UPDATE users SET wallet_balance = 200 WHERE wallet_balance IS NOT NULL");
    
    if ($result) {
        $affectedRows = $conn->affected_rows;
        echo json_encode([
            'success' => true,
            'message' => 'All users reset to 200 coins',
            'count' => $affectedRows
        ]);
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
