<?php
if (isset($_GET['khachhang']) && $_GET['khachhang'] == 'xoa' && isset($_GET['id'])) {
    $cid = (int)$_GET['id'];

    // Kiểm tra ràng buộc hóa đơn
    $check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM invoices WHERE customer_id = $cid");
    $cnt = mysqli_fetch_assoc($check)['cnt'];

    if ($cnt > 0) {
        setNotify('error', 'Không thể xóa khách hàng này vì đã có hóa đơn liên kết.', 'Lỗi xóa dữ liệu');
    } else {
        $sql = "DELETE FROM customers WHERE customer_id = $cid";
        if (mysqli_query($conn, $sql)) {
            setNotify('success', 'Đã xóa khách hàng thành công.', 'Hoàn tất');
        } else {
            setNotify('error', 'Lỗi hệ thống khi xóa khách hàng.', 'Lỗi');
        }
    }
    redirect('user_page.php?khachhang');
}
