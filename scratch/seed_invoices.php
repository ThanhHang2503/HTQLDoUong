<?php
require __DIR__ . '/../config.php';

// Dữ liệu seed: 7 ngày gần nhất với tổng doanh thu khác nhau
// Ngày 0 = hôm nay, ngày -1 = hôm qua, ...
// Cấu trúc: [ daysAgo => [ [item_id, qty, unit_price], ... ] ]
$seed = [
    // Hôm nay: nhiều đơn, tổng > 10 triệu
    0 => [
        ['date_offset' => 0, 'items' => [[1, 20, 25000], [2, 25, 30000], [3, 15, 35000], [4, 20, 40000], [5, 18, 35000], [6, 15, 45000], [7, 12, 45000], [8, 10, 50000]]],
        ['date_offset' => 0, 'items' => [[9, 10, 55000], [10, 8, 55000], [1, 30, 25000], [2, 20, 30000], [3, 25, 35000], [4, 15, 40000], [5, 20, 35000]]],
        ['date_offset' => 0, 'items' => [[6, 20, 45000], [7, 18, 45000], [8, 15, 50000], [9, 12, 55000], [10, 10, 55000], [1, 25, 25000], [2, 22, 30000]]],
    ],
    // 1 ngày trước: tổng trong khoảng 1-10 triệu
    1 => [
        ['date_offset' => 1, 'items' => [[1, 15, 25000], [2, 12, 30000], [3, 10, 35000], [4, 8, 40000], [5, 10, 35000]]],
        ['date_offset' => 1, 'items' => [[6, 8, 45000], [7, 6, 45000], [8, 5, 50000], [9, 4, 55000], [10, 4, 55000]]],
    ],
    // 2 ngày trước: tổng > 10 triệu
    2 => [
        ['date_offset' => 2, 'items' => [[1, 30, 25000], [2, 28, 30000], [3, 20, 35000], [4, 22, 40000], [5, 25, 35000]]],
        ['date_offset' => 2, 'items' => [[6, 18, 45000], [7, 15, 45000], [8, 12, 50000], [9, 10, 55000], [10, 8, 55000]]],
        ['date_offset' => 2, 'items' => [[1, 20, 25000], [2, 18, 30000], [3, 15, 35000], [4, 12, 40000], [5, 14, 35000], [6, 10, 45000]]],
    ],
    // 3 ngày trước: tổng < 1 triệu (ngày thấp điểm)
    3 => [
        ['date_offset' => 3, 'items' => [[1, 5, 25000], [2, 4, 30000], [3, 3, 35000]]],
    ],
    // 4 ngày trước: tổng trong khoảng 1-10 triệu
    4 => [
        ['date_offset' => 4, 'items' => [[1, 18, 25000], [2, 15, 30000], [3, 12, 35000], [4, 10, 40000]]],
        ['date_offset' => 4, 'items' => [[5, 12, 35000], [6, 10, 45000], [7, 8, 45000], [8, 6, 50000]]],
    ],
    // 5 ngày trước: tổng < 1 triệu
    5 => [
        ['date_offset' => 5, 'items' => [[9, 3, 55000], [10, 3, 55000], [1, 6, 25000]]],
    ],
    // 6 ngày trước: tổng > 10 triệu (ngày cuối tuần sầm uất)
    6 => [
        ['date_offset' => 6, 'items' => [[1, 35, 25000], [2, 30, 30000], [3, 25, 35000], [4, 28, 40000], [5, 30, 35000]]],
        ['date_offset' => 6, 'items' => [[6, 22, 45000], [7, 20, 45000], [8, 18, 50000], [9, 15, 55000], [10, 12, 55000]]],
        ['date_offset' => 6, 'items' => [[1, 25, 25000], [2, 22, 30000], [3, 18, 35000], [4, 15, 40000], [5, 20, 35000], [6, 15, 45000]]],
    ],
];

$accounts = [1, 2, 3];
$customers = [1, 2, 3];
$invoiceCount = 0;

foreach ($seed as $daysAgo => $invoices) {
    foreach ($invoices as $idx => $inv) {
        $offset = $inv['date_offset'];
        // Sinh thời điểm ngẫu nhiên trong ngày đó
        $hour   = rand(8, 21);
        $minute = rand(0, 59);
        $second = rand(0, 59);
        $ts     = date('Y-m-d', strtotime("-{$offset} days")) . " {$hour}:{$minute}:{$second}";

        $account_id  = $accounts[$idx % count($accounts)];
        $customer_id = $customers[$idx % count($customers)];

        // Tính tổng hóa đơn
        $total = 0;
        foreach ($inv['items'] as $it) {
            $total += $it[1] * $it[2];
        }

        // Insert invoice
        $sql = "INSERT INTO invoices (account_id, customer_id, discount, total, status, notes, creation_time)
                VALUES ($account_id, $customer_id, 0, $total, 'completed', 'Seed data - test dashboard', '$ts')";
        if (!mysqli_query($conn, $sql)) {
            echo "ERROR inserting invoice: " . mysqli_error($conn) . "\n";
            continue;
        }
        $invoice_id = mysqli_insert_id($conn);

        // Insert invoice_details
        foreach ($inv['items'] as $it) {
            [$item_id, $qty, $price] = $it;
            $dSql = "INSERT INTO invoice_details (invoice_id, item_id, quantity, unit_price)
                     VALUES ($invoice_id, $item_id, $qty, $price)";
            if (!mysqli_query($conn, $dSql)) {
                echo "ERROR inserting detail: " . mysqli_error($conn) . "\n";
            }
        }
        $invoiceCount++;
        echo "✓ Invoice #$invoice_id | Ngày -$offset | Tổng: " . number_format($total, 0, ',', '.') . " VNĐ | $ts\n";
    }
}

echo "\nHoàn tất! Đã thêm $invoiceCount hóa đơn seed vào database.\n";
