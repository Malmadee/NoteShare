<?php
require 'db_config.php';

echo "=== DETAILED TABLE STRUCTURES ===\n\n";

// Purchases table
echo "PURCHASES TABLE:\n";
$result = $conn->query('DESCRIBE purchases');
$purchaseCols = [];
while($row = $result->fetch_assoc()) {
    $purchaseCols[] = $row['Field'];
    $null = $row['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
    echo "  ✓ " . $row['Field'] . " (" . $row['Type'] . ") " . $null . "\n";
}

// Purchase_items table
echo "\nPURCHASE_ITEMS TABLE:\n";
$result = $conn->query('DESCRIBE purchase_items');
$itemCols = [];
while($row = $result->fetch_assoc()) {
    $itemCols[] = $row['Field'];
    $null = $row['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
    echo "  ✓ " . $row['Field'] . " (" . $row['Type'] . ") " . $null . "\n";
}

// Check for required columns in purchase_items
echo "\n=== REQUIRED COLUMNS CHECK ===\n";
$requiredInPurchaseItems = ['purchase_item_id', 'purchase_id', 'material_id', 'title', 'category', 'price', 'file_path', 'file_size', 'purchased_at'];
echo "Checking purchase_items table for required columns:\n";
foreach ($requiredInPurchaseItems as $col) {
    if (in_array($col, $itemCols)) {
        echo "  ✓ " . $col . "\n";
    } else {
        echo "  ✗ " . $col . " - MISSING\n";
    }
}

// Sample data check
echo "\n=== SAMPLE DATA ===\n";
$result = $conn->query('SELECT COUNT(*) as count FROM purchases');
$row = $result->fetch_assoc();
echo "Purchases count: " . $row['count'] . "\n";

$result = $conn->query('SELECT COUNT(*) as count FROM purchase_items');
$row = $result->fetch_assoc();
echo "Purchase items count: " . $row['count'] . "\n";

$conn->close();
?>
