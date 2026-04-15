<?php
require 'config.php';
global $conn;
$res = mysqli_query($conn, 'SHOW CREATE TABLE leave_requests');
$row = mysqli_fetch_row($res);
echo $row[1];
