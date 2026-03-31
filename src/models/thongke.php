<?php

require __DIR__ . '/../../config.php';

$start_date = '';
$end_date_string = '';
$end_date = '';
$ds_hoadon = [];
$tong_doanhthu = [];
$total_revenue = 0;
$customer_sales_rows = [];
$mon_chay_rows = [];
$name_best_staff_rows = [];

if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
	$start_date = $_POST['start_date'];
	$end_date_string = $_POST['end_date'];
} else {
	$currentYear = date('Y');
	$start_date = $currentYear . '-01-01';
	$end_date_string = $currentYear . '-12-31';
}

try {
	if (!empty($start_date) && !empty($end_date_string)) {
		$end_date_obj = new DateTime($end_date_string);
		$end_date_obj->modify('+1 day');
		$end_date = $end_date_obj->format('Y-m-d');

		if ($start_date > $end_date) {
			echo "<script>alert('Ngày bắt đầu phải nhỏ hơn ngày kết thúc!');</script>";
		} else {
			if ($conn->connect_error) {
				die('Kết nối thất bại: ' . $conn->connect_error);
			}

			$invoice_sql = "SELECT iv.invoice_id, iv.creation_time, ct.customer_name, ac.full_name, iv.total
							FROM invoices iv
							LEFT JOIN customers ct ON iv.customer_id = ct.customer_id
							LEFT JOIN accounts ac ON iv.account_id = ac.account_id
							WHERE iv.creation_time >= ? AND iv.creation_time < ?
							ORDER BY iv.creation_time DESC";
			$stmt = $conn->prepare($invoice_sql);
			$stmt->bind_param('ss', $start_date, $end_date);
			$stmt->execute();
			$ds_hoadon = $stmt->get_result();
			$stmt->close();

			$revenue_sql = "SELECT COALESCE(SUM(total), 0) AS total_revenue
							FROM invoices
							WHERE creation_time >= ? AND creation_time < ?";
			$stmt2 = $conn->prepare($revenue_sql);
			$stmt2->bind_param('ss', $start_date, $end_date);
			$stmt2->execute();
			$tong_doanhthu = $stmt2->get_result();
			if ($tong_doanhthu->num_rows > 0) {
				$row = $tong_doanhthu->fetch_assoc();
				$total_revenue = $row['total_revenue'];
			}
			$stmt2->close();

			$customer_sql = "SELECT c.customer_name, COUNT(*) AS invoice_count
							 FROM invoices i
							 JOIN customers c ON i.customer_id = c.customer_id
							 WHERE i.creation_time >= ? AND i.creation_time < ?
							 GROUP BY c.customer_id, c.customer_name
							 HAVING COUNT(*) = (
								SELECT MAX(t.cnt)
								FROM (
									SELECT COUNT(*) AS cnt
									FROM invoices i2
									WHERE i2.creation_time >= ? AND i2.creation_time < ?
									GROUP BY i2.customer_id
								) t
							 )";
			$stmt3 = $conn->prepare($customer_sql);
			$stmt3->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
			$stmt3->execute();
			$customer_sales_rows = $stmt3->get_result();
			if ($customer_sales_rows->num_rows <= 0) {
				$customer_sales_rows = [];
			}
			$stmt3->close();

			$best_item_sql = "SELECT it.item_name AS best_selling_item, SUM(id.quantity) AS total_quantity
							  FROM invoice_details id
							  JOIN invoices iv ON iv.invoice_id = id.invoice_id
							  JOIN items it ON it.item_id = id.item_id
							  WHERE iv.creation_time >= ? AND iv.creation_time < ?
							  GROUP BY it.item_id, it.item_name
							  HAVING SUM(id.quantity) = (
								SELECT MAX(t.total_qty)
								FROM (
									SELECT SUM(id2.quantity) AS total_qty
									FROM invoice_details id2
									JOIN invoices iv2 ON iv2.invoice_id = id2.invoice_id
									WHERE iv2.creation_time >= ? AND iv2.creation_time < ?
									GROUP BY id2.item_id
								) t
							  )";
			$stmt4 = $conn->prepare($best_item_sql);
			$stmt4->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
			$stmt4->execute();
			$mon_chay_rows = $stmt4->get_result();
			if ($mon_chay_rows->num_rows <= 0) {
				$mon_chay_rows = [];
			}
			$stmt4->close();

			$best_staff_sql = "SELECT a.full_name, COUNT(*) AS invoice_count
							   FROM invoices i
							   JOIN accounts a ON i.account_id = a.account_id
							   WHERE i.creation_time >= ? AND i.creation_time < ?
							   GROUP BY a.account_id, a.full_name
							   HAVING COUNT(*) = (
								SELECT MAX(t.cnt)
								FROM (
									SELECT COUNT(*) AS cnt
									FROM invoices i2
									WHERE i2.creation_time >= ? AND i2.creation_time < ?
									GROUP BY i2.account_id
								) t
							   )";
			$stmt5 = $conn->prepare($best_staff_sql);
			$stmt5->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
			$stmt5->execute();
			$name_best_staff_rows = $stmt5->get_result();
			if ($name_best_staff_rows->num_rows <= 0) {
				$name_best_staff_rows = [];
			}
			$stmt5->close();
		}
	}
} catch (Exception $e) {
	echo "<script>alert('Đã xảy ra lỗi: " . $e->getMessage() . "');</script>";
}

