<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="img/logo.jpg" />
    <title>COFFEE SHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/style.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.2/jspdf.min.js"></script>


</head>

<?php
$roleName = currentRole();
$isWarehouseRole = $roleName === AppRole::WAREHOUSE;
$isSalesRole = $roleName === AppRole::SALES;
$isManagerRole = $roleName === AppRole::MANAGER;
$isAdminRole = $roleName === AppRole::ADMIN;
$isManagerOrAdmin = $isManagerRole || $isAdminRole;
?>
<?php
$currentTitle = 'COFFEE SHOP';
if (isset($_GET['home'])) {
    $currentTitle = 'COFFEE SHOP';
} elseif (isset($_GET['dashboard'])) {
    $currentTitle = 'COFFEE SHOP';
} elseif (isset($_GET['sanpham'])) {
    $currentTitle = 'Sản phẩm';
} elseif (isset($_GET['phieunhap'])) {
    $currentTitle = 'Phiếu nhập kho';
} elseif (isset($_GET['phieuxuat'])) {
    $currentTitle = 'Phiếu xuất kho';
} elseif (isset($_GET['kho_thongke'])) {
    $currentTitle = 'Thống kê tồn kho';
} elseif (isset($_GET['baocao_kho'])) {
    $currentTitle = 'Báo cáo kho';
} elseif (isset($_GET['baocao_kinhdoanh'])) {
    $currentTitle = 'Báo cáo kinh doanh';
} elseif (isset($_GET['baocao_nhansu'])) {
    $currentTitle = 'Báo cáo nhân sự';
} elseif (isset($_GET['nhacungcap'])) {
    $currentTitle = 'Quản lý nhà cung cấp';
} elseif (isset($_GET['loai'])) {
    $currentTitle = 'Loại sản phẩm';
} elseif (isset($_GET['donhang'])) {
    $currentTitle = 'Đơn hàng';
} elseif (isset($_GET['khachhang'])) {
    $currentTitle = 'Khách hàng';
} elseif (isset($_GET['nhansu'])) {
    $currentTitle = 'Nhân sự';
} elseif (isset($_GET['chucvu'])) {
    $currentTitle = 'Quản lý chức vụ';
} elseif (isset($_GET['bangluong'])) {
    $currentTitle = 'Bảng lương';
} elseif (isset($_GET['luong_ca_nhan'])) {
    $currentTitle = 'Lương của tôi';
} elseif (isset($_GET['donnghi'])) {
    $currentTitle = 'Đơn nghỉ phép';
} elseif (isset($_GET['donnghiviec'])) {
    $currentTitle = 'Đơn nghỉ việc';
} elseif (isset($_GET['profile'])) {
    $currentTitle = 'Hồ sơ cá nhân';
} elseif (isset($_GET['thongke'])) {
    $currentTitle = 'Thống kê';
}

// Đếm đơn chờ duyệt (chỉ cho Quản lý nhân sự để hiển thị Badge)
$pending_leave_count = 0;
$pending_resignation_count = 0;
if ($isManagerRole) {
    global $conn;
    $pc = mysqli_query($conn, "SELECT COUNT(*) FROM leave_requests WHERE status='chờ duyệt'");
    $pending_leave_count = $pc ? (int)mysqli_fetch_row($pc)[0] : 0;
    
    $pr = mysqli_query($conn, "SELECT COUNT(*) FROM resignation_requests WHERE status='chờ duyệt'");
    $pending_resignation_count = $pr ? (int)mysqli_fetch_row($pr)[0] : 0;
}
?>

<body class="app-shell">
    <div class="nav-side p-0">
        <div class="logo p-1 justify-content-center text-center">
            <a href="user_page.php?home"> <img src="img/logo.jpg" alt="Logo"></a>
        </div>

        <div class="navs pt-3">
            <div class="container-fluid">
                <a class="text-truncate <?= isset($_GET['home'])||isset($_GET['dashboard']) ? 'active' : '' ?>"
                   href="user_page.php?home"><i class="fa-solid fa-house-chimney"></i> Home</a>
            </div>

            <?php if ($isAdminRole) : ?>
                <div class="container-fluid">
                    <a class="text-truncate" href="admin/index.php"><i class="fa-solid fa-mug-hot"></i> SP & Nhà Cung Cấp</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['nhansu']) ? 'active' : '' ?>"
                       href="user_page.php?nhansu"><i class="fa-solid fa-users-gear"></i> DS Nhân sự (Tài khoản)</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['thongke']) ? 'active' : '' ?>"
                       href="user_page.php?thongke"><i class="fa-solid fa-coins"></i> Thống kê HĐ</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['baocao_kinhdoanh']) ? 'active' : '' ?>"
                       href="user_page.php?baocao_kinhdoanh"><i class="fa-solid fa-chart-line"></i> BC Kinh doanh</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['baocao_kho']) ? 'active' : '' ?>"
                       href="user_page.php?baocao_kho"><i class="fa-solid fa-chart-bar"></i> BC Kho</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['baocao_nhansu']) ? 'active' : '' ?>"
                       href="user_page.php?baocao_nhansu"><i class="fa-solid fa-chart-pie"></i> BC Nhân sự</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['luong_ca_nhan']) ? 'active' : '' ?>"
                       href="user_page.php?luong_ca_nhan"><i class="fa-solid fa-wallet"></i> Lương của tôi</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['profile']) ? 'active' : '' ?>"
                       href="user_page.php?profile"><i class="fa-solid fa-user-pen"></i> Hồ sơ cá nhân</a>
                </div>
            <?php endif; ?>

            <!-- ===== CATALOG / KHO ===== -->
            <?php if ($isWarehouseRole || $isManagerRole) : ?>
                <div class="nav-section-label">KHO HÀNG</div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['loai']) ? 'active' : '' ?>"
                       href="user_page.php?loai"><i class="fa-solid fa-list"></i> Loại SP</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['sanpham']) ? 'active' : '' ?>"
                       href="user_page.php?sanpham"><i class="fa-solid fa-mug-hot"></i> Sản Phẩm</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['phieunhap']) ? 'active' : '' ?>"
                       href="user_page.php?phieunhap"><i class="fa-solid fa-file-import"></i> Phiếu nhập</a>
                </div>
                <?php if (!$isWarehouseRole) : ?>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['phieuxuat']) ? 'active' : '' ?>"
                       href="user_page.php?phieuxuat"><i class="fa-solid fa-file-export"></i> Phiếu xuất</a>
                </div>
                <?php endif; ?>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['nhacungcap']) ? 'active' : '' ?>"
                       href="user_page.php?nhacungcap"><i class="fa-solid fa-truck-field"></i> Nhà cung cấp</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['baocao_kho']) ? 'active' : '' ?>"
                       href="user_page.php?baocao_kho"><i class="fa-solid fa-warehouse"></i> Báo cáo kho</a>
                </div>
            <?php endif; ?>

            <!-- ===== BÁN HÀNG ===== -->
            <?php if ($isSalesRole || $isManagerRole) : ?>
                <div class="nav-section-label">BÁN HÀNG</div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['donhang']) ? 'active' : '' ?>"
                       href="user_page.php?donhang"><i class="fa-solid fa-receipt"></i> Đơn hàng</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['khachhang']) ? 'active' : '' ?>"
                       href="user_page.php?khachhang"><i class="fa-solid fa-address-book"></i> Khách hàng</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== QUẢN LÝ NHÂN SỰ (Manager) ===== -->
        <?php if ($isManagerRole) : ?>
            <hr>
            <div class="container-fluid admin-head">
                <h5 class="text-center py-2 fw-bold">Quản lý nhân sự</h5>
            </div>
            <div class="navs text-center p-0">
                <!-- Khu vực dành riêng cho Manager (HR) -->
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['chucvu']) ? 'active' : '' ?>"
                       href="user_page.php?chucvu"><i class="fa-solid fa-briefcase"></i> Chức vụ</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['bangluong']) ? 'active' : '' ?>"
                       href="user_page.php?bangluong"><i class="fa-solid fa-money-bill-wave"></i> Bảng lương</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['donnghi']) ? 'active' : '' ?>"
                       href="user_page.php?donnghi">
                        <i class="fa-solid fa-calendar-check"></i> Đơn nghỉ phép
                        <?php if ($pending_leave_count > 0): ?>
                        <span class="badge text-bg-danger"><?= $pending_leave_count ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate <?= isset($_GET['donnghiviec']) ? 'active' : '' ?>"
                       href="user_page.php?donnghiviec">
                        <i class="fa-solid fa-door-open"></i> Đơn nghỉ việc
                        <?php if ($pending_resignation_count > 0): ?>
                        <span class="badge text-bg-danger"><?= $pending_resignation_count ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- ===== NHÂN VIÊN: menu cá nhân ===== -->
        <?php if (!$isManagerRole): ?>
        <hr class="mt-2">
        <div class="navs text-center p-0">
            <div class="container-fluid">
                <a class="text-truncate <?= isset($_GET['donnghi']) ? 'active' : '' ?>"
                   href="user_page.php?donnghi"><i class="fa-solid fa-calendar-minus"></i> Đơn nghỉ phép</a>
            </div>
            <div class="container-fluid">
                <a class="text-truncate <?= isset($_GET['donnghiviec']) ? 'active' : '' ?>"
                   href="user_page.php?donnghiviec"><i class="fa-solid fa-door-open"></i> Đơn nghỉ việc</a>
            </div>
            <div class="container-fluid">
                <a class="text-truncate <?= isset($_GET['luong_ca_nhan']) ? 'active' : '' ?>"
                   href="user_page.php?luong_ca_nhan"><i class="fa-solid fa-wallet"></i> Lương của tôi</a>
            </div>
            <div class="container-fluid">
                <a class="text-truncate <?= isset($_GET['profile']) ? 'active' : '' ?>"
                   href="user_page.php?profile"><i class="fa-solid fa-user-pen"></i> Hồ sơ cá nhân</a>
            </div>
        </div>
            <?php endif; ?>

        <!-- ===== USER INFO ===== -->
        <div class="navs text-center p-0">
            <hr class="mt-3">
            <div class="container-fluid">
                <a class="text-truncate"><i class="fa-solid fa-user-check"></i> <?= $user_name ?? '' ?></a>
            </div>
            <div class="container-fluid">
                <a class="text-truncate"><i class="fa-solid fa-id-badge"></i> <?= roleLabel(currentRole()) ?></a>
            </div>
            <div class="    container-fluid logout-btn">
                <a class="text-truncate" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </div>
        </div>

    </div>

    <div class="app-right">
        <div class="app-topbar px-3">
            <h4 class="mb-0 fw-bold"><?= htmlspecialchars($currentTitle) ?></h4>
        </div>
        <div class="main p-0">