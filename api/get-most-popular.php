<?php
// api/get-most-popular.php
// Get the top 9 most popular materials (most purchased) across all users

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            m.id,
            m.title,
            m.description,
            m.price,
            m.file_path,
            m.thumbnail_path,
            m.purchases_count,
            m.views,
            c.name AS category,
            COALESCE(CONCAT(u.first_name, ' ', u.last_name), u.email, CONCAT('User ', m.user_id)) AS creator
        FROM materials m
        LEFT JOIN upload_categories c ON m.category_id = c.id
        LEFT JOIN users u ON u.id = m.user_id
        ORDER BY m.purchases_count DESC, m.views DESC
        LIMIT 9
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $materials = [];
    while ($row = $result->fetch_assoc()) {
        $materials[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'] ?: 'Untitled',
            'description' => $row['description'] ?: '',
            'price' => (float)$row['price'],
            'file_path' => $row['file_path'],
            'creator' => $row['creator'] ?: 'Unknown',
            'category' => $row['category'] ?: 'Others',
            'thumbnail_path' => $row['thumbnail_path'] ?: 'assets/images/default-thumbnail.jpg',
            'purchases_count' => (int)($row['purchases_count'] ?: 0),
            'views' => (int)($row['views'] ?: 0)
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'materials' => $materials,
        'count' => count($materials)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
