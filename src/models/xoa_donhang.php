<?php
// Deletion is disabled to maintain inventory and financial record integrity.
// requirePermission(AppPermission::ADMIN); // If we wanted only admins to do it

setNotify('warning', 'Hệ thống không cho phép xóa hóa đơn để đảm bảo tính toàn vẹn của dữ liệu tồn kho và báo cáo.');

redirect('user_page.php?donhang');
exit;
?>