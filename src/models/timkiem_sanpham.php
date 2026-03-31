<?php

$sql = "select i.*, c.category_name from items i, category c
        where i.category_id = c.category_id and i.item_name like '%" . trim($_GET['timkiem-sanpham']) . "%'";
$result = mysqli_query($conn, $sql);
$ds_sp_timkiem = mysqli_fetch_all($result);
?>