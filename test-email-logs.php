<?php
require_once 'db_config.php';

echo "=== Testing Email Logging ===\n\n";

// Test 1: Subscribe
echo "1. Testing subscribe...\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'logging@test.com';
ob_start();
include 'api/subscribe-email.php';
$out = ob_get_clean();
echo "   Response: " . json_decode($out, true)['message'] . "\n";

// Test 2: Support
echo "\n2. Testing support message...\n";
$_POST = [];
$_POST['name'] = 'Test Logging';
$_POST['email'] = 'testlog@test.com';
$_POST['message'] = 'Testing email logs.';
ob_start();
include 'api/send-support-message.php';
$out = ob_get_clean();
echo "   Response: " . json_decode($out, true)['message'] . "\n";

// Verify logs
echo "\n=== Email Logs ===\n";
if ($conn && !isset($db_error)) {
    $result = $conn->query("SELECT to_email, from_email, subject, status, sent_at FROM email_logs ORDER BY sent_at DESC LIMIT 5");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "  - To: {$row['to_email']}, Status: {$row['status']}, Sent: {$row['sent_at']}\n";
        }
    } else {
        echo "  No logs found.\n";
    }
}
?>
