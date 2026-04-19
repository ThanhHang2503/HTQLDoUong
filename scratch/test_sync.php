<?php
require 'config.php';
require 'src/models/functions.php';
require 'src/hr/Services.php';
require 'src/hr/Models.php';
require 'src/hr/Repositories.php';

use HR\Services\EmployeeService;

global $conn;
$service = new EmployeeService($conn);

echo "Testing syncStatusHistory for account_id 2...\n";
try {
    $service->syncStatusHistory(2);
    echo "Success calling syncStatusHistory(2)\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
