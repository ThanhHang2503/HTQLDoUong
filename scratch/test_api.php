<?php
require 'config.php';
require 'src/hr/Models.php';
require 'src/hr/Repositories.php';
require 'src/hr/Services.php';

use HR\Services\EmployeeService;

$service = new EmployeeService($conn);
$aid = 13;

// Find which position the user STOPS at currently
$q = mysqli_query($conn, "SELECT position_id FROM accounts WHERE account_id = 13");
$curr = (int)mysqli_fetch_assoc($q)['position_id'];
$next_pos = ($curr == 3) ? 4 : 3;

echo "Current Position: $curr, Attempting to promote to: $next_pos\n";
echo "--- TEST: Valid future month (2026-08-01) ---\n";
echo json_encode($service->changePosition($aid, $next_pos, '2026-08-01', "Test successful promotion"), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
?>
