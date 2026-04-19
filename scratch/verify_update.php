<?php
require_once dirname(__DIR__) . '/config.php';

$query = "SELECT account_id, full_name, hire_date FROM accounts LIMIT 5";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['account_id'] . " | Name: " . $row['full_name'] . " | Hire Date: " . $row['hire_date'] . "\n";
}
?>
