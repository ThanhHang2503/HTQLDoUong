<?php
require 'config.php';

echo "Starting synchronization of inventory_receipts.total_value...\n";

$sql = "UPDATE inventory_receipts ir
        SET ir.total_value = (
            SELECT COALESCE(SUM(line_total), 0)
            FROM inventory_receipt_items
            WHERE receipt_id = ir.receipt_id
        )
        WHERE ir.status = 'completed'";

if (mysqli_query($conn, $sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "Success! Updated $affected receipts.\n";
} else {
    echo "Error updating receipts: " . mysqli_error($conn) . "\n";
}

// Verification
$sql_check = "SELECT ir.receipt_id, ir.total_value, 
                    (SELECT SUM(line_total) FROM inventory_receipt_items WHERE receipt_id = ir.receipt_id) as detail_sum
             FROM inventory_receipts ir
             WHERE ir.status = 'completed'";
$res = mysqli_query($conn, $sql_check);
$errors = 0;
while ($row = mysqli_fetch_assoc($res)) {
    if (abs($row['total_value'] - $row['detail_sum']) > 0.01) {
        $errors++;
    }
}

if ($errors === 0) {
    echo "Verification passed: All completed receipts are now consistent.\n";
} else {
    echo "Verification failed: $errors receipts are still inconsistent.\n";
}
