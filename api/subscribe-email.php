<?php
// api/subscribe-email.php
// Handle newsletter subscription, save to DB, and send welcome email via Gmail SMTP

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/smtp-helper.php';

header('Content-Type: application/json; charset=utf-8');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate email input
if (!isset($_POST['email']) || empty($_POST['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

$email = trim($_POST['email']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Email configuration
$from = 'webnoteshare@gmail.com';
$subject = "Welcome to NodeShare! You're officially one of us 💛💚";

// Create plain text email body
$textBody = "Hi there! 👋\n\nThank you for subscribing to NodeShare!\n\n";
$textBody .= "We're really happy to have you here — seriously, it made our day. 😊\n\n";
$textBody .= "Here's what being a subscriber means:\n";
$textBody .= "🌟 You'll get updates on new features\n";
$textBody .= "📢 News about improvements and upcoming tools\n";
$textBody .= "🎁 Exclusive offers and bonus coin events\n";
$textBody .= "📝 Tips to help you upload, trade, and get the most out of your materials\n\n";
$textBody .= "NodeShare is all about sharing knowledge in a fun and easy way.\n";
$textBody .= "Whether you're uploading notes, trading materials, or discovering something new, we're here to make your experience smooth and enjoyable.\n\n";
$textBody .= "Stay tuned — exciting things are on the way, and you'll be the first to know!\n\n";
$textBody .= "Warm hugs,\nThe NodeShare Team 💛💚";

// Create HTML email body
$htmlBody = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        p { margin: 10px 0; }
        ul { margin: 10px 0; padding-left: 20px; }
        li { margin: 5px 0; }
    </style>
</head>
<body>
<p>Hi there! 👋</p>
<p>Thank you for subscribing to <strong>NodeShare</strong>!</p>
<p>We're really happy to have you here — seriously, it made our day. 😊</p>
<p><strong>Here's what being a subscriber means:</strong></p>
<ul>
    <li>🌟 You'll get updates on new features</li>
    <li>📢 News about improvements and upcoming tools</li>
    <li>🎁 Exclusive offers and bonus coin events</li>
    <li>📝 Tips to help you upload, trade, and get the most out of your materials</li>
</ul>
<p>NodeShare is all about sharing knowledge in a fun and easy way.</p>
<p>Whether you're uploading notes, trading materials, or discovering something new, we're here to make your experience smooth and enjoyable.</p>
<p>Stay tuned — exciting things are on the way, and you'll be the first to know!</p>
<p>Warm hugs,<br><strong>The NodeShare Team</strong> 💛</p>
</body>
</html>";

try {
    // Save to subscribers table
    if ($conn && !isset($db_error)) {
        $stmt = $conn->prepare("INSERT INTO subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE email = email");
        if ($stmt) {
            $stmt->bind_param('s', $email);
            if (!$stmt->execute()) {
                error_log("DB insert failed for subscriber: " . $stmt->error);
            }
            $stmt->close();
        }
    }
    
    // Initialize SMTP mailer
    $mailer = new SimpleSmtpMailer('webnoteshare@gmail.com', 'uhpvkihmsgqzauwh');
    
    // Connect to Gmail SMTP server
    $mailer->connect();
    
    // Send email
    $mailer->send($from, $email, $subject, $htmlBody, $textBody);
    
    // Log email send
    if ($conn && !isset($db_error)) {
        $stmt = $conn->prepare("INSERT INTO email_logs (to_email, from_email, subject, body, status) VALUES (?, ?, ?, ?, 'sent')");
        if ($stmt) {
            $stmt->bind_param('ssss', $email, $from, $subject, $htmlBody);
            if (!$stmt->execute()) {
                error_log("Email log failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }
    
    // Close connection
    $mailer->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Check your inbox.'
    ]);
    
} catch (Exception $e) {
    // Log email failure
    if ($conn && !isset($db_error)) {
        $error_msg = $e->getMessage();
        $stmt = $conn->prepare("INSERT INTO email_logs (to_email, from_email, subject, body, status, error) VALUES (?, ?, ?, ?, 'failed', ?)");
        if ($stmt) {
            $stmt->bind_param('sssss', $email, $from, $subject, $htmlBody, $error_msg);
            if (!$stmt->execute()) {
                error_log("Email log failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Email sending failed: ' . $e->getMessage()
    ]);
}
