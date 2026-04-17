<?php
require_once __DIR__ . '/../config.php';

mysqli_begin_transaction($conn);
try {
    // 1. Get all active items
    echo "Auditing all active items...\n";
    $itemsRs = mysqli_query($conn, "SELECT item_id, item_name FROM items WHERE item_status = 'active'");
    $items = mysqli_fetch_all($itemsRs, MYSQLI_ASSOC);

    foreach ($items as $item) {
        $id = (int)$item['item_id'];
        $name = $item['item_name'];

        // 2. Sum quantities from all Goods Receipts
        $importRs = mysqli_query($conn, "SELECT SUM(quantity) as total FROM inventory_receipt_items WHERE item_id = $id");
        $importRow = mysqli_fetch_assoc($importRs);
        $totalImported = (int)($importRow['total'] ?? 0);

        // 3. Sum quantities from all Sales Invoices
        $saleRs = mysqli_query($conn, "SELECT SUM(quantity) as total FROM invoice_details WHERE item_id = $id");
        $saleRow = mysqli_fetch_assoc($saleRs);
        $totalSold = (int)($saleRow['total'] ?? 0);

        // 4. Calculate net stock
        $newStock = $totalImported - $totalSold;
        $saleStatus = $newStock <= 0 ? 'stopped' : 'selling';
        
        echo "Product #$id ($name): Imported($totalImported) - Sold($totalSold) = Result($newStock)\n";
        
        // 5. Hard update the master stock quantity
        $updateSql = "UPDATE items SET stock_quantity = $newStock, sale_status = '$saleStatus' WHERE item_id = $id";
        if (!mysqli_query($conn, $updateSql)) {
            throw new Exception("Failed to update item $id: " . mysqli_error($conn));
        }
    }

    mysqli_commit($conn);
    echo "Stock re-audit successful! All items are now synchronized with existing receipts and invoices.\n";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "ERROR: " . $e->getMessage() . "\n";
}
