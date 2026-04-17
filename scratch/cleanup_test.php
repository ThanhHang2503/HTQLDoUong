<?php
require_once dirname(__DIR__) . '/config.php';
$email = 'salary_test@example.com';
$res = mysqli_query($conn, "SELECT account_id FROM accounts WHERE email = '$email'");
if ($row = mysqli_fetch_assoc($res)) {
    $aid = $row['account_id'];
    mysqli_query($conn, "DELETE FROM employee_positions_history WHERE account_id = $aid");
    mysqli_query($conn, "DELETE FROM salary_records WHERE account_id = $aid");
    mysqli_query($conn, "DELETE FROM accounts WHERE account_id = $aid");
    echo "Deleted test account ID: $aid\n";
} else {
    echo "Test account not found.\n";
}
// Also delete the temporary positions created in test script
mysqli_query($conn, "DELETE FROM positions WHERE position_name LIKE 'Test Position%'");
echo "Cleaned up test positions.\n";
