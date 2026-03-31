<?php
@include('config.php');
@include('src/models/functions.php');
checkAdmin();

$sql = "select * from accounts";
$ds_taikhoan =  mysqli_query($conn, $sql);
$ds_taikhoan = mysqli_fetch_all($ds_taikhoan);

$statusMessage = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'deleted') {
        $statusMessage = '<div class="alert alert-success">Xóa nhân sự thành công.</div>';
    } elseif ($_GET['status'] === 'reactivated') {
        $statusMessage = '<div class="alert alert-success">Tài khoản đã được kích hoạt lại.</div>';
    } elseif ($_GET['status'] === 'deactivated') {
        $statusMessage = '<div class="alert alert-success">Tài khoản đã lập hóa đơn đã được ngừng hoạt động (không xóa cứng).</div>';
    } elseif ($_GET['status'] === 'has_invoices') {
        $statusMessage = '<div class="alert alert-warning">Không thể xóa tài khoản đã lập hóa đơn.</div>';
    } elseif ($_GET['status'] === 'self_delete') {
        $statusMessage = '<div class="alert alert-warning">Bạn không thể tự xóa tài khoản đang đăng nhập.</div>';
    } elseif ($_GET['status'] === 'invalid_id') {
        $statusMessage = '<div class="alert alert-danger">ID nhân sự không hợp lệ.</div>';
    } elseif ($_GET['status'] === 'not_found') {
        $statusMessage = '<div class="alert alert-info">Không tìm thấy nhân sự để xóa.</div>';
    } elseif ($_GET['status'] === 'error') {
        $statusMessage = '<div class="alert alert-danger">Có lỗi khi xóa nhân sự. Vui lòng thử lại.</div>';
    }
}
?>
<div class="dash_board px-2">
    <h1 class="head-name">NHÂN SỰ</h1>
    <div class="head-line"></div>
    <div class="container-fluid">
        <?= $statusMessage ?>
        <?php if (isset($_SESSION['admin_id'])) : ?>
            <div class="text-end">

                <a href="user_page.php?nhansu=them" class="my-2 btn btn-success fw-bolder"><i class="fa-solid fa-file-circle-plus"></i> Thêm users</a>
            </div>
        <?php endif; ?>
        <!-- HIEN THI BANG NHAN SU  -->
        <table id="myTable" class="table container-fluid text-center table-hover table-striped table-bordered">
            <tr>
                <!-- <th>Mã số</th> -->
                <th>ID <i href="" class=" fw-bolder"></i></i></th>
                <th onclick="sortTable(1)">Tên <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></i></th>
                <th onclick="sortTable(2)">email <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></i></th>
                <?php
                if (isset($_SESSION['admin_id']))
                    echo "<th>Thao tác</th>";
                ?>
                <th>Quyền</th>
            </tr>
            <?php foreach ($ds_taikhoan as $tk) : ?>
                <tr>
                    <td><?= $tk[0] ?></td>
                    <td><?= $tk[1] ?></td>
                    <td><?= $tk[2] ?></td>
                    <?php
                    $isInactive = isset($tk[4]) && $tk[4] === 'inactive';
                    if (isset($_SESSION['admin_id']) && !$isInactive) {
                        echo '<td><a href="user_page.php?nhansu=sua&id=' . $tk[0] . '"><i class="btn btn-outline-success fa-solid fa-pen"></i> </a>
                        <a href="user_page.php?nhansu=xoa&id=' . $tk[0] . '" onclick="return confirm(\'Bạn chắc chắn muốn xóa nhân sự này?\')"><i class="btn btn-outline-danger fa-solid fa-trash"></i></a></td>';
                    } elseif (isset($_SESSION['admin_id']) && $isInactive) {
                        echo '<td>
                        <span class="badge text-bg-secondary">Đã ngừng hoạt động</span>
                        <a class="btn btn-outline-primary ms-2" href="user_page.php?nhansu=khoiphuc&id=' . $tk[0] . '" onclick="return confirm(\'Bạn muốn kích hoạt lại tài khoản này?\')">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        </td>';
                    } else

                    ?>
                    <td><?= $tk[4] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

    </div>
</div>
</div>

</div>
</div>