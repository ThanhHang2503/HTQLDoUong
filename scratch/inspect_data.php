<?php
require 'config.php';
header('Content-Type: text/plain; charset=utf-8');

function check_data($conn, $table, $col) {
    echo "--- Table: $table, Column: $col ---\n";
    $res = mysqli_query($conn, "SELECT $col FROM $table LIMIT 10");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $val = $row[$col];
            echo $val . " (Hex: " . bin2hex($val) . ")\n";
        }
    } else {
        echo "Query failed.\n";
    }
    echo "\n";
}

check_data($conn, 'accounts', 'full_name');
check_data($conn, 'customers', 'customer_name');
check_data($conn, 'roles', 'display_name');
