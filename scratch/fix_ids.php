<?php
require_once __DIR__ . '/../config.php';

mysqli_begin_transaction($conn);
try {
    // 1. Delete references in stock_movements
    echo "Deleting movements for ID 1, 2...\n";
    mysqli_query($conn, "DELETE FROM stock_movements WHERE reference_type = 'invoice' AND reference_id IN (1, 2)");

    // 2. Delete details
    echo "Deleting details for ID 1, 2...\n";
    mysqli_query($conn, "DELETE FROM invoice_details WHERE invoice_id IN (1, 2)");

    // 3. Delete invoices
    echo "Deleting invoices 1, 2...\n";
    mysqli_query($conn, "DELETE FROM invoices WHERE invoice_id IN (1, 2)");

    // Disable foreign key checks for ID reassignment
    mysqli_query($conn, "SET foreign_key_checks = 0");

    // 4. Update ID 3 to 1
    echo "Reassigning invoice ID 3 to 1...\n";
    mysqli_query($conn, "UPDATE invoices SET invoice_id = 1 WHERE invoice_id = 3");
    mysqli_query($conn, "UPDATE invoice_details SET invoice_id = 1 WHERE invoice_id = 3");
    
    // Update movement reference and note
    mysqli_query($conn, "UPDATE stock_movements SET reference_id = 1, note = 'Bán hàng (Số HĐ: 1)' WHERE reference_type = 'invoice' AND reference_id = 3");

    mysqli_query($conn, "SET foreign_key_checks = 1");

    // 5. Reset AUTO_INCREMENT
    mysqli_query($conn, "ALTER TABLE invoices AUTO_INCREMENT = 2");

    mysqli_commit($conn);
    echo "Success! Invoices 1 & 2 deleted. Invoice 3 became 1. Auto-increment reset to 2.\n";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "ERROR: " . $e->getMessage() . "\n";
}
