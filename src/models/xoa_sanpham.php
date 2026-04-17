<?php
if (isset($_GET['sanpham']) && $_GET['sanpham'] === 'xoa') {
    $_SESSION['product_delete_error'] = 'Không thể xóa sản phẩm. Hãy chuyển sang trạng thái "Ngừng bán" thay thế.';
    header('location:user_page.php?sanpham');
    exit;
}
?>
