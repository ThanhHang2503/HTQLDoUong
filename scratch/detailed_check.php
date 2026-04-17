<?php
$c = mysqli_connect('localhost', 'root', '', 'eldercoffee_db');
if (!$c) die("Connection failed: " . mysqli_connect_error());

$tables = [];
$res = mysqli_query($c, "SHOW TABLES");
while ($row = mysqli_fetch_row($res)) {
    if (strpos($row[0], 'v_') !== 0) { // skip views
        $tables[] = $row[0];
    }
}

foreach ($tables as $t) {
    $res = mysqli_query($c, "SELECT COUNT(*) FROM `$t` ");
    $row = mysqli_fetch_row($res);
    echo "$t: " . $row[0] . "\n";
}

echo "\n--- ROLES ---\n";
$res = mysqli_query($c, "SELECT * FROM roles");
while ($row = mysqli_fetch_assoc($res)) print_r($row);

echo "\n--- POSITIONS ---\n";
$res = mysqli_query($c, "SELECT * FROM positions");
while ($row = mysqli_fetch_assoc($res)) print_r($row);
?>
