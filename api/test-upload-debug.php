<?php
header('Content-Type: application/json');
session_start();

// Log all data received
$debug = [
    'session_user_id' => $_SESSION['user_id'] ?? null,
    'post_keys' => array_keys($_POST),
    'post_data' => [
        'title' => $_POST['title'] ?? 'missing',
        'description' => $_POST['description'] ?? 'missing',
        'category' => $_POST['category'] ?? 'missing',
        'price' => $_POST['price'] ?? 'missing',
    ],
    'file_info' => [
        'has_file' => isset($_FILES['file']),
        'file_name' => $_FILES['file']['name'] ?? null,
        'file_type' => $_FILES['file']['type'] ?? null,
        'file_size' => $_FILES['file']['size'] ?? null,
        'file_error' => $_FILES['file']['error'] ?? null,
        'file_tmp_name' => $_FILES['file']['tmp_name'] ?? null,
    ],
    'file_error_messages' => [
        0 => 'UPLOAD_ERR_OK',
        1 => 'UPLOAD_ERR_INI_SIZE',
        2 => 'UPLOAD_ERR_FORM_SIZE',
        3 => 'UPLOAD_ERR_PARTIAL',
        4 => 'UPLOAD_ERR_NO_FILE',
        6 => 'UPLOAD_ERR_NO_TMP_DIR',
        7 => 'UPLOAD_ERR_CANT_WRITE',
        8 => 'UPLOAD_ERR_EXTENSION'
    ]
];

echo json_encode($debug, JSON_PRETTY_PRINT);
?>
