<?php
require __DIR__ . '/../config.php';

// Kiểm tra account, customer, item có sẵn
$accRes = mysqli_query($conn, "SELECT account_id, full_name FROM accounts LIMIT 3");
echo "=== ACCOUNTS ===\n";
while ($r = mysqli_fetch_assoc($accRes)) echo $r['account_id'] . ": " . $r['full_name'] . "\n";

$cusRes = mysqli_query($conn, "SELECT customer_id, customer_name FROM customers LIMIT 3");
echo "\n=== CUSTOMERS ===\n";
while ($r = mysqli_fetch_assoc($cusRes)) echo $r['customer_id'] . ": " . $r['customer_name'] . "\n";

$itemRes = mysqli_query($conn, "SELECT item_id, item_name, unit_price FROM items WHERE item_status='active' LIMIT 10");
echo "\n=== ITEMS ===\n";
while ($r = mysqli_fetch_assoc($itemRes)) echo $r['item_id'] . ": " . $r['item_name'] . " - " . $r['unit_price'] . "\n";
