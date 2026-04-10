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

<body class="row">
    <div class="nav-side col-2 p-0 sticky-top">
        <div class="logo p-1 justify-content-center text-center">
            <a href="user_page.php?dashboard"> <img width="30%" src="img/logo.jfif" alt="Logo"></a>
        </div>

        <div class="navs pt-3">
            <div class="container-fluid">
                <a class="text-truncate" href="user_page.php?dashboard"><i class="fa-solid fa-house-chimney"></i> DashBoard</a>
            </div>
            <?php if (can(AppPermission::MANAGE_CATALOG)) : ?>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?loai"><i class="fa-solid fa-list"></i> Loại</a>
                </div>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?sanpham"><i class="fa-solid fa-mug-hot"></i> Sản Phẩm</a>
                </div>
            <?php endif; ?>
            <?php if (can(AppPermission::PROCESS_ORDERS)) : ?>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?donhang"><i class="fa-solid fa-receipt"></i> Đơn hàng</a>
                </div>
            <?php endif; ?>
            <?php if (can(AppPermission::MANAGE_CUSTOMERS)) : ?>
                <div class="container-fluid">
                    <a class="text-truncate" href="user_page.php?khachhang"><i class="fa-solid fa-address-book"></i> Khách hàng</a>
                </div>
            <?php endif; ?>
        </div>


        <?php if (can(AppPermission::MANAGE_STAFF) || can(AppPermission::VIEW_REPORTS)) : ?>
            <hr>
            <div class="container-fluid admin-head">
                <h5 class="text-center py-2 fw-bold">Quản Lý</h5>
            </div>
            <div class="navs text-center p-0">
                <?php if (can(AppPermission::MANAGE_STAFF)) : ?>
                    <div class="container-fluid">
                        <a class="text-truncate" href="user_page.php?nhansu"><i class="fa-solid fa-users"></i> Nhân sự</a>
                    </div>
                <?php endif; ?>
                <?php if (can(AppPermission::VIEW_REPORTS)) : ?>
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
    <div class="main col-10 p-0">