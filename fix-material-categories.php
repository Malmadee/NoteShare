<?php
// Fix missing categories in materials table
require 'db_config.php';

// Update materials to have proper categories
// You can adjust these based on actual content types
$updates = [
    2 => 'Notes',           // Linear Algebra - likely notes
    3 => 'Notes',           // Performance Evaluation - likely notes
    6 => 'Others'           // Hiring - category as Others
];

echo "=== Updating Material Categories ===\n";
foreach ($updates as $material_id => $category) {
    $stmt = $conn->prepare("UPDATE materials SET category = ? WHERE id = ?");
    $stmt->bind_param('si', $category, $material_id);
    if ($stmt->execute()) {
        echo "✓ Material ID $material_id updated to category: $category\n";
    } else {
        echo "✗ Failed to update Material ID $material_id\n";
    }
    $stmt->close();
}

echo "\n=== Verifying Updates ===\n";
$result = $conn->query("SELECT id, title, category FROM materials");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Title: " . $row['title'] . ", Category: " . $row['category'] . "\n";
}

echo "\n=== Testing Sales by Category Query ===\n";
$result = $conn->query("
    SELECT 
        m.category,
        COUNT(pi.id) as sales_count
    FROM purchase_items pi
    JOIN materials m ON pi.material_id = m.id
    WHERE m.user_id = 1
    GROUP BY m.category
    ORDER BY m.category ASC
");

if ($result && $result->num_rows > 0) {
    echo "Sales by category for user 1:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['category'] . ": " . $row['sales_count'] . " sales\n";
    }
} else {
    echo "No sales data found\n";
}
?>
