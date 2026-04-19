<?php
require 'config.php';
require 'src/hr/Models.php';
require 'src/hr/Repositories.php';
require 'src/hr/Services.php';

use HR\Services\ResignationService;

$service = new ResignationService($conn);
$aid = 13;

// 1. Create a dummy resignation request
$effective_date = '2026-06-15';
$sql = "INSERT INTO resignation_requests (account_id, notice_date, effective_date, reason, status) 
        VALUES ($aid, CURDATE(), '$effective_date', 'Testing approval logic', 'chờ duyệt')";
mysqli_query($conn, $sql);
$request_id = mysqli_insert_id($conn);

echo "Created request ID: $request_id\n";

// 2. Approve it
$result = $service->approveResignation($request_id, 1); // Approved by admin
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

// 3. Verify account status
$q = mysqli_query($conn, "SELECT hr_status, system_status, resignation_date FROM accounts WHERE account_id = $aid");
$status = mysqli_fetch_assoc($q);
echo "Final Account Status:\n";
print_r($status);

// Cleanup
mysqli_query($conn, "DELETE FROM resignation_requests WHERE resignation_request_id = $request_id");
mysqli_query($conn, "UPDATE accounts SET hr_status='active', system_status='active', resignation_date=NULL WHERE account_id = $aid");
?>
