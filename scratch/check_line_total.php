<?php
require 'config.php';
$sql = "SELECT receipt_item_id, quantity, import_price, line_total, (quantity * import_price) as calc_total
        FROM inventory_receipt_items
        LIMIT 20";
$res = mysqli_query($conn, $sql);
echo "Checking line_total vs quantity * import_price:\n";
while ($row = mysqli_fetch_assoc($res)) {
    if (abs($row['line_total'] - $row['calc_total']) > 0.01) {
        echo "Item #{$row['receipt_item_id']}: Qty={$row['quantity']}, Price={$row['import_price']}, LineTotal={$row['line_total']}, Calc=" . $row['calc_total'] . " ERROR\n";
    } else {
        echo "Item #{$row['receipt_item_id']}: OK\n";
    }
}
