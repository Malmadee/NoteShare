<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$password = 'root';
$database = 'noteshare_db';

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");
?>
