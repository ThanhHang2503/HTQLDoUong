<?php
require_once dirname(__DIR__) . '/config.php';
$aid = 10; // Lê Thị Mai

// Re-import the logic from bangluong.php
function getProRatedBaseSalary_Final($conn, $accountId, $month, $year, $hireDate = null) {
    if (!$hireDate) {
        $hireSql = "SELECT hire_date FROM accounts WHERE account_id = $accountId";
        $hireRes = mysqli_query($conn, $hireSql);
        $hireData = mysqli_fetch_assoc($hireRes);
        $hireDate = $hireData['hire_date'] ?? '2000-01-01';
    }
    
    $startOfMonth = sprintf("%04d-%02d-01", $year, $month);
    $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
    $totalDaysInMonth = (int)date('t', strtotime($startOfMonth));

    $unpaidLeaveDates = []; // assume none
    $historySql = "SELECT eph.start_date, eph.end_date, p.base_salary, p.position_id
                   FROM employee_positions_history eph
                   JOIN positions p ON p.position_id = eph.position_id
                   WHERE eph.account_id = $accountId
                   AND eph.start_date <= '$endOfMonth'
                   AND (eph.end_date IS NULL OR eph.end_date >= '$startOfMonth')
                   ORDER BY eph.start_date ASC";
    $histRes = mysqli_query($conn, $historySql);
    $history = $histRes ? mysqli_fetch_all($histRes, MYSQLI_ASSOC) : [];

    $workingDaysByPos = []; 
    $totalWorkingDays = 0;

    for ($d = 1; $d <= $totalDaysInMonth; $d++) {
        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $d);
        $ts = strtotime($dateStr);
        $currentPos = null;
        foreach ($history as $h) {
            $sTs = strtotime($h['start_date']);
            $eTs = $h['end_date'] ? strtotime($h['end_date']) : strtotime('2099-12-31');
            if ($ts >= $sTs && $ts <= $eTs) {
                $currentPos = $h;
                break;
            }
        }

        if ($currentPos && !isset($unpaidLeaveDates[$dateStr])) {
            $sal = (int)$currentPos['base_salary'];
            $pid = (int)$currentPos['position_id'];
            if (!isset($workingDaysByPos[$pid])) {
                $workingDaysByPos[$pid] = ['days' => 0, 'base_salary' => $sal, 'position_id' => $pid];
            }
            $workingDaysByPos[$pid]['days']++;
            $totalWorkingDays++;
        } elseif (!$currentPos && !isset($unpaidLeaveDates[$dateStr]) && $ts >= strtotime($hireDate)) {
            if (!empty($history)) {
                $firstPos = $history[0];
                $sal = (int)$firstPos['base_salary'];
                $pid = (int)$firstPos['position_id'];
                if (!isset($workingDaysByPos[$pid])) {
                    $workingDaysByPos[$pid] = ['days' => 0, 'base_salary' => $sal, 'position_id' => $pid];
                }
                $workingDaysByPos[$pid]['days']++;
                $totalWorkingDays++;
            }
        }
    }

    $finalBaseSalary = 0;
    foreach ($workingDaysByPos as $pos) {
        $finalBaseSalary += ($pos['days'] * $pos['base_salary'] / 30);
    }
    return round($finalBaseSalary);
}

// Check ID 10
$res = mysqli_query($conn, "SELECT hire_date FROM accounts WHERE account_id = 10");
$mai = mysqli_fetch_assoc($res);
$calc = getProRatedBaseSalary_Final($conn, 10, 4, 2026, $mai['hire_date']);

echo "Lê Thị Mai April 2026 Salary: " . number_format($calc) . " VND\n";
echo "Expected: ~13,133,333 VND\n";
