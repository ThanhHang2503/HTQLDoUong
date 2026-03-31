<?php

$sql = 'delete from invoice_details where invoice_id = ' . $_GET['id'];
mysqli_query($conn,$sql);

$sql = 'delete from invoices where invoice_id = ' . $_GET['id'];
mysqli_query($conn,$sql);

$_SESSION['xoa_don_hang_thanh_cong'] = 'Bạn đã xóa đơn hàng thành công';

header('Location: user_page.php?donhang');

?>