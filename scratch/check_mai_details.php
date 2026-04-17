<?php
require_once dirname(__DIR__) . '/config.php';
$aid = 10; // Lê Thị Mai
$res = mysqli_query($conn, "SELECT a.*, p.position_name, p.base_salary FROM accounts a 
                             JOIN positions p ON p.position_id = a.position_id
                             WHERE a.account_id = $aid");
$mai = mysqli_fetch_assoc($res);
echo "Current Position: " . $mai['position_name'] . " (Salary: " . number_format($mai['base_salary']) . ")\n";
echo "Hire Date: " . $mai['hire_date'] . "\n";

$hRes = mysqli_query($conn, "SELECT * FROM employee_positions_history WHERE account_id = $aid ORDER BY start_date ASC");
echo "History:\n";
while ($h = mysqli_fetch_assoc($hRes)) {
    echo "ID: " . $h['history_id'] . " | Pos: " . $h['position_id'] . " | Start: " . $h['start_date'] . " | End: " . ($h['end_date'] ?: 'Present') . "\n";
}
