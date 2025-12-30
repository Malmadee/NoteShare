<?php
require 'db_config.php';

echo "=== UPDATING USER WALLET BALANCES ===\n\n";

// Update all users to 200 coins
$result = $conn->query("UPDATE users SET wallet_balance = 200 WHERE wallet_balance IS NOT NULL");

if ($result) {
    $affectedRows = $conn->affected_rows;
    echo "✓ Updated $affectedRows user(s) to 200 coins\n\n";
    
    // Show updated balances
    echo "=== UPDATED WALLET BALANCES ===\n\n";
    $result = $conn->query("SELECT id, first_name, last_name, email, wallet_balance FROM users ORDER BY id");
    
    if ($result->num_rows > 0) {
        printf("%-5s | %-20s | %-30s | %-10s\n", "ID", "Name", "Email", "Balance");
        echo str_repeat("-", 70) . "\n";
        
        while ($row = $result->fetch_assoc()) {
            $name = $row['first_name'] . ' ' . $row['last_name'];
            printf("%-5d | %-20s | %-30s | %-10d\n", 
                $row['id'], 
                substr($name, 0, 20), 
                substr($row['email'], 0, 30), 
                $row['wallet_balance']
            );
        }
    }
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

// Also update the database default for future users
echo "\n=== UPDATING DEFAULT FOR NEW USERS ===\n";
$result = $conn->query("ALTER TABLE users MODIFY COLUMN wallet_balance INT DEFAULT 200");

if ($result) {
    echo "✓ New default set to 200 coins for future users\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

$conn->close();
?>
