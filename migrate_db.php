<?php
require "config.php";
global $conn;

echo "Starting migration...\n";

// 1. Re-align positions table to exactly match Role IDs
// Role IDs: 1: admin, 2: manager, 3: sales, 4: warehouse
$positions_data = [
    [1, 'Quản trị viên', 20000000, 'Quản lý cấp cao hệ thống'],
    [2, 'Quản lý', 15000000, 'Quản lý toàn bộ hoạt động cửa hàng/nhân sự'],
    [3, 'Nhân viên bán hàng', 8000000, 'Tư vấn và phục vụ đồ uống cho khách hàng'],
    [4, 'Nhân viên kho', 7500000, 'Quản lý nhập xuất vật tư và kiểm kê hàng hóa']
];

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0;");
mysqli_query($conn, "TRUNCATE TABLE positions;");

foreach ($positions_data as $row) {
    $sql = "INSERT INTO positions (position_id, position_name, base_salary, description) VALUES ({$row[0]}, '{$row[1]}', {$row[2]}, '{$row[3]}')";
    mysqli_query($conn, $sql);
}
echo "Positions table refreshed.\n";

// 2. Synchronize existing accounts: sets position_id = role_id
mysqli_query($conn, "UPDATE accounts SET position_id = role_id WHERE role_id IN (1, 2, 3, 4)");
echo "Accounts table synchronized.\n";

// 3. Refresh position history for a clean start
mysqli_query($conn, "TRUNCATE TABLE employee_positions_history;");
$res = mysqli_query($conn, "SELECT account_id, position_id, hire_date FROM accounts");
while($row = mysqli_fetch_assoc($res)) {
    $aid = $row['account_id'];
    $pid = $row['position_id'];
    if (!$pid) continue;
    $hdate = $row['hire_date'] ?: date('Y-m-d');
    $sql = "INSERT INTO employee_positions_history (account_id, position_id, start_date, reason, created_by) 
            VALUES ($aid, $pid, '$hdate', 'Đồng bộ hóa hệ thống (Vai trò = Chức vụ)', 1)";
    mysqli_query($conn, $sql);
}
echo "Employee history refreshed.\n";

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1;");
echo "Migration complete.\n";
