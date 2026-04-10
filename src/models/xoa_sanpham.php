<?php

if(isset($_GET['sanpham']) && $_GET['sanpham']=='xoa'){
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        $_SESSION['product_delete_error'] = 'Sản phẩm không hợp lệ.';
        header('location:user_page.php?sanpham');
        exit;
    }

    try {
        $sql = "DELETE FROM items WHERE item_id = {$id}";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $_SESSION['product_delete_success'] = 'Xóa sản phẩm thành công.';
        } else {
            $_SESSION['product_delete_error'] = 'Không thể xóa sản phẩm.';
        }
    } catch (\Throwable $e) {
        $_SESSION['product_delete_error'] = 'Không thể xóa sản phẩm vì đang phát sinh dữ liệu nhập/xuất/đơn hàng liên quan.';
    }

    header('location:user_page.php?sanpham');
    exit;
    
}

?>
