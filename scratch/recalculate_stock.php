<?php
require_once __DIR__ . '/../config.php';

mysqli_begin_transaction($conn);
try {
    // 1. Get all active items
    echo "Fetching all active items...\n";
    $itemsRs = mysqli_query($conn, "SELECT item_id, item_name FROM items WHERE item_status = 'active'");
    $items = mysqli_fetch_all($itemsRs, MYSQLI_ASSOC);

    foreach ($items as $item) {
        $id = (int)$item['item_id'];
        $name = $item['item_name'];

        // 2. Sum all movements for this item
        $movRs = mysqli_query($conn, "SELECT SUM(quantity_change) as total FROM stock_movements WHERE item_id = $id");
        $movRow = mysqli_fetch_assoc($movRs);
        $newStock = (int)($movRow['total'] ?? 0);

        // 3. Update the item stock and status
        $saleStatus = $newStock <= 0 ? 'stopped' : 'selling';
        
        echo "Updating Product #$id ($name): New Stock = $newStock\n";
        $updateSql = "UPDATE items SET stock_quantity = $newStock, sale_status = '$saleStatus' WHERE item_id = $id";
        if (!mysqli_query($conn, $updateSql)) {
            throw new Exception("Failed to update item $id: " . mysqli_error($conn));
        }
    }

    mysqli_commit($conn);
    echo "Stock recalculation successful! All items now reflect the sum of their valid stock movements.\n";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "ERROR: " . $e->getMessage() . "\n";
}
