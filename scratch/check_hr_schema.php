<?php
require 'config.php';
$tables = ['accounts', 'salary_records', 'resignation_requests'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $q = mysqli_query($conn, "DESCRIBE $table");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            echo "{$row['Field']} ({$row['Type']}) - {$row['Null']} - {$row['Key']}\n";
        }
    } else {
        echo "Table does not exist.\n";
    }
}
?>
