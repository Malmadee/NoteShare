<?php
/**
 * Test script to verify uploads API works immediately after insert
 * Run this to test the fix
 */

// Start a test session
session_start();
$_SESSION['user_id'] = 1; // Use a test user ID

header('Content-Type: application/json');

require_once 'db_config.php';

try {
    echo json_encode([
        'status' => 'testing',
        'message' => 'Testing get-user-uploads API response time',
        'timestamp' => microtime(true)
    ]);
    echo "\n";
    
    // Test the API call with timing
    $start = microtime(true);
    
    $user_id = 1;
    $stmt = $conn->prepare("
        SELECT m.id, m.title, m.description, uc.name as category, m.price, m.file_path, m.created_at
        FROM materials m
        LEFT JOIN upload_categories uc ON m.category_id = uc.id
        WHERE m.user_id = ?
        ORDER BY m.created_at DESC
        LIMIT 5
    ");
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $uploads = [];
    while ($row = $result->fetch_assoc()) {
        $uploads[] = $row;
    }
    
    $elapsed = microtime(true) - $start;
    
    $stmt->close();
    
    error_log('Database query took: ' . round($elapsed * 1000, 2) . 'ms');
    
    echo json_encode([
        'success' => true,
        'uploads_found' => count($uploads),
        'query_time_ms' => round($elapsed * 1000, 2),
        'first_upload' => $uploads[0] ?? null
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
