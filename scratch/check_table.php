<?php
require 'config.php';
global $conn;
$res = mysqli_query($conn, "DESCRIBE employee_status_history");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
