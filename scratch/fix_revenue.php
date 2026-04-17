<?php
include 'config.php';

$sql = "UPDATE invoice_details id 
        JOIN items i ON i.item_id = id.item_id 
        SET id.unit_price = i.unit_price 
        WHERE id.unit_price = 0";
$ok = mysqli_query($conn, $sql);
if ($ok) {
    echo "Fixed details: " . mysqli_affected_rows($conn) . "\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
