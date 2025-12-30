<?php
// api/get-sales-by-category.php
// Get sales count by category for logged-in user's uploads

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
    // Ensure DB connection succeeded in db_config.php
    if ((isset($db_error) && $db_error) || (isset($conn) && $conn->connect_error)) {
        $errMsg = isset($db_error) ? $db_error : (isset($conn) ? $conn->connect_error : 'Unknown DB connection error');
        error_log('[get-sales-by-category] DB connection error: ' . $errMsg);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection error']);
        exit;
    }
    // Get sales count by category for this user's uploads
    // Use category_id -> upload_categories to get canonical category names
    $sql = "SELECT
        COALESCE(uc.name, m.category) AS category_name,
        COUNT(pi.id) AS sales_count
    FROM purchase_items pi
    JOIN materials m ON pi.material_id = m.id
    LEFT JOIN upload_categories uc ON m.category_id = uc.id
    WHERE m.user_id = ?
    GROUP BY category_name
    ORDER BY category_name ASC";

    $statsStmt = $conn->prepare($sql);
    if ($statsStmt === false) {
        $err = isset($conn) ? $conn->error : 'prepare failed';
        error_log('[get-sales-by-category] prepare() failed: ' . $err);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database query preparation failed']);
        exit;
    }

    if (!$statsStmt->bind_param('i', $user_id)) {
        error_log('[get-sales-by-category] bind_param failed: ' . $statsStmt->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database bind failed']);
        exit;
    }

    if (!$statsStmt->execute()) {
        error_log('[get-sales-by-category] execute failed: ' . $statsStmt->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database query execution failed']);
        exit;
    }

    $statsResult = $statsStmt->get_result();
    
    $salesByCategory = [
        'Notes' => 0,
        'Videos' => 0,
        'Exam Papers' => 0,
        'Others' => 0
    ];
    
    while ($row = $statsResult->fetch_assoc()) {
        $category = trim($row['category_name']);
        $sales_count = (int)$row['sales_count'];

        // Normalize category names case-insensitively and handle common variants
        $lower = strtolower($category);
        if ($lower === 'notes' || strpos($lower, 'note') !== false) {
            $salesByCategory['Notes'] += $sales_count;
        } elseif ($lower === 'videos' || strpos($lower, 'video') !== false) {
            $salesByCategory['Videos'] += $sales_count;
        } elseif ($lower === 'exam papers' || $lower === 'exampapers' || strpos($lower, 'exam') !== false || strpos($lower, 'paper') !== false) {
            $salesByCategory['Exam Papers'] += $sales_count;
        } else {
            // Anything else is treated as Others
            $salesByCategory['Others'] += $sales_count;
        }
    }
    
    $statsStmt->close();
    
    echo json_encode([
        'success' => true,
        'categories' => array_keys($salesByCategory),
        'sales' => array_values($salesByCategory)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
