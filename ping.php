<?php
// Simple PHP/ping endpoint to verify PHP is running and DB status
header('Content-Type: application/json');
$response = ['php' => true, 'time' => date('c')];

// Attempt to include DB config to report DB status (non-fatal)
require_once __DIR__ . '/db_config.php';
if (isset($db_error) && $db_error) {
    $response['db'] = ['ok' => false, 'error' => $db_error];
} elseif (isset($conn) && $conn && !$conn->connect_error) {
    $response['db'] = ['ok' => true];
} else {
    $response['db'] = ['ok' => false, 'error' => 'Unknown DB state'];
}

echo json_encode($response);
?>