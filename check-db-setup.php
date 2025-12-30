<?php
require 'db_config.php';

echo "=== DATABASE SETUP CHECK ===\n\n";

// Check existing tables
echo "1. EXISTING TABLES:\n";
$result = $conn->query('SHOW TABLES');
while($row = $result->fetch_assoc()) {
    $tableName = array_values($row)[0];
    echo "  ✓ " . $tableName . "\n";
}

// Check users table structure
echo "\n2. USERS TABLE COLUMNS:\n";
$result = $conn->query('DESCRIBE users');
$hasWalletColumn = false;
while($row = $result->fetch_assoc()) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    if ($row['Field'] === 'wallet_balance') {
        $hasWalletColumn = true;
    }
}

if (!$hasWalletColumn) {
    echo "\n⚠️  wallet_balance column MISSING from users table\n";
    echo "   Action: APIs will auto-create this with ALTER TABLE IF NOT EXISTS\n";
} else {
    echo "\n✓ wallet_balance column exists\n";
}

// Check if cart table exists
echo "\n3. CART TABLE:\n";
$cartExists = $conn->query("SHOW TABLES LIKE 'cart'");
if ($cartExists->num_rows > 0) {
    echo "  ✓ Cart table exists\n";
    $result = $conn->query('DESCRIBE cart');
    while($row = $result->fetch_assoc()) {
        echo "    - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "  ✗ Cart table MISSING\n";
    echo "  Action: Will be created when users add items to cart\n";
}

// Check if purchases table exists
echo "\n4. PURCHASES TABLE:\n";
$purchasesExists = $conn->query("SHOW TABLES LIKE 'purchases'");
if ($purchasesExists->num_rows > 0) {
    echo "  ✓ Purchases table exists\n";
} else {
    echo "  ✗ Purchases table MISSING\n";
}

// Check if purchase_items table exists
echo "\n5. PURCHASE_ITEMS TABLE:\n";
$purchaseItemsExists = $conn->query("SHOW TABLES LIKE 'purchase_items'");
if ($purchaseItemsExists->num_rows > 0) {
    echo "  ✓ Purchase_items table exists\n";
} else {
    echo "  ✗ Purchase_items table MISSING\n";
}

echo "\n=== END CHECK ===\n";
$conn->close();
?>
