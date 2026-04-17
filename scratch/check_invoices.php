<?php
require 'config.php';
$res = mysqli_query($conn, "SHOW CREATE TABLE invoices");
$row = mysqli_fetch_row($res);
echo "INVOICES:\n" . $row[1] . "\n\n";

$res = mysqli_query($conn, "SELECT COUNT(*) FROM invoices");
$row = mysqli_fetch_row($res);
echo "Invoice count: " . $row[0] . "\n";
