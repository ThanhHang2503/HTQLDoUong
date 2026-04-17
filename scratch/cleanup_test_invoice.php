<?php
include 'config.php';

mysqli_begin_transaction($conn);
try {
    // 1. Get last invoice
    $res = mysqli_query($conn, "SELECT invoice_id FROM invoices ORDER BY invoice_id DESC LIMIT 1");
    $row = mysqli_fetch_assoc($res);
    if (!$row) throw new Exception("No invoice found to delete");
    $invoice_id = $row['invoice_id'];
    echo "Deleting invoice #$invoice_id...\n";

    // 2. Get details to Revert Stock
    $res_det = mysqli_query($conn, "SELECT item_id, quantity FROM invoice_details WHERE invoice_id = $invoice_id");
    while ($detail = mysqli_fetch_assoc($res_det)) {
        $item_id = $detail['item_id'];
        $qty = $detail['quantity'];
        echo "Reverting stock for item #$item_id (+$qty)...\n";
        mysqli_query($conn, "UPDATE items SET stock_quantity = stock_quantity + $qty WHERE item_id = $item_id");
        
        // Also delete the movement log
        mysqli_query($conn, "DELETE FROM stock_movements WHERE reference_type = 'invoice' AND reference_id = $invoice_id AND item_id = $item_id");
    }

    // 3. Delete invoice and its details
    mysqli_query($conn, "DELETE FROM invoice_details WHERE invoice_id = $invoice_id");
    mysqli_query($conn, "DELETE FROM invoices WHERE invoice_id = $invoice_id");

    mysqli_commit($conn);
    echo "Success! Invoice deleted and stock reverted.\n";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage() . "\n";
}
