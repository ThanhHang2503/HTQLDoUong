<?php
require 'config.php';
$sql = "ALTER TABLE accounts ADD COLUMN resignation_date DATE NULL AFTER hire_date";
if (mysqli_query($conn, $sql)) {
    echo "Successfully added resignation_date column to accounts table.\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}
?>
