<?php
require __DIR__ . '/../../config.php';
$orderBy = "customer_name ASC";
switch ($_GET['khachhang']) {
    case 'tang_dan': $orderBy = "customer_name ASC"; break;
    case 'giam_dan': $orderBy = "customer_name DESC"; break;
    case 'id_tang':  $orderBy = "customer_id ASC"; break;
    case 'id_giam':  $orderBy = "customer_id DESC"; break;
    case 'ngay_tang': $orderBy = "created_at ASC"; break;
    case 'ngay_giam': $orderBy = "created_at DESC"; break;
}

$sql = "SELECT * FROM customers ORDER BY $orderBy";
$result = mysqli_query($conn, $sql);
$ds_kh_sapxep = mysqli_fetch_all($result, MYSQLI_ASSOC);


require_once __DIR__ . '/../views/khachhang.php';
