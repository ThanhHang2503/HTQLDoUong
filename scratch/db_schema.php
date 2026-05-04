<?php
require 'config.php';
$res = mysqli_query($conn, 'SHOW TABLES');
while ($row = mysqli_fetch_row($res)) {
    $table = $row[0];
    echo "TABLE: $table\n";
    $res2 = mysqli_query($conn, "DESCRIBE $table");
    while ($col = mysqli_fetch_assoc($res2)) {
        echo "  " . $col['Field'] . " - " . $col['Type'] . "\n";
    }
}
