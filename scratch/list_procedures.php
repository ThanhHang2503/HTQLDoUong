<?php
require 'config.php';
$res = mysqli_query($conn, "SHOW PROCEDURES");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        if (strpos($row['Name'], 'Customer') !== false) {
            echo "Found: " . $row['Name'] . "\n";
        }
    }
} else {
    // Try another way to list
    $res = mysqli_query($conn, "SELECT name FROM mysql.proc WHERE db = 'eldercoffee_db' AND type = 'PROCEDURE'");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo "Name: " . $row['name'] . "\n";
        }
    } else {
        echo "Could not list procedures.\n";
    }
}
