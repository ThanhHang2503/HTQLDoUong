<?php
require 'config.php';
$sql = "SELECT ir.receipt_id, ir.total_value as header_total, 
               (SELECT SUM(line_total) FROM inventory_receipt_items WHERE receipt_id = ir.receipt_id) as detail_sum
        FROM inventory_receipts ir
        WHERE ir.status = 'completed'";
$res = mysqli_query($conn, $sql);
echo "Checking completed receipts for consistency:\n";
while ($row = mysqli_fetch_assoc($res)) {
    if (abs($row['header_total'] - $row['detail_sum']) > 0.01) {
        echo "Receipt #{$row['receipt_id']}: Header=" . $row['header_total'] . ", Detail=" . ($row['detail_sum'] ?? 0) . " ERROR\n";
    } else {
        echo "Receipt #{$row['receipt_id']}: OK\n";
    }
}
