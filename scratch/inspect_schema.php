<?php
require 'config.php';
global $conn;
$res = mysqli_query($conn, "DESCRIBE accounts");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
echo "\n--- Positions schema ---\n";
$res = mysqli_query($conn, "DESCRIBE positions");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
