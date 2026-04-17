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
            <button type="button" class="my-2 btn btn-success fw-bolder" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                <i class="fa-solid fa-file-circle-plus"></i> Thêm
            </button>
        </div><!-- Modal Thêm Khách Hàng -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="addCustomerModalLabel"><i class="fa-solid fa-user-plus me-2"></i>Thêm khách hàng mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="user_page.php?khachhang" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="customer_name" class="form-label fw-bold">Tên khách hàng <span class="text-danger">*</span></label>
                            <input required type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Ví dụ: Nguyễn Văn A">
                        </div>
                        <div class="col-md-6">
                            <label for="phone_number" class="form-label fw-bold">Số điện thoại <span class="text-danger">*</span></label>
                            <input required type="text" class="form-control" id="phone_number" name="phone_number" placeholder="Ví dụ: 090xxxxxxx">
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label fw-bold">Email (Nếu có)</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Ví dụ: email@example.com">
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label fw-bold">Địa chỉ</label>
                            <textarea class="form-control" id="address" name="address" rows="3" placeholder="Nhập địa chỉ của khách hàng..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_customer_submit" class="btn btn-success px-5 fw-bold">Xác nhận thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>



        <table id="myTable" class="table container-fluid text-center table-hover table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th style="width: 120px;">
                        <div class="d-flex align-items-center justify-content-center">
                            <span>Mã số</span>
                            <div class="ms-2 d-flex flex-row">
                                <a href="user_page.php?khachhang=id_tang" class="text-white me-1"><i class="fa-solid fa-sort-up"></i></a>
                                <a href="user_page.php?khachhang=id_giam" class="text-white"><i class="fa-solid fa-sort-down"></i></a>
                            </div>
                        </div>
                    </th>
                    <th>Tên khách hàng</th>
                    <th>Số điện thoại</th>
                    <th>Email</th>
                    <th>Địa chỉ</th>
                    <th style="min-width: 180px;">
                        <div class="d-flex align-items-center justify-content-center">
                            <span>Ngày tạo</span>
                            <div class="ms-2 d-flex flex-row">
                                <a href="user_page.php?khachhang=ngay_tang" class="text-white me-1"><i class="fa-solid fa-sort-up"></i></a>
                                <a href="user_page.php?khachhang=ngay_giam" class="text-white"><i class="fa-solid fa-sort-down"></i></a>
                            </div>
                        </div>
                    </th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ds_khachhang as $kh) :
                    // Chuyển thành indexed array để tương thích với code cũ nếu cần, 
                    // nhưng ta sẽ dùng key nếu có thể để an toàn hơn.
                    $kh_indexed = array_values($kh);
                    $cid = $kh_indexed[0];
                    $name = $kh_indexed[1];
                    $phone = $kh_indexed[2] ?? '-';
                    $email = $kh_indexed[3] ?? '-';
                    $address = $kh_indexed[4] ?? '-';
                    $created = isset($kh_indexed[5]) ? date('d/m/Y H:i', strtotime($kh_indexed[5])) : '-';
                ?>
                    <tr>
                        <td class="fw-bold text-muted">#<?= $cid ?></td>
                        <td class="text-start"><?= htmlspecialchars($name) ?></td>
                        <td><?= htmlspecialchars($phone) ?></td>
                        <td><?= htmlspecialchars($email) ?></td>
                        <td class="text-start"><small><?= htmlspecialchars($address) ?></small></td>
                        <td><?= $created ?></td>
                        <td>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="user_page.php?khachhang=xem&id=<?= $cid ?>" class="btn btn-sm btn-outline-info" title="Xem chi tiết">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="user_page.php?khachhang=sua&id=<?= $cid ?>" class="btn btn-sm btn-outline-primary" title="Chỉnh sửa">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>
</div>

</div>

<script>
// Chế độ xem chi tiết khách hàng
</script>