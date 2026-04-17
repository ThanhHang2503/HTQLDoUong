<?php
require 'config.php';
$tables = [
    'items', 
    'inventory_receipts', 
    'inventory_receipt_items', 
    'invoices', 
    'invoice_details', 
    'inventory_exports', 
    'inventory_export_items'
];

foreach ($tables as $t) {
    echo "--- Table: $t ---\n";
    $res = mysqli_query($conn, "DESCRIBE $t");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Table does not exist.\n";
    }
    
    // Check foreign keys referencing this table
    $sql_fk = "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
               FROM information_schema.KEY_COLUMN_USAGE
               WHERE REFERENCED_TABLE_NAME = '$t' AND TABLE_SCHEMA = DATABASE()";
    $res_fk = mysqli_query($conn, $sql_fk);
    if ($res_fk && mysqli_num_rows($res_fk) > 0) {
        echo "Referenced by:\n";
        while ($row = mysqli_fetch_assoc($res_fk)) {
            echo "  - {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} via {$row['CONSTRAINT_NAME']}\n";
        }
    }
    echo "\n";
}
