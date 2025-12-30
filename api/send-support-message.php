<?php
// api/send-support-message.php
// Receives support form submissions, saves to DB, and sends an email to webnoteshare@gmail.com

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/smtp-helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($name === '' || $email === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

$to = 'webnoteshare@gmail.com';
$from = 'webnoteshare@gmail.com';
$subject = "New Support Message from {$name} — NodeShare";

$sentOn = date('Y-m-d H:i:s');

$textBody = "Hi NodeShare Team! 👋💛💚\n\n";
$textBody .= "You’ve received a new message from the support page.\n";
$textBody .= "Here are the details — nicely wrapped and delivered with care ✨\n\n";
$textBody .= "---\n\n";
$textBody .= "🌟 Name:\n\n{$name}\n\n";
$textBody .= "📩 Email:\n\n{$email}\n\n";
$textBody .= "💬 Message:\n\n{$message}\n\n";
$textBody .= "🕒 Sent On:\n\n{$sentOn}\n\n";
$textBody .= "If you’d like to help them out, you can reply directly to their email!\n\n";
$textBody .= "Sending good vibes,\nNodeShare Support Bot 🤖💛💚";

$htmlBody = "<html><body style='font-family: Arial, sans-serif; color: #222;'>";
$htmlBody .= "<p>Hi NodeShare Team! 👋💛💚</p>";
$htmlBody .= "<p>You’ve received a new message from the support page.<br>Here are the details — nicely wrapped and delivered with care ✨</p>";
$htmlBody .= "<hr>";
$htmlBody .= "<p><strong>🌟 Name:</strong><br>" . htmlspecialchars($name) . "</p>";
$htmlBody .= "<p><strong>📩 Email:</strong><br>" . htmlspecialchars($email) . "</p>";
$htmlBody .= "<p><strong>💬 Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";
$htmlBody .= "<p><strong>🕒 Sent On:</strong><br>{$sentOn}</p>";
$htmlBody .= "<p>If you’d like to help them out, you can reply directly to their email!</p>";
$htmlBody .= "<p>Sending good vibes,<br>NodeShare Support Bot 🤖💛💚</p>";
$htmlBody .= "</body></html>";

try {
    // Save to database
    if ($conn && !isset($db_error)) {
        $stmt = $conn->prepare("INSERT INTO support_messages (name, email, message, status) VALUES (?, ?, ?, 'new')");
        if ($stmt) {
            $stmt->bind_param('sss', $name, $email, $message);
            if (!$stmt->execute()) {
                error_log("DB insert failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }
    
    // Send email
    $mailer = new SimpleSmtpMailer('webnoteshare@gmail.com', 'uhpvkihmsgqzauwh');
    $mailer->connect();
    $mailer->send($from, $to, $subject, $htmlBody, $textBody, $email);
    $mailer->close();

    // Log email send
    if ($conn && !isset($db_error)) {
        $stmt = $conn->prepare("INSERT INTO email_logs (to_email, from_email, subject, body, status) VALUES (?, ?, ?, ?, 'sent')");
        if ($stmt) {
            $stmt->bind_param('ssss', $to, $from, $subject, $htmlBody);
            if (!$stmt->execute()) {
                error_log("Email log failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Support message sent']);
    exit;
} catch (Exception $e) {
    // Log email failure
    if ($conn && !isset($db_error)) {
        $error_msg = $e->getMessage();
        $stmt = $conn->prepare("INSERT INTO email_logs (to_email, from_email, subject, body, status, error) VALUES (?, ?, ?, ?, 'failed', ?)");
        if ($stmt) {
            $stmt->bind_param('sssss', $to, $from, $subject, $htmlBody, $error_msg);
            if (!$stmt->execute()) {
                error_log("Email log failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send support message: ' . $e->getMessage()]);
    exit;
}

?>
