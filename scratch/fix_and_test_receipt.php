<?php
/**
 * Fix: Bỏ trigger trg_items_sale_status_after_update (gây deadlock trong transaction)
 * Thay thế = cập nhật sale_status trong hàm updateStock của Repositories.php
 */
require_once __DIR__ . '/../config.php';

// Drop trigger gây deadlock
$ok = mysqli_query($conn, "DROP TRIGGER IF EXISTS trg_items_sale_status_after_update");
echo $ok ? "✅ Dropped trigger trg_items_sale_status_after_update\n" : "❌ " . mysqli_error($conn) . "\n";

// Test thử INSERT phiếu nhập
echo "\n=== TEST INSERT inventory_receipts ===\n";
$test_supplier = 1;
$test_account  = 1;
$code = 'TEST-' . date('His');
$ok = mysqli_query($conn, "INSERT INTO inventory_receipts (receipt_code, supplier_id, import_date, total_value, status, created_by)
    VALUES ('$code', $test_supplier, CURDATE(), 0, 'completed', $test_account)");
if ($ok) {
    $rid = mysqli_insert_id($conn);
    echo "✅ Insert receipt OK, receipt_id=$rid\n";

    // Test insert receipt item
    $item_id = 1;
    $ok2 = mysqli_query($conn, "INSERT INTO inventory_receipt_items (receipt_id, item_id, quantity, import_price, unit_price, line_total)
        VALUES ($rid, $item_id, 10, 50000, 65000, 500000)");
    echo $ok2 ? "✅ Insert receipt_item OK\n" : "❌ receipt_item: " . mysqli_error($conn) . "\n";

    // Cleanup
    mysqli_query($conn, "DELETE FROM inventory_receipt_items WHERE receipt_id=$rid");
    mysqli_query($conn, "DELETE FROM inventory_receipts WHERE receipt_id=$rid");
    echo "✅ Cleanup done.\n";
} else {
    echo "❌ Insert receipt failed: " . mysqli_error($conn) . "\n";
}

echo "\n=== TEST stock_movements INSERT ===\n";
$ok3 = mysqli_query($conn, "INSERT INTO stock_movements (item_id, movement_type, quantity_change, stock_before, stock_after, unit_cost, reference_type, reference_id, note, created_by)
    VALUES (1, 'import', 10, 150, 160, 50000, 'receipt', 999, 'test', 1)");
if ($ok3) {
    $mid = mysqli_insert_id($conn);
    echo "✅ Insert stock_movement OK, movement_id=$mid\n";
    mysqli_query($conn, "DELETE FROM stock_movements WHERE movement_id=$mid");
} else {
    echo "❌ stock_movement: " . mysqli_error($conn) . "\n";
}

echo "\n🎉 Done!\n";
