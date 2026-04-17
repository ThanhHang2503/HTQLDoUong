<?php
// Chức năng xóa loại sản phẩm đã bị vô hiệu hóa.
$_SESSION['loai_error'] = 'Không được phép xóa loại sản phẩm.';
header('location:user_page.php?loai');
exit;
