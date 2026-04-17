<?php
require_once dirname(__DIR__) . '/config.php';
$name = 'Lê Thị Mai';
$res = mysqli_query($conn, "SELECT account_id, full_name, hire_date FROM accounts WHERE full_name LIKE '%$name%'");
while ($row = mysqli_fetch_assoc($res)) {
    echo "Account: " . $row['full_name'] . " (ID: " . $row['account_id'] . "), Hired: " . $row['hire_date'] . "\n";
    $aid = $row['account_id'];
    $hRes = mysqli_query($conn, "SELECT eph.*, p.position_name, p.base_salary 
                                  FROM employee_positions_history eph
                                  JOIN positions p ON p.position_id = eph.position_id
                                  WHERE eph.account_id = $aid
                                  ORDER BY eph.start_date ASC");
    while ($h = mysqli_fetch_assoc($hRes)) {
        echo " - History: " . $h['position_name'] . " (Salary: " . number_format($h['base_salary']) . ") From " . $h['start_date'] . " To " . ($h['end_date'] ?: 'Present') . "\n";
    }
}
