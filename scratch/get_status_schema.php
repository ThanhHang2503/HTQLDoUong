<?php
$conn = mysqli_connect('localhost', 'root', '', 'eldercoffee_db');
$r = mysqli_query($conn, 'SHOW CREATE TABLE employee_status_history');
$row = mysqli_fetch_assoc($r);
echo $row['Create Table'];
