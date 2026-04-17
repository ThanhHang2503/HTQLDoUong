<?php
$conn = mysqli_connect("localhost", "root", "", "eldercoffee_db");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$ids = [16, 17, 18];
$ids_str = implode(',', $ids);

$sql = "DELETE FROM customers WHERE customer_id IN ($ids_str)";
if (mysqli_query($conn, $sql)) {
    echo "Deleted customers $ids_str successfully.\n";
} else {
    echo "Error deleting: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
