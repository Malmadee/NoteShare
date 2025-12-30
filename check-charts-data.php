<?php
require 'db_config.php';

echo "=== Database Status ===\n";

$result = $conn->query('SELECT COUNT(*) as count FROM purchases');
$row = $result->fetch_assoc();
echo 'Total purchases: ' . $row['count'] . "\n";

$result = $conn->query('SELECT COUNT(*) as count FROM purchase_items');
$row = $result->fetch_assoc();
echo 'Total purchase items: ' . $row['count'] . "\n";

$result = $conn->query('SELECT COUNT(*) as count FROM materials');
$row = $result->fetch_assoc();
echo 'Total materials: ' . $row['count'] . "\n";

$result = $conn->query('SELECT COUNT(*) as count FROM users');
$row = $result->fetch_assoc();
echo 'Total users: ' . $row['count'] . "\n";

echo "\n=== Sample Check Query for User 1 ===\n";
$result = $conn->query("
    SELECT 
        m.category,
        COUNT(pi.id) as sales_count
    FROM purchase_items pi
    JOIN materials m ON pi.material_id = m.id
    WHERE m.user_id = 1
    GROUP BY m.category
");

if ($result && $result->num_rows > 0) {
    echo "Sales by category for user 1:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['category'] . ": " . $row['sales_count'] . " sales\n";
    }
} else {
    echo "No sales data found for user 1\n";
}
?>
