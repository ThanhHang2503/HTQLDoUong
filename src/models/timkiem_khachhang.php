<?php

$keyword = mysqli_real_escape_string($conn, trim($_GET['timkiem-khachhang']));
$sql = "select * from customers
        where customer_name like '%" . $keyword . "%'";
$result = mysqli_query($conn, $sql);
$ds_kh_timkiem = mysqli_fetch_all($result);
