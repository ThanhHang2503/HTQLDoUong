<?php
include 'config.php';
$r = mysqli_query($conn, 'DESCRIBE invoice_details');
while($row = mysqli_fetch_assoc($r)) {
    print_r($row);
}
