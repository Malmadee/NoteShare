<?php
session_start();
require_once __DIR__ . '/../db_config.php';

// If DB connection failed, return JSON error so the client sees a clear message
if (isset($db_error) && $db_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'errors' => ["Database connection error: $db_error"]]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
    
    // Check user exists
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'errors' => ['Email or password is incorrect.']]);
        $stmt->close();
        exit;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        echo json_encode(['success' => true, 'message' => 'Login successful!', 'redirect' => '/NoteShare/home.html']);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Email or password is incorrect.']]);
    }
}

$conn->close();
?>
