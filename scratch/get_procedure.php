<?php
require 'config.php';
global $conn;
$res = mysqli_query($conn, 'SHOW CREATE PROCEDURE CalculateEmployeeSalary');
if ($res) {
    $row = mysqli_fetch_row($res);
    echo $row[2];
} else {
    echo "Procedure not found or error: " . mysqli_error($conn);
}
