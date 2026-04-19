<?php
/**
 * Test Validation for Employee Birth Date and Phone
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/hr/Models.php';
require_once __DIR__ . '/../src/hr/Repositories.php';
require_once __DIR__ . '/../src/hr/Services.php';

use HR\Services\EmployeeService;

global $conn;
$empService = new EmployeeService($conn);

function testValidate($data, $scenario) {
    global $empService;
    echo "\nScenario: $scenario\n";
    // Using updateEmployee for testing validation
    $res = $empService->updateEmployee(2, $data); 
    if ($res['success']) {
        echo "SUCCESS (Unexpected if testing invalid data)\n";
    } else {
        echo "FAILED: " . $res['error'] . "\n";
    }
}

// 1. Phone number with letters
testValidate(['phone' => '090abc1234'], "Phone with letters");

// 2. Phone number too short
testValidate(['phone' => '12345'], "Phone too short");

// 3. Birth date in future
testValidate(['birth_date' => date('Y-m-d', strtotime('+1 day'))], "Birth date in future");

// 4. Birth date (17 years old)
testValidate(['birth_date' => date('Y-m-d', strtotime('-17 years'))], "Birth date (17 years old)");

// 5. Valid data
// testValidate(['phone' => '0987654321', 'birth_date' => '2000-01-01'], "Valid data");
