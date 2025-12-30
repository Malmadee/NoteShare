<?php
// api/get-monthly-earnings.php
// Get monthly earnings for logged-in user's uploads for the current year

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_year = date('Y');

try {
    // Get earnings by month for current year
    $statsStmt = $conn->prepare("
        SELECT 
            MONTH(p.created_at) as month,
            COALESCE(SUM(pi.price), 0) as earnings
        FROM purchase_items pi
        JOIN materials m ON pi.material_id = m.id
        JOIN purchases p ON pi.purchase_id = p.id
        WHERE m.user_id = ? 
            AND YEAR(p.created_at) = ?
        GROUP BY MONTH(p.created_at)
        ORDER BY MONTH(p.created_at) ASC
    ");
    $statsStmt->bind_param('ii', $user_id, $current_year);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    
    // Initialize all months with 0
    $monthlyEarnings = array_fill(0, 12, 0);
    
    while ($row = $statsResult->fetch_assoc()) {
        $month = (int)$row['month'] - 1; // Convert to 0-indexed
        $monthlyEarnings[$month] = (int)$row['earnings'];
    }
    
    $statsStmt->close();
    
    echo json_encode([
        'success' => true,
        'months' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'earnings' => $monthlyEarnings,
        'year' => $current_year
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
