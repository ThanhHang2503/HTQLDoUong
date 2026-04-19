<?php
require_once __DIR__ . '/../config.php';
global $conn;

$res = mysqli_query($conn, "SHOW TRIGGERS");
if (mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo "Trigger: " . $row['Trigger'] . "\n";
        echo "Table: " . $row['Table'] . "\n";
        echo "Statement: " . $row['Statement'] . "\n";
        echo "-------------------\n";
    }
} else {
    echo "No triggers found.\n";
}
?>
