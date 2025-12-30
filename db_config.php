<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'noteshare_db';

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    // Don't terminate execution; expose an error message for callers to handle.
    $db_error = "Connection failed: " . $conn->connect_error;
} else {
    // Set charset to utf8 when connected
    $conn->set_charset("utf8");
}
?>