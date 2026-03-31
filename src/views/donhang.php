<?php
if (!isset($ds_donhang_timkiem)) {
    $sql = "SELECT iv.invoice_id, iv.creation_time, ac.full_name, ct.customer_name, iv.total
            FROM invoices iv
            LEFT JOIN accounts ac ON iv.account_id = ac.account_id
            LEFT JOIN customers ct ON iv.customer_id = ct.customer_id
            ORDER BY iv.creation_time DESC";
    $result = mysqli_query($conn, $sql);
    $ds_hoadon = $result ? mysqli_fetch_all($result) : [];
} else {
    $ds_hoadon = $ds_donhang_timkiem;
}


?>
<div class="dash_board px-2">
    <h1 class="head-name">ĐƠN HÀNG</h1>

    <?php

    if (isset($_SESSION['tao_don_hang_thanh_cong'])) {
        echo '<h4 class="fw-bolder text-center text-success">' . $_SESSION['tao_don_hang_thanh_cong'] . '</h4>';
        unset($_SESSION['tao_don_hang_thanh_cong']);
    };
    if (isset($_SESSION['xoa_don_hang_thanh_cong'])) {
        echo '<h4 class="fw-bolder text-center text-success">' . $_SESSION['xoa_don_hang_thanh_cong'] . '</h4>';
        unset($_SESSION['xoa_don_hang_thanh_cong']);
    };

    ?>

    <div class="head-line"></div>
    <div class="container-fluid row justify-content-between">
        <form action="user_page.php?" method="GET" class="d-flex col-8 my-2" role="search">
            <input class="form-control me-2" type="text" placeholder="Nhập mã đơn hàng" name='timkiem-donhang' aria-label="Search" required>
            <button class="btn btn-outline-success" type="submit">Tìm</button>
        </form>
        <div class="text-end col-4">
            <a href="user_page.php?donhang=them" class="my-2 btn btn-success fw-bolder"><i class="fa-solid fa-file-circle-plus"></i> Tạo mới hóa đơn</a>
        </div>
        <!-- Bảng hiển thị đơn hàng  -->
        <table id="myTable" class="table container-fluid text-center table-hover table-striped table-bordered">
            <tr>
                <!-- <th>Mã số</th> -->
                <th>ID Hóa đơn </th>
                <th onclick="sortTable(1)">Ngày tạo <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></i></th>
                <th onclick="sortTable(2)">Người lập <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></i></th>
                <th onclick="sortTable(3)">Tên khách <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></th>
                <th>Số tiền <i href="" class=" fw-bolder"></th>
                <th>Thao tác</th>
            </tr>
            <?php foreach ($ds_hoadon as $hd) : ?>
                <tr>
                    <td><?= $hd[0] ?></td>
                    <td><?= $hd[1] ?></td>
                    <td><?= $hd[2] ?></td>
                    <td><?= $hd[3] ?></td>
                    <td><?= intval($hd[4]) ?></td>
                    <td>
                        <a href="user_page.php?donhang=xoa&id=<?= $hd[0] ?>"><i class="btn btn-outline-danger fa-solid fa-trash"></i></a>
                        <a href="user_page.php?donhang=in&id=<?= $hd[0] ?>"><i class="btn btn-outline-primary fa-solid fa-print"></i></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</div>

</div>
</div>