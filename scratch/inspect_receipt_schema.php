<?php
require 'config.php';
$tables = ['inventory_receipts', 'inventory_receipt_items'];
foreach ($tables as $t) {
    echo "--- Table: $t ---\n";
    $res = mysqli_query($conn, "DESCRIBE $t");
    while ($row = mysqli_fetch_assoc($res)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\n";
}
