<?php
$kw = isset($_GET['timkiem-donhang']) ? mysqli_real_escape_string($conn, trim($_GET['timkiem-donhang'])) : '';

$sql = "SELECT iv.invoice_id, iv.creation_time, ac.full_name, ct.customer_name, iv.total
	FROM invoices iv
	LEFT JOIN accounts ac ON iv.account_id = ac.account_id
	LEFT JOIN customers ct ON iv.customer_id = ct.customer_id
	WHERE (
        iv.invoice_id LIKE '%$kw%' 
        OR ct.customer_name LIKE '%$kw%' 
        OR ac.full_name LIKE '%$kw%'
    )
	ORDER BY iv.creation_time DESC";
$result = mysqli_query($conn, $sql);
$ds_donhang_timkiem = $result ? mysqli_fetch_all($result) : [];
?>
