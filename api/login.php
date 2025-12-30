<?php
session_start();
header('Content-Type: application/json');

// Get login credentials
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

require_once __DIR__ . '/../db_config.php';

// Query user by email
$stmt = $conn->prepare('SELECT id, email, password FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Verify password
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['email'] = $user['email'];

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Logged in successfully',
    'user_id' => $user['id'],
    'email' => $user['email']
]);

$conn->close();
?>
