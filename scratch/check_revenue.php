<?php
require __DIR__ . '/../config.php';
$res = mysqli_query($conn, "
    SELECT DATE(creation_time) as date, SUM(total) as total, COUNT(*) as cnt
    FROM invoices
    WHERE status = 'completed' AND creation_time >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY)
    GROUP BY DATE(creation_time)
    ORDER BY date ASC
");
echo str_pad("Ngày", 12) . str_pad("Tổng doanh thu", 20) . "Số HĐ\n";
echo str_repeat("-", 40) . "\n";
while ($r = mysqli_fetch_assoc($res)) {
    $total = (int)$r['total'];
    $flag = $total < 1000000 ? "🔴" : ($total < 10000000 ? "🟡" : "🟢");
    echo str_pad($r['date'], 12) . str_pad(number_format($total, 0, ',', '.') . " đ", 22) . "{$r['cnt']} HĐ  $flag\n";
}
