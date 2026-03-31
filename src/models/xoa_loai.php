<?php

if (isset($_GET['loai']) && $_GET['loai'] == 'xoa') {
    try {
        $id = $_GET['id'];
        $sql = "delete from category where category_id=$id";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            echo "<script>alert('Xóa thành công');</script>";
            echo "<script>window.location.href='user_page.php?loai';</script>";
        }
    } catch (Exception $e) {
        echo "<script>confirm('Loại này đã tồn tại trong hóa đơn khác')</script>";
        echo "<script>window.location.href='user_page.php?loai';</script>";
    }
}
