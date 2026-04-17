<?php
require_once __DIR__ . '/../config.php';

// Giả lập ngày lọc mặc định
$currentYear = date('Y');
$start_date = $currentYear . '-01-01';
$end_date_string = $currentYear . '-12-31';
$end_date_obj = new DateTime($end_date_string);
$end_date_obj->modify('+1 day');
$end_date = $end_date_obj->format('Y-m-d');

echo "Checking Top 5 Customers Query...\n";
echo "Date Range: $start_date to $end_date\n";

$top_customers_sql = "SELECT c.customer_id, c.customer_name, c.phone_number, c.email,
                        COUNT(iv.invoice_id) AS total_orders,
                        SUM(iv.total) AS total_spent
                     FROM invoices iv
                     JOIN customers c ON iv.customer_id = c.customer_id
                     WHERE iv.creation_time >= ? AND iv.creation_time < ?
                     GROUP BY c.customer_id
                     ORDER BY total_spent DESC
                     LIMIT 5";

$stmt6 = $conn->prepare($top_customers_sql);
$stmt6->bind_param('ss', $start_date, $end_date);
$stmt6->execute();
$result = $stmt6->get_result();

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " customers:\n";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "NO CUSTOMERS FOUND in this range.\n";
    // Kiểm tra xem có hóa đơn nào không
    $check_iv = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM invoices");
    $cnt_iv = mysqli_fetch_assoc($check_iv)['cnt'];
    echo "Total invoices in DB: $cnt_iv\n";
    
    // Kiểm tra xem có khách hàng nào không
    $check_c = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM customers");
    $cnt_c = mysqli_fetch_assoc($check_c)['cnt'];
    echo "Total customers in DB: $cnt_c\n";
}
?>
