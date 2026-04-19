<?php
$conn = mysqli_connect('localhost', 'root', '', 'eldercoffee_db');
$r = mysqli_query($conn, 'SELECT * FROM employee_status_history WHERE account_id = 4');
while($row = mysqli_fetch_assoc($r)) print_r($row);
