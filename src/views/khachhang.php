<?php

if (isset($ds_kh_sapxep)) {
    $ds_khachhang = array_values($ds_kh_sapxep);
} else
if (!isset($ds_kh_timkiem)) {
    $sql = "select * from customers; ";
    $ds_khachhang =  mysqli_query($conn, $sql);
    $ds_khachhang = mysqli_fetch_all($ds_khachhang);
} else {
    $ds_khachhang = $ds_kh_timkiem;
}
?>


<div class="dash_board px-2">
    <h1 class="head-name">KHÁCH HÀNG</h1>
    <?php if (isset($_GET['timkiem-khachhang'])) : ?>
        <h4 class="fw-bolder text-center text-success">Kết quả cho từ khóa '<?= $_GET['timkiem-khachhang'] ?? '' ?>'</h4>
    <?php endif; ?>
    <?php if (isset($_SESSION['them_kh_thanh_cong'])) : ?>
        <h4 class="fw-bolder text-center text-success"><?= $_SESSION['them_kh_thanh_cong']; ?></h4>
    <?php unset($_SESSION['them_kh_thanh_cong']);
    endif; ?>
    <div class="head-line"></div>
    <div class="container-fluid row justify-content-between">

        <!-- Thanh tìm kiếm-->

        <form method="GET" action="user_page.php?" class="d-flex col-6 my-2" role="search">
            <input class="form-control me-2" type="search" placeholder="Nhập tên khách hàng" name='timkiem-khachhang' aria-label="Search">
            <button class="btn btn-outline-success" type="submit">Tìm</button>
        </form>



        <div class="text-end col-4">
            <a href="user_page.php?khachhang=tang_dan" class="my-2 btn btn-success fw-bolder"><i class="fa-solid fa-arrow-down-a-z"></i></a>
            <a href="user_page.php?khachhang=giam_dan" class="my-2 btn btn-success fw-bolder"><i class="fa-solid fa-arrow-down-z-a"></i></a>
            <a href="user_page.php?khachhang=them" class="my-2 btn btn-success fw-bolder"><i class="fa-solid fa-file-circle-plus"></i> Thêm</a>
        </div>



        <table id="myTable" class="table container-fluid text-center table-hover table-striped table-bordered">
            <tr>
                <!-- <th>Mã số</th> -->
                <th>Mã số </th>
                <th onclick="sortTable(1)">Tên khách hàng <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></i></i> </th>
                <th onclick="sortTable(2)">Số điện thoại <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></i></th>


            </tr>
            <?php foreach ($ds_khachhang as $kh) :
                $kh = array_values($kh);
            ?>
                <tr>
                    <td><?= $kh[0] ?></td>
                    <td><?= $kh[1] ?></td>
                    <td><?= $kh[2] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

    </div>
</div>
</div>

</div>
</div>