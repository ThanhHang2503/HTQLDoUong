<?php
$conn = mysqli_connect('localhost', 'root', '', 'eldercoffee_db');
$ids = [20, 21, 22, 23, 24];
foreach ($ids as $id) {
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM invoices WHERE customer_id = $id");
    $row = mysqli_fetch_assoc($res);
    if ($row['cnt'] == 0) {
        mysqli_query($conn, "DELETE FROM customers WHERE customer_id = $id");
        echo "Deleted customer ID $id\n";
    } else {
        echo "Skipped ID $id (has invoices)\n";
    }
}
?>
