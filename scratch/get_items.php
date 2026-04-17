<?php
include 'config.php';
$r = mysqli_query($conn, "SELECT item_id, item_name, unit_price, stock_quantity FROM items WHERE item_status='active' AND stock_quantity > 0 LIMIT 5");
while($row = mysqli_fetch_assoc($r)) {
    print_r($row);
}
