<?php

$sql = "select i.*, c.category_name from items i, category c
        where i.category_id = c.category_id and i.item_name like '%" . trim($_GET['timkiem-sanpham']) . "%'
        ORDER BY CASE WHEN i.stock_quantity <= 0 THEN 1 ELSE 0 END ASC, CAST(i.stock_quantity AS UNSIGNED) DESC, i.item_id DESC";
$result = mysqli_query($conn, $sql);
$ds_sp_timkiem = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>