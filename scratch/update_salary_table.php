<?php
require 'config.php';
global $conn;

$sql = "ALTER TABLE salary_records ADD COLUMN status ENUM('draft', 'finalized') NOT NULL DEFAULT 'draft' AFTER notes";

if (mysqli_query($conn, $sql)) {
    echo "Column 'status' added successfully to salary_records.\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
