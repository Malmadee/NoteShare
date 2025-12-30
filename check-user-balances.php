<?php
require 'db_config.php';

echo "=== CURRENT USER WALLET BALANCES ===\n\n";

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
} else {
    echo "No users found.\n";
}

$conn->close();
?>
