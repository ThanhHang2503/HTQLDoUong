<?php
$c = mysqli_connect('localhost', 'root', '', 'eldercoffee_db');
if (!$c) die("Connection failed: " . mysqli_connect_error());

$tables = [];
$res = mysqli_query($c, "SHOW TABLES");
while ($row = mysqli_fetch_row($res)) {
    $tables[] = $row[0];
}

echo "TABLE LIST:\n" . implode(", ", $tables) . "\n\n";

foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    $res = mysqli_query($c, "DESCRIBE `$table` ");
    while ($row = mysqli_fetch_assoc($res)) {
        printf("%-20s | %-15s | %-5s | %-5s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key']);
    }
    echo "\n";
}
?>
