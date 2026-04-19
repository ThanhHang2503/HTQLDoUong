<?php
require 'config.php';
global $conn;

// Helper to map status (duplicate from service for migration)
function mapStatus($hr, $sys) {
    if ($hr === 'resigned') return 'resigned';
    if ($hr === 'on_leave') return 'on_leave';
    if (in_array($sys, ['locked', 'disabled', 'pending'])) return 'locked';
    return 'active';
}

$res = mysqli_query($conn, "SELECT account_id, hr_status, system_status, hire_date, resignation_date FROM accounts");
$count = 0;

while ($acc = mysqli_fetch_assoc($res)) {
    $aid = $acc['account_id'];
    $h_date = $acc['hire_date'] ?: '2020-01-01';
    $r_date = $acc['resignation_date'];
    $status = mapStatus($acc['hr_status'], $acc['system_status']);
    
    // Clear existing history for this account to avoid duplicates during migration
    mysqli_query($conn, "DELETE FROM employee_status_history WHERE account_id = $aid");
    
    if ($status === 'resigned' && $r_date) {
        // Active from hire to resign-1
        $yesterday = date('Y-m-d', strtotime($r_date . ' -1 day'));
        if ($yesterday >= $h_date) {
            mysqli_query($conn, "INSERT INTO employee_status_history (account_id, status, start_date, end_date) VALUES ($aid, 'active', '$h_date', '$yesterday')");
        }
        // Resigned from resign date
        mysqli_query($conn, "INSERT INTO employee_status_history (account_id, status, start_date, end_date) VALUES ($aid, 'resigned', '$r_date', NULL)");
    } else {
        // Just one status since hire
        mysqli_query($conn, "INSERT INTO employee_status_history (account_id, status, start_date, end_date) VALUES ($aid, '$status', '$h_date', NULL)");
    }
    $count++;
}

echo "Migrated $count accounts to employee_status_history.\n";
