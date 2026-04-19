<?php
/**
 * Test script for Salary Calculation Standardization
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

function testCalc($accountId, $month, $year, $scenario) {
    global $salService;
    echo "\nScenario: $scenario\n";
    $result = $salService->calculateSalary($accountId, $month, $year);
    if ($result['success']) {
        echo "Actual Active Days: " . $result['actual_active_days'] . "\n";
        echo "Effective Days (Standardized): " . $result['effective_days'] . "\n";
        echo "Base Salary: " . number_format($result['base_salary']) . "\n";
        echo "Pro-rated Salary: " . number_format($result['pro_rated_base']) . "\n";
    } else {
        echo "Error: " . $result['error'] . "\n";
    }
}

// Test với account_id = 4 (Lê Thị Mai) - Giả sử là tháng 4/2026
// Lê Thị Mai có hire_date = 2026-04-10 (theo dữ liệu cũ)
testCalc(4, 4, 2026, "Lê Thị Mai - Tháng 4/2026 (Tháng vào làm)");

// Test với account_id = 2 (Nguyễn Văn An)
testCalc(2, 4, 2026, "Nguyễn Văn An - Tháng 4/2026 (Làm đủ tháng)");
