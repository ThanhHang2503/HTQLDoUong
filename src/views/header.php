<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="img/logo.jfif" />
    <title>Ông Già Nè</title>
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
?>
<?php
$currentTitle = 'Trang chủ';
if (isset($_GET['home'])) {
    $currentTitle = 'Trang chủ';
} elseif (isset($_GET['dashboard'])) {
    $currentTitle = 'Trang chủ';
} elseif (isset($_GET['sanpham'])) {
    $currentTitle = 'Sản phẩm';
} elseif (isset($_GET['phieunhap'])) {
    $currentTitle = 'Phiếu nhập';
} elseif (isset($_GET['kho_thongke'])) {
    $currentTitle = 'Thống kê tồn kho';
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
} elseif (isset($_GET['thongke'])) {
    $currentTitle = 'Thống kê';
}
?>

<body class="app-shell">
    <div class="nav-side p-0">
        <div class="logo p-1 justify-content-center text-center">
            <a href="user_page.php?home"> <img src="img/logo.jfif" alt="Logo"></a>
        </div>

        <div class="navs pt-3">
            <div class="container-fluid">
                <a class="text-truncate" href="user_page.php?home"><i class="fa-solid fa-house-chimney"></i> Home</a>
            </div>
            <?php if ($isWarehouseRole || $isManagerRole || $isAdminRole) : ?>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?loai"><i class="fa-solid fa-list"></i> Loại</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?sanpham"><i class="fa-solid fa-mug-hot"></i> Sản Phẩm</a>
                </div>
            <?php endif; ?>

            <?php if ($isWarehouseRole || $isAdminRole) : ?>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?phieunhap"><i class="fa-solid fa-file-import"></i> Phiếu nhập</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?nhacungcap"><i class="fa-solid fa-truck-field"></i> Quản lý nhà cung cấp</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?kho_thongke"><i class="fa-solid fa-warehouse"></i> Thống kê tồn kho</a>
                </div>
            <?php endif; ?>

            <?php if ($isSalesRole || $isManagerRole || $isAdminRole) : ?>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?donhang"><i class="fa-solid fa-receipt"></i> Đơn hàng</a>
                </div>
            <?php endif; ?>

            <?php if ($isSalesRole || $isManagerRole || $isAdminRole) : ?>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?khachhang"><i class="fa-solid fa-address-book"></i> Khách hàng</a>
                </div>
            <?php endif; ?>
        </div>


        <?php if ($isManagerRole || $isAdminRole) : ?>
            <hr>
            <div class="container-fluid admin-head">
                <h5 class="text-center py-2 fw-bold">Quản Lý</h5>
            </div>
            <div class="navs text-center p-0">
                <?php if ($isAdminRole) : ?>
                    <div class="container-fluid">
                        <a class="text-truncate" href="user_page.php?nhansu"><i class="fa-solid fa-users"></i> Nhân sự</a>
                    </div>
                <?php endif; ?>
                <?php if ($isManagerRole || $isAdminRole) : ?>
                    <div class="container-fluid">
                        <a class="text-truncate" href="user_page.php?thongke"><i class="fa-solid fa-coins"></i> Thống kê</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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