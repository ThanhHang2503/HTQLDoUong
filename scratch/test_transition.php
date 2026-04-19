<?php
/**
 * Test Transition Day logic
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/hr/Models.php';
require_once __DIR__ . '/../src/hr/Repositories.php';
require_once __DIR__ . '/../src/hr/Services.php';

use HR\Services\EmployeeService;
use HR\Services\SalaryService;

global $conn;
$empService = new EmployeeService($conn);
$salService = new SalaryService($conn);

$accountId = 2; // Nguyễn Văn An
$today = date('Y-m-d');

echo "Current Status History for #$accountId:\n";
$q = mysqli_query($conn, "SELECT * FROM employee_status_history WHERE account_id = $accountId ORDER BY start_date DESC");
while ($r = mysqli_fetch_assoc($q)) {
    echo "ID: {$r['history_id']}, Status: {$r['status']}, Start: {$r['start_date']}, End: {$r['end_date']}\n";
}

echo "\n--- Simulating transition to 'on_leave' TODAY ---\n";
// Update accounts directly to trigger sync
mysqli_query($conn, "UPDATE accounts SET hr_status = 'on_leave' WHERE account_id = $accountId");
$empService->syncStatusHistory($accountId, $today);

echo "\nStatus History AFTER transition:\n";
$q = mysqli_query($conn, "SELECT * FROM employee_status_history WHERE account_id = $accountId ORDER BY start_date DESC");
while ($r = mysqli_fetch_assoc($q)) {
    echo "ID: {$r['history_id']}, Status: {$r['status']}, Start: {$r['start_date']}, End: {$r['end_date']}\n";
}

echo "\nCalculating Salary for April 2026...\n";
$res = $salService->calculateSalary($accountId, 4, 2026);
echo "Working Days (Until Today): " . $res['actual_active_days'] . "\n";
echo "(Today is " . date('d') . "th. Expected: " . date('d') . " days if they were active since start of month)\n";

// Cleanup
mysqli_query($conn, "UPDATE accounts SET hr_status = 'active' WHERE account_id = $accountId");
mysqli_query($conn, "DELETE FROM employee_status_history WHERE account_id = $accountId AND start_date = '$today' AND status = 'on_leave'");
mysqli_query($conn, "UPDATE employee_status_history SET end_date = NULL WHERE account_id = $accountId AND end_date = '$today' AND status = 'active'");
