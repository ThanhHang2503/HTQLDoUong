<?php
require_once dirname(__DIR__) . '/config.php';
$sql = "SELECT a.account_id, a.full_name, a.hire_date, a.position_id,
               (SELECT MIN(start_date) FROM employee_positions_history WHERE account_id = a.account_id) as first_history_date,
               (SELECT COUNT(*) FROM employee_positions_history WHERE account_id = a.account_id) as history_count
        FROM accounts a
        WHERE a.role_id != 1 AND a.hr_status = 'active'";
$res = mysqli_query($conn, $sql);
echo "Employee Audit:\n";
echo str_pad("ID", 5) . " | " . str_pad("Name", 20) . " | " . str_pad("Hire Date", 12) . " | " . str_pad("First Hist", 12) . " | Status\n";
echo str_repeat("-", 70) . "\n";
while ($row = mysqli_fetch_assoc($res)) {
    $status = "OK";
    if ($row['history_count'] == 0) {
        $status = "MISSING HISTORY";
    } elseif ($row['first_history_date'] > $row['hire_date']) {
        $status = "GAP (Ends: " . $row['first_history_date'] . ")";
    }
    echo str_pad($row['account_id'], 5) . " | " . str_pad($row['full_name'], 20) . " | " . str_pad($row['hire_date'], 12) . " | " . str_pad($row['first_history_date'] ?? 'None', 12) . " | $status\n";
}
