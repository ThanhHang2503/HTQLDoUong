<?php
require_once dirname(__DIR__) . '/config.php';
$res = mysqli_query($conn, "SELECT * FROM positions");
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['position_id'] . " | Name: " . $row['position_name'] . " | Salary: " . number_format($row['base_salary']) . "\n";
}
