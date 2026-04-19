<?php
require 'config.php';
global $conn;
$res = mysqli_query($conn, "SHOW CREATE PROCEDURE CalculateEmployeeSalary");
$row = mysqli_fetch_assoc($res);
echo $row['Create Procedure'] . "\n";
