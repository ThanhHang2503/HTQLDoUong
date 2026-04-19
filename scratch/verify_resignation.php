<?php
require 'config.php';
require 'src/hr/Models.php';
require 'src/hr/Repositories.php';
require 'src/hr/Services.php';

use HR\Services\SalaryService;

$service = new SalaryService($conn);
$aid = 13; // Lê Thị Mai

echo "--- Initial Status for user 13 ---\n";
$q = mysqli_query($conn, "SELECT hr_status, system_status, resignation_date FROM accounts WHERE account_id = $aid");
print_r(mysqli_fetch_assoc($q));

echo "\n--- Setting resignation date to 2026-05-10 ---\n";
mysqli_query($conn, "UPDATE accounts SET resignation_date = '2026-05-10' WHERE account_id = $aid");

echo "--- Calculating salary for May 2026 (Resignation month) ---\n";
$calc = $service->calculateSalary($aid, 5, 2026);
echo "Effective Days: " . $calc['effective_days'] . " (Expected: 9)\n";
echo "Pro-rated Base: " . $calc['pro_rated_base'] . "\n";

echo "\n--- Setting resignation date to 2026-05-01 ---\n";
mysqli_query($conn, "UPDATE accounts SET resignation_date = '2026-05-01' WHERE account_id = $aid");
$calc = $service->calculateSalary($aid, 5, 2026);
echo "Effective Days: " . $calc['effective_days'] . " (Expected: 0)\n";

echo "\n--- Setting resignation date to 2026-04-15 (Past month) ---\n";
mysqli_query($conn, "UPDATE accounts SET resignation_date = '2026-04-15' WHERE account_id = $aid");
echo "--- Calculating salary for May 2026 ---\n";
$calc = $service->calculateSalary($aid, 5, 2026);
echo "Effective Days: " . $calc['effective_days'] . " (Expected: 0)\n";

// Reset for next potential tests
mysqli_query($conn, "UPDATE accounts SET resignation_date = NULL WHERE account_id = $aid");
?>
