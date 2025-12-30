<?php
require 'db_config.php';

echo "=== Materials Details ===\n";
$result = $conn->query("SELECT id, user_id, title, category FROM materials LIMIT 10");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", User: " . $row['user_id'] . ", Title: " . $row['title'] . ", Category: '" . $row['category'] . "'\n";
    }
} else {
    echo "No materials found\n";
}

echo "\n=== Purchase Items Details ===\n";
$result = $conn->query("SELECT pi.id, pi.purchase_id, pi.material_id, pi.price, m.user_id, m.category FROM purchase_items pi LEFT JOIN materials m ON pi.material_id = m.id");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "PI_ID: " . $row['id'] . ", Purchase: " . $row['purchase_id'] . ", Material: " . $row['material_id'] . ", Price: " . $row['price'] . ", Mat_User: " . $row['user_id'] . ", Category: '" . $row['category'] . "'\n";
    }
} else {
    echo "No purchase items found\n";
}

echo "\n=== Purchases Details ===\n";
$result = $conn->query("SELECT id, user_id, total_amount, created_at FROM purchases");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Purchase ID: " . $row['id'] . ", User: " . $row['user_id'] . ", Amount: " . $row['total_amount'] . ", Date: " . $row['created_at'] . "\n";
    }
} else {
    echo "No purchases found\n";
}
?>
