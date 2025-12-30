<?php
require_once 'db_config.php';

// Check if cart table exists
$result = $conn->query("SHOW TABLES LIKE 'cart'");
if ($result && $result->num_rows > 0) {
    echo "✓ cart table already exists\n";
    
    // Show structure
    $struct = $conn->query("DESCRIBE cart");
    echo "\nCart table structure:\n";
    while ($row = $struct->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "✗ cart table does NOT exist - you need to create it\n";
    echo "\nRun this SQL in phpMyAdmin:\n\n";
    
    echo "CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    material_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, material_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
);\n";
}

$conn->close();
?>
