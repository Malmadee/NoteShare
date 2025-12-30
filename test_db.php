<?php
// Test database connection
echo "Testing database connection...\n\n";

$host = 'localhost';
$user = 'root';
$password = 'root';
$database = 'noteshare_db';

// Test 1: Connect without selecting database
echo "1. Connecting to MySQL server...\n";
$conn = new mysqli($host, $user, $password);

if ($conn->connect_error) {
    echo "❌ FAILED: " . $conn->connect_error . "\n";
    echo "MySQL server is not running or credentials are wrong.\n";
    exit;
}
echo "✓ Connected to MySQL\n\n";

// Test 2: Check if database exists
echo "2. Checking if database 'noteshare_db' exists...\n";
$result = $conn->query("SHOW DATABASES LIKE 'noteshare_db'");

if ($result->num_rows == 0) {
    echo "❌ Database 'noteshare_db' NOT FOUND\n";
    echo "You need to create it in phpMyAdmin:\n";
    echo "   1. Go to http://localhost/phpmyadmin\n";
    echo "   2. Click SQL tab\n";
    echo "   3. Paste and run these commands:\n\n";
    echo "   CREATE DATABASE noteshare_db;\n";
    echo "   USE noteshare_db;\n";
    echo "   CREATE TABLE users (\n";
    echo "       id INT AUTO_INCREMENT PRIMARY KEY,\n";
    echo "       first_name VARCHAR(100) NOT NULL,\n";
    echo "       last_name VARCHAR(100) NOT NULL,\n";
    echo "       email VARCHAR(255) NOT NULL UNIQUE,\n";
    echo "       password VARCHAR(255) NOT NULL,\n";
    echo "       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
    echo "       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n";
    echo "   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
    echo "   CREATE INDEX idx_email ON users(email);\n";
    $conn->close();
    exit;
}
echo "✓ Database 'noteshare_db' exists\n\n";

// Test 3: Connect to database
echo "3. Connecting to database 'noteshare_db'...\n";
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo "❌ FAILED: " . $conn->connect_error . "\n";
    exit;
}
echo "✓ Connected to database\n\n";

// Test 4: Check if users table exists
echo "4. Checking if 'users' table exists...\n";
$result = $conn->query("SHOW TABLES LIKE 'users'");

if ($result->num_rows == 0) {
    echo "❌ Table 'users' NOT FOUND\n";
    echo "Run this SQL command in phpMyAdmin:\n";
    echo "   CREATE TABLE users (\n";
    echo "       id INT AUTO_INCREMENT PRIMARY KEY,\n";
    echo "       first_name VARCHAR(100) NOT NULL,\n";
    echo "       last_name VARCHAR(100) NOT NULL,\n";
    echo "       email VARCHAR(255) NOT NULL UNIQUE,\n";
    echo "       password VARCHAR(255) NOT NULL,\n";
    echo "       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
    echo "       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n";
    echo "   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
    $conn->close();
    exit;
}
echo "✓ Table 'users' exists\n\n";

// Test 5: Check table structure
echo "5. Checking table structure...\n";
$result = $conn->query("DESCRIBE users");

if ($result) {
    echo "✓ Table columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "❌ Error describing table: " . $conn->error . "\n";
}

echo "\n✅ ALL TESTS PASSED! Database is properly set up.\n";
$conn->close();
?>
