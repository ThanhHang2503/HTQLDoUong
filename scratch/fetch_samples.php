<?php
$c = mysqli_connect('localhost', 'root', '', 'eldercoffee_db');
if (!$c) die("Connection failed: " . mysqli_connect_error());

$tables = ['suppliers', 'customers', 'items', 'accounts'];
$data = [];
foreach ($tables as $t) {
    echo "--- $t ---\n";
    $res = mysqli_query($c, "SELECT * FROM `$t` LIMIT 10");
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
}
?>
