<?php
require_once 'db_config.php';

echo "Email Logs in Database:\n";
$result = $conn->query("SELECT to_email, subject, status, sent_at FROM email_logs ORDER BY sent_at DESC LIMIT 5");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['to_email']} | {$row['subject']} | Status: {$row['status']} | {$row['sent_at']}\n";
    }
} else {
    echo "  No logs found\n";
}

$conn->close();
?>
