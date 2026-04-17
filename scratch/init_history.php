<?php
require_once dirname(__DIR__) . '/config.php';

// 1. Employees with NO history at all
$sql_none = "SELECT a.account_id, a.hire_date, a.position_id, a.full_name
             FROM accounts a
             WHERE a.hr_status = 'active'
               AND a.role_id != 1
               AND NOT EXISTS (SELECT 1 FROM employee_positions_history WHERE account_id = a.account_id)";
$res_none = mysqli_query($conn, $sql_none);
$count_none = 0;
while ($row = mysqli_fetch_assoc($res_none)) {
    $aid = $row['account_id'];
    $pid = (int)$row['position_id'];
    $hire = $row['hire_date'] ?: date('Y-m-d');
    $q = "INSERT INTO employee_positions_history (account_id, position_id, start_date, reason) 
          VALUES ($aid, $pid, '$hire', 'Khởi tạo dữ liệu lịch sử tự động')";
    if (mysqli_query($conn, $q)) $count_none++;
}
echo "Initialized history for $count_none employees with NO initial data.\n";

// 2. Employees with GAPS (Hire date earlier than first history)
$sql_gap = "SELECT a.account_id, a.hire_date, a.full_name,
                   (SELECT MIN(start_date) FROM employee_positions_history WHERE account_id = a.account_id) as first_start,
                   (SELECT position_id FROM employee_positions_history WHERE account_id = a.account_id ORDER BY start_date ASC LIMIT 1) as first_pid
            FROM accounts a
            WHERE a.hr_status = 'active'
              AND a.role_id != 1
            HAVING first_start > hire_date";
$res_gap = mysqli_query($conn, $sql_gap);
$count_gap = 0;
while ($row = mysqli_fetch_assoc($res_gap)) {
    $aid = $row['account_id'];
    $pid = (int)$row['first_pid'];
    $hire = $row['hire_date'];
    $end = date('Y-m-d', strtotime($row['first_start'] . ' -1 day'));
    
    if ($hire <= $end) {
        $q = "INSERT INTO employee_positions_history (account_id, position_id, start_date, end_date, reason) 
              VALUES ($aid, $pid, '$hire', '$end', 'Tự động lấp đầy khoảng trống lịch sử từ ngày vào làm')";
        if (mysqli_query($conn, $q)) $count_gap++;
    }
}
echo "Filled gaps for $count_gap employees.\n";
