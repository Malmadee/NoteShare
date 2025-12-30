<?php
// api/get-user-sales-earnings.php
// Get total sales and earnings for logged-in user's uploads

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
    // Get total sales (count of all purchase_items for materials uploaded by this user)
    // and total earnings (sum of prices of all purchased items from this user's uploads)
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(pi.id) as total_sales,
            COALESCE(SUM(pi.price), 0) as total_earnings
        FROM purchase_items pi
        JOIN materials m ON pi.material_id = m.id
        WHERE m.user_id = ?
    ");
    $statsStmt->bind_param('i', $user_id);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $statsRow = $statsResult->fetch_assoc();
    $statsStmt->close();
    
    $total_sales = (int)$statsRow['total_sales'];
    $total_earnings = (int)$statsRow['total_earnings'];
    
    echo json_encode([
        'success' => true,
        'total_sales' => $total_sales,
        'total_earnings' => $total_earnings
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
