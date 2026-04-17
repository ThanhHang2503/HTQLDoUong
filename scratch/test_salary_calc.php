<?php
/**
 * Test script for split-month salary calculation logic.
 */
require_once dirname(__DIR__) . '/config.php';

// Helper function to create/get a test user
function getTestUser($conn) {
    $email = 'salary_test@example.com';
    $res = mysqli_query($conn, "SELECT account_id FROM accounts WHERE email = '$email'");
    if ($row = mysqli_fetch_assoc($res)) {
        return (int)$row['account_id'];
    }
    
    // Create test user if not exists
    mysqli_query($conn, "INSERT INTO accounts (full_name, email, password, role_id, hr_status) 
                         VALUES ('Test Salary User', '$email', 'nopass', 2, 'active')");
    return (int)mysqli_insert_id($conn);
}

// Helper function to create test positions
function createTestPosition($conn, $name, $salary) {
    mysqli_query($conn, "INSERT INTO positions (position_name, base_salary, is_active) 
                         VALUES ('$name', $salary, 1)");
    return (int)mysqli_insert_id($conn);
}

// The logic from bangluong.php (copied for testing)
function getProRatedBaseSalary_Test($conn, $accountId, $month, $year) {
    $startOfMonth = sprintf("%04d-%02d-01", $year, $month);
    $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
    $totalDaysInMonth = (int)date('t', strtotime($startOfMonth));

    // 1. Unpaid leave (simplified for test)
    $unpaidLeaveDates = []; // Assume no unpaid leave for this test

    // 2. Lịch sử chức vụ
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
        }
    }

    $finalBaseSalary = 0;
    $distinctPositionsCount = count($workingDaysByPos);

    if ($distinctPositionsCount >= 2) {
        foreach ($workingDaysByPos as $pos) {
            $finalBaseSalary += ($pos['days'] * $pos['base_salary'] / 30);
        }
    } elseif ($distinctPositionsCount === 1) {
        $pos = reset($workingDaysByPos);
        if ($totalWorkingDays >= 28) {
            $finalBaseSalary = $pos['base_salary'];
        } else {
            $finalBaseSalary = ($totalWorkingDays * $pos['base_salary'] / 30);
        }
    }

    return [
        'total' => round($finalBaseSalary),
        'working_days' => $totalWorkingDays,
        'positions_count' => $distinctPositionsCount,
        'breakdown' => $workingDaysByPos
    ];
}

// --- START TEST ---
echo "--- SALARY CALCULATION TEST START ---\n";

// 1. Setup Data
$uid = getTestUser($conn);
echo "Test User ID: $uid\n";

// Clear previous history for this user
mysqli_query($conn, "DELETE FROM employee_positions_history WHERE account_id = $uid");

// Create positions
$posA_id = createTestPosition($conn, 'Test Position A', 6000000);
$posB_id = createTestPosition($conn, 'Test Position B', 9000000);
echo "Position A (6M): $posA_id\n";
echo "Position B (9M): $posB_id\n";

// Insert History: Split April 2026 (30 days)
// April 1 to April 15 -> Position A (15 days)
// April 16 to April 30 -> Position B (15 days)
mysqli_query($conn, "INSERT INTO employee_positions_history (account_id, position_id, start_date, end_date) 
                     VALUES ($uid, $posA_id, '2026-04-01', '2026-04-15')");
mysqli_query($conn, "INSERT INTO employee_positions_history (account_id, position_id, start_date, end_date) 
                     VALUES ($uid, $posB_id, '2026-04-16', '2026-04-30')");

// 2. Run Calculation for April 2026
$result = getProRatedBaseSalary_Test($conn, $uid, 4, 2026);

// 3. Verify
echo "Total Working Days: " . $result['working_days'] . "\n";
echo "Positions Count: " . $result['positions_count'] . "\n";
foreach ($result['breakdown'] as $pid => $data) {
    echo " - Position $pid: " . $data['days'] . " days, Base: " . number_format($data['base_salary']) . "\n";
}

$expected = (15 * 6000000 / 30) + (15 * 9000000 / 30);
echo "Final Calculated Salary: " . number_format($result['total']) . " VND\n";
echo "Expected Salary:         " . number_format($expected) . " VND\n";

if (abs($result['total'] - $expected) < 1) {
    echo ">>> RESULT: SUCCESS <<<\n";
} else {
    echo ">>> RESULT: FAILED <<<\n";
}

// 4. Cleanup (Optional, but let's keep the user for manual inspection if needed, but delete history)
// mysqli_query($conn, "DELETE FROM employee_positions_history WHERE account_id = $uid");

echo "--- SALARY CALCULATION TEST END ---\n";
