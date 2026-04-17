<?php
$conn = mysqli_connect("localhost", "root", "", "eldercoffee_db");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$ids = [16, 17, 18];
foreach ($ids as $id) {
    $sql = "SELECT COUNT(*) as count FROM invoices WHERE customer_id = $id";
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($res);
    echo "Customer $id linked to " . $row['count'] . " invoices.\n";
}

mysqli_close($conn);
