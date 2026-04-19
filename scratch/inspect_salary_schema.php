<?php
require 'config.php';
global $conn;
$res = mysqli_query($conn, "DESCRIBE salary_records");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
