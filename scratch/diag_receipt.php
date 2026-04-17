<?php
require_once __DIR__ . '/../config.php';

echo "=== TABLES CHECK ===\n";
$tables = ['inventory_receipts', 'inventory_receipt_items', 'stock_movements', 'items', 'suppliers', 'accounts'];
foreach ($tables as $t) {
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    echo ($r && mysqli_num_rows($r) > 0 ? "✅" : "❌") . " $t\n";
}

echo "\n=== DESCRIBE inventory_receipts ===\n";
$r = mysqli_query($conn, "DESCRIBE inventory_receipts");
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null=' . $row['Null'] . ' | Key=' . $row['Key'] . ' | Default=' . $row['Default'] . PHP_EOL;
}

echo "\n=== DESCRIBE inventory_receipt_items ===\n";
$r = mysqli_query($conn, "DESCRIBE inventory_receipt_items");
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null=' . $row['Null'] . ' | Key=' . $row['Key'] . PHP_EOL;
}

echo "\n=== DESCRIBE stock_movements ===\n";
$r = mysqli_query($conn, "DESCRIBE stock_movements");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        echo $row['Field'] . ' | ' . $row['Type'] . ' | Null=' . $row['Null'] . ' | Key=' . $row['Key'] . PHP_EOL;
    }
} else {
    echo "Table does not exist\n";
}

echo "\n=== ACTIVE ITEMS ===\n";
$r = mysqli_query($conn, "SELECT item_id, item_code, item_name, stock_quantity FROM items WHERE item_status='active' LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['item_id'] . ' | ' . $row['item_code'] . ' | ' . $row['item_name'] . ' | stock=' . $row['stock_quantity'] . PHP_EOL;
}

echo "\n=== ACTIVE SUPPLIERS ===\n";
$r = mysqli_query($conn, "SELECT supplier_id, supplier_name, status FROM suppliers WHERE status='active' LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['supplier_id'] . ' | ' . $row['supplier_name'] . ' | status=' . $row['status'] . PHP_EOL;
}

echo "\n=== FOREIGN KEYS on inventory_receipt_items ===\n";
$r = mysqli_query($conn, "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_receipt_items' AND REFERENCED_TABLE_NAME IS NOT NULL");
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['CONSTRAINT_NAME'] . ': ' . $row['COLUMN_NAME'] . ' -> ' . $row['REFERENCED_TABLE_NAME'] . '.' . $row['REFERENCED_COLUMN_NAME'] . PHP_EOL;
}
