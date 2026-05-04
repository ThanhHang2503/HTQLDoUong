<?php
require __DIR__ . '/../config.php';

$cur_year = date('Y');

echo "=== TẤT CẢ ĐƠN NGHỈ PHÉP ===\n";
$res = mysqli_query($conn, "SELECT lr.leave_request_id, a.full_name, lr.from_date, lr.to_date, lr.status, lr.leave_type FROM leave_requests lr JOIN accounts a ON a.account_id = lr.account_id ORDER BY lr.from_date");
while ($r = mysqli_fetch_assoc($res)) {
    $days = (strtotime($r['to_date']) - strtotime($r['from_date'])) / 86400 + 1;
    echo "ID#{$r['leave_request_id']} | {$r['full_name']} | {$r['from_date']} → {$r['to_date']} ($days ngày) | Status: {$r['status']} | Loại: {$r['leave_type']}\n";
}

echo "\n=== SỐ NGÀY NGHỈ HỢP LỆ (chấp thuận) THEO THÁNG năm $cur_year ===\n";
$res2 = mysqli_query($conn, "
    SELECT MONTH(from_date) as thang, SUM(DATEDIFF(to_date, from_date) + 1) as tong_ngay, COUNT(*) as so_don
    FROM leave_requests 
    WHERE YEAR(from_date) = $cur_year AND status = 'chấp thuận'
    GROUP BY MONTH(from_date)
    ORDER BY thang
");
if (mysqli_num_rows($res2) == 0) {
    echo "Không có đơn nào status = 'chấp thuận'\n";
} else {
    while ($r = mysqli_fetch_assoc($res2)) {
        echo "Tháng {$r['thang']}: {$r['tong_ngay']} ngày ({$r['so_don']} đơn)\n";
    }
}

echo "\n=== TỔNG HỢP THEO STATUS ===\n";
$res3 = mysqli_query($conn, "SELECT status, COUNT(*) as c FROM leave_requests GROUP BY status");
while ($r = mysqli_fetch_assoc($res3)) {
    echo "'{$r['status']}': {$r['c']} đơn\n";
}
