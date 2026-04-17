<?php
require_once __DIR__ . '/../config.php';

$codes = [
    'PN-20260417-121425',
    'PN-20260416-132625',
    'PN-20260416-131417',
    'PN-20260416-131114',
    'PN-20260416-131022'
];

mysqli_begin_transaction($conn);
try {
    // 1. Get the receipt_ids
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $stmt = mysqli_prepare($conn, "SELECT receipt_id FROM inventory_receipts WHERE receipt_code IN ($placeholders)");
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($codes)), ...$codes);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ids = [];
    while ($r = mysqli_fetch_row($res)) {
        $ids[] = $r[0];
    }
    
    if (empty($ids)) {
        throw new Exception("No receipts found with the provided codes.");
    }

    echo "Found IDs: " . implode(', ', $ids) . "\n";
    $idList = implode(',', $ids);

    // 2. Delete from stock_movements
    echo "Deleting from stock_movements...\n";
    mysqli_query($conn, "DELETE FROM stock_movements WHERE reference_type = 'receipt' AND reference_id IN ($idList)");

    // 3. Delete from inventory_receipt_items
    echo "Deleting from inventory_receipt_items...\n";
    mysqli_query($conn, "DELETE FROM inventory_receipt_items WHERE receipt_id IN ($idList)");

    // 4. Delete from inventory_receipts
    echo "Deleting from inventory_receipts...\n";
    mysqli_query($conn, "DELETE FROM inventory_receipts WHERE receipt_id IN ($idList)");

    mysqli_commit($conn);
    echo "Successfully deleted 5 receipts and their associated records.\n";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "ERROR: " . $e->getMessage() . "\n";
}
