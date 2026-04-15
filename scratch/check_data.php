<?php
require_once 'config.php';
global $conn;

echo "--- POSITIONS ---\n";
$res = mysqli_query($conn, "SELECT * FROM positions");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}


echo "\n--- ACCOUNTS ---\n";
$res = mysqli_query($conn, "SELECT account_id, full_name, role_id, position_id, hr_status FROM accounts");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
