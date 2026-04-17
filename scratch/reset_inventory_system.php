<?php
require 'config.php';

echo "=== INVENTORY RESET SYSTEM ===\n\n";

// 1. CLEAR HISTORICAL DATA
echo "--- Step 1: Clearing sales and export data ---\n";

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

$tables_to_clear = [
    'invoice_details' => "Clearing Invoice Details...",
    'invoices' => "Clearing Invoices...",
    'inventory_export_items' => "Clearing Export Items...",
    'inventory_exports' => "Clearing Inventory Exports...",
    'stock_movements' => "Clearing Stock Movements..."
];

foreach ($tables_to_clear as $table => $msg) {
    echo $msg . " ";
    if (mysqli_query($conn, "TRUNCATE TABLE $table")) {
        echo "[OK]\n";
    } else {
        echo "[FAILED: " . mysqli_error($conn) . "]\n";
    }
}

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
echo "\n";

// 2. RESET AND RECALCULATE STOCK
echo "--- Step 2: Recalculating stock from imports (Completed Receipts only) ---\n";

// Reset all to 0 first
mysqli_query($conn, "UPDATE items SET stock_quantity = 0");

// Calculate total imported for each product
$sql_calc = "SELECT ri.item_id, SUM(ri.quantity) as total_imported
             FROM inventory_receipt_items ri
             JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id
             WHERE ir.status = 'completed'
             GROUP BY ri.item_id";

$res_calc = mysqli_query($conn, $sql_calc);
$update_count = 0;

if ($res_calc) {
    while ($row = mysqli_fetch_assoc($res_calc)) {
        $item_id = $row['item_id'];
        $qty = $row['total_imported'];
        
        $sql_update = "UPDATE items SET stock_quantity = $qty WHERE item_id = $item_id";
        if (mysqli_query($conn, $sql_update)) {
            $update_count++;
        }
    }
}

echo "Successfully updated $update_count products based on import records.\n\n";

// 3. VERIFICATION SUMMARY
echo "--- Step 3: Verification ---\n";

$res_inv = mysqli_query($conn, "SELECT COUNT(*) FROM invoices");
$count_inv = mysqli_fetch_row($res_inv)[0];
echo "Invoices count: $count_inv " . ($count_inv == 0 ? "[OK]" : "[ERROR]") . "\n";

$res_exp = mysqli_query($conn, "SELECT COUNT(*) FROM inventory_exports");
$count_exp = mysqli_fetch_row($res_exp)[0];
echo "Exports count: $count_exp " . ($count_exp == 0 ? "[OK]" : "[ERROR]") . "\n";

$res_total_stock = mysqli_query($conn, "SELECT SUM(stock_quantity) FROM items");
$total_stock = mysqli_fetch_row($res_total_stock)[0];
echo "Total System Stock: " . ($total_stock ?: 0) . "\n";

$res_total_import = mysqli_query($conn, "SELECT SUM(ri.quantity) FROM inventory_receipt_items ri JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id WHERE ir.status = 'completed'");
$total_import = mysqli_fetch_row($res_total_import)[0];
echo "Total Completed Imports: " . ($total_import ?: 0) . "\n";

if ($total_stock == $total_import) {
    echo "\nRESULT: SUCCESS. Stock is perfectly synchronized with imports.\n";
} else {
    echo "\nRESULT: WARNING. Stock mismatch detected.\n";
}
