<?php
require 'config.php';
function check_table($conn, $table) {
    echo "--- Checking Table: $table ---\n";
    $res = mysqli_query($conn, "SHOW CREATE TABLE $table");
    if ($res) {
        $row = mysqli_fetch_row($res);
        echo $row[1] . "\n\n";
    } else {
        echo "Table $table not found.\n\n";
    }
}

check_table($conn, 'accounts');
check_table($conn, 'customers');
check_table($conn, 'roles');
check_table($conn, 'positions');
