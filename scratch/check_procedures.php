<?php
require 'config.php';
$res = mysqli_query($conn, "SHOW CREATE PROCEDURE SortCustomersAscendingByName");
if ($res) {
    $row = mysqli_fetch_row($res);
    echo "ASC:\n" . $row[2] . "\n\n";
}
$res = mysqli_query($conn, "SHOW CREATE PROCEDURE SortCustomersDescendingByName");
if ($res) {
    $row = mysqli_fetch_row($res);
    echo "DESC:\n" . $row[2] . "\n\n";
}
