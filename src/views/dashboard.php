<?php
function dashboardScalar(mysqli $conn, string $sql, string $column)
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return $row[$column] ?? 0;
}

$so_mon = dashboardScalar($conn, "SELECT COUNT(*) AS so_mon FROM items", "so_mon");
$so_quanly = dashboardScalar($conn, "SELECT COUNT(*) AS so_quanly FROM accounts a JOIN roles r ON r.id = a.role_id WHERE r.name = 'manager' AND a.status = 'active'", "so_quanly");
$so_nv_banhang = dashboardScalar($conn, "SELECT COUNT(*) AS so_nv_banhang FROM accounts a JOIN roles r ON r.id = a.role_id WHERE r.name = 'sales' AND a.status = 'active'", "so_nv_banhang");
$so_nv_kho = dashboardScalar($conn, "SELECT COUNT(*) AS so_nv_kho FROM accounts a JOIN roles r ON r.id = a.role_id WHERE r.name = 'warehouse' AND a.status = 'active'", "so_nv_kho");
$so_khachhang = dashboardScalar($conn, "SELECT COUNT(*) AS so_khachhang FROM customers", "so_khachhang");
$so_danhmuc = dashboardScalar($conn, "SELECT COUNT(*) AS so_danhmuc FROM category", "so_danhmuc");
$so_admin = dashboardScalar($conn, "SELECT COUNT(*) AS so_admin FROM accounts a JOIN roles r ON r.id = a.role_id WHERE r.name = 'admin' AND a.status = 'active'", "so_admin");
$so_hoadon = dashboardScalar($conn, "SELECT COUNT(*) AS so_hoadon FROM invoices", "so_hoadon");
$so_tien = dashboardScalar($conn, "SELECT COALESCE(SUM(total), 0) AS so_tien FROM invoices", "so_tien");

?>



<div class="dash_board px-2">
    <h1 class="head-name">DASH BOARD</h1>
    <div class="head-line"></div>
    <div class="container-fluid">
        <div class="row my-2 justify-content-around">
            <div class="col-6 col-md-4 col-lg-3">
                <div class="px-3">
                    <div style="background-color: #e75811;" class="box row justify-content-between">
                        <div class="col-6">
                            <h1 class="fw-bolder"><?= $so_mon ?></h1>
                            <p class="fw-bolder text-start">Món ăn</p>
                        </div>
                        <div class="col-5 text-center">
                            <i class="big-icon fa-solid fa-mug-hot"></i>
                        </div>
                        <div class="foot-box col-12 text-center"><i class="small-icon text-white fa-solid fa-eye"></i></div>
                    </div>

                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="px-3">
                    <div style="background-color: #019159;" class="box row justify-content-between">
                        <div class="col-6">
                            <h1 class="fw-bolder"><?= $so_nv_banhang ?></h1>
                            <p class="fw-bolder text-start">NV bán hàng</p>
                        </div>
                        <div class="col-5 text-center">
                            <i class="big-icon fa-solid fa-users"></i>
                        </div>
                        <div class="foot-box col-12 text-center"><i class="small-icon text-white fa-solid fa-eye"></i></div>
                    </div>

                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="px-3">
                    <div style="background-color: #ff9e16;" class="box row justify-content-between">
                        <div class="col-6">
                            <h1 class="fw-bolder"><?= $so_khachhang ?></h1>
                            <p class="fw-bolder text-start">Khách hàng</p>
                        </div>
                        <div class="col-5 text-center">
                            <i class="big-icon fa-solid fa-users-viewfinder"></i>
                        </div>
                        <div class="foot-box col-12 text-center"><i class="small-icon text-white fa-solid fa-eye"></i></div>
                    </div>

                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="px-3">
                    <div style="background-color: #2c76b5;" class="box row justify-content-between">
                        <div class="col-6">
                            <h1 class="fw-bolder"><?= $so_danhmuc ?></h1>
                            <p class="fw-bolder text-start">Danh mục</p>
                        </div>
                        <div class="col-5 text-center">
                            <i class="big-icon fa-solid fa-list"></i>
                        </div>
                        <div class="foot-box col-12 text-center"><i class="small-icon text-white fa-solid fa-eye"></i></div>
                    </div>

                </div>
            </div>
        </div>

        <div class="row my-3 justify-content-around">

            <div class="col-6 col-md-4 col-lg-3">
                <div class="px-3">
                    <div style="background-color: #001e40" class="box row justify-content-between">
                        <div class="col-6">
                            <h1 class="fw-bolder"><?= $so_quanly ?></h1>
                            <p class="fw-bolder text-start">Quản lý</p>
                        </div>
                        <div class="col-5 text-center">
                            <i style="color: #b4aba9;" class="big-icon fa-solid fa-people-roof"></i>
                        </div>
                        <div class="foot-box col-12 text-center"><i class="small-icon text-white fa-solid fa-eye"></i></div>
                    </div>

                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="px-3">
                    <div style="background-color: #27aebb;" class="box row justify-content-between">
                        <div class="col-6">
                            <h1 class="fw-bolder"><?= $so_nv_kho ?></h1>
                            <p class="fw-bolder text-start">NV kho</p>
                        </div>
                        <div class="col-5 text-center">
                            <i class="big-icon fa-solid fa-boxes-stacked"></i>
                        </div>
                        <div class="foot-box col-12 text-center"><i class="small-icon text-white fa-solid fa-eye"></i></div>
                    </div>

                </div>
            </div>
            <div class="col-6">
                <div class="px-3">
                    <div style="background-color: #e83260;" class="box row justify-content-between">
                        <div class="col-7">
                            <h1 class="fw-bolder"><?= $so_admin ?></h1>
                            <p class="fw-bolder text-start">Admin</p>
                        </div>
                        <div class="col-5 text-center">
                            <i class="big-icon fa-solid fa-user-shield"></i>
                        </div>
                        <div class=" foot-box col-12 text-center"><i class="small-icon text-white fa-solid fa-eye"></i></div>
                    </div>

                </div>
            </div>
            <div class="col-12 mt-3">
                <div class="px-3">
                    <div style="background-color: #e83260;" class="box row justify-content-between">
                        <div class="col-7">
                            <h1 class="fw-bolder"><?= intval($so_tien) . ' VNĐ' ?></h1>
                            <p class="fw-bolder text-start">Tổng doanh thu</p>
                        </div>
                        <div class="col-5 text-center">
                            <i class="big-icon fa-solid fa-coins"></i>
                        </div>
                        <div class=" foot-box col-12 text-center"><i class="small-icon text-white fa-solid fa-eye"></i></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
</div>

</div>
</div>