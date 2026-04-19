<?php
/**
 * Test Year-based Age Validation
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/hr/Models.php';
require_once __DIR__ . '/../src/hr/Repositories.php';
require_once __DIR__ . '/../src/hr/Services.php';

use HR\Services\EmployeeService;

global $conn;
$empService = new EmployeeService($conn);

function testAge($birth_date, $scenario) {
    global $empService;
    echo "Scenario: $scenario ($birth_date)\n";
    $res = $empService->updateEmployee(2, ['birth_date' => $birth_date]); 
    if ($res['success']) {
        echo "RESULT: SUCCESS\n";
    } else {
        echo "RESULT: FAILED - " . $res['error'] . "\n";
    }
    echo "-------------------\n";
}

$currentYear = (int)date('Y');
$eligibleYear = $currentYear - 18;
$ineligibleYear = $currentYear - 17;

testAge("$eligibleYear-12-31", "Born in year $eligibleYear (End of year)");
testAge("$eligibleYear-01-01", "Born in year $eligibleYear (Start of year)");
testAge("$ineligibleYear-01-01", "Born in year $ineligibleYear (Start of year)");
testAge("$ineligibleYear-12-31", "Born in year $ineligibleYear (End of year)");
