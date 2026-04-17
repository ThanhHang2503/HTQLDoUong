<?php
$conn = mysqli_connect("localhost", "root", "", "eldercoffee_db");
$res = mysqli_query($conn, "DESCRIBE invoices");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
mysqli_close($conn);
