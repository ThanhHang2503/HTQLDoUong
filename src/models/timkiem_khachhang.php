<?php

$sql = "select * from customers
        where customer_name like '%" . trim($_GET['timkiem-khachhang']) . "%'";
$result = mysqli_query($conn, $sql);
$ds_kh_timkiem = mysqli_fetch_all($result);
