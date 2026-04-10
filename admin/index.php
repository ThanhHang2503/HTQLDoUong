<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/components/SearchBox.php';
require_once __DIR__ . '/components/FilterPanel.php';
require_once __DIR__ . '/components/Table.php';

$view = $_GET['view'] ?? 'products';
if (!in_array($view, ['products', 'suppliers'], true)) {
    $view = 'products';
}

$filters = [
    'q' => $_GET['q'] ?? '',
    'category_id' => $_GET['category_id'] ?? 0,
    'status' => $_GET['status'] ?? '',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'sort' => $_GET['sort'] ?? 'date',
    'direction' => $_GET['direction'] ?? 'desc',
];

$categoriesResult = mysqli_query($conn, 'SELECT category_id, category_name FROM category ORDER BY category_name ASC');
$categories = $categoriesResult ? mysqli_fetch_all($categoriesResult, MYSQLI_ASSOC) : [];

$products = [];
$suppliers = [];
if ($view === 'products') {
    $products = adminFetchProducts($conn, $filters);
} else {
    $suppliers = adminFetchSuppliers($conn, $filters);
}

$productCount = adminCountValue($conn, 'SELECT COUNT(*) FROM items');
$supplierCount = adminCountValue($conn, 'SELECT COUNT(*) FROM suppliers');
$activeProducts = adminCountValue($conn, "SELECT COUNT(*) FROM items WHERE item_status = 'active'");
$activeSuppliers = adminCountValue($conn, "SELECT COUNT(*) FROM suppliers WHERE status = 'active'");

?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | ElderCoffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="brand-block">
                <div class="brand-badge">EC</div>
                <div>
                    <div class="brand-title">ElderCoffee Admin</div>
                    <div class="brand-subtitle"><?= adminText($_SESSION['full_name'] ?? 'Admin') ?></div>
                </div>
            </div>

            <nav class="admin-nav">
                <a class="<?= $view === 'products' ? 'active' : '' ?>" href="?view=products">
                    <i class="fa-solid fa-mug-hot"></i> Sản phẩm
                </a>
                <a class="<?= $view === 'suppliers' ? 'active' : '' ?>" href="?view=suppliers">
                    <i class="fa-solid fa-truck-fast"></i> Nhà cung cấp
                </a>
                <a href="../user_page.php?nhansu">
                    <i class="fa-solid fa-users-gear"></i> Quản lý User
                </a>
                <a href="../user_page.php?baocao_kinhdoanh">
                    <i class="fa-solid fa-chart-line"></i> BC Kinh doanh
                </a>
                <a href="../user_page.php?luong_ca_nhan">
                    <i class="fa-solid fa-wallet"></i> Lương của tôi
                </a>
                <a href="../user_page.php?profile">
                    <i class="fa-solid fa-user-pen"></i> Hồ sơ cá nhân
                </a>
                <a href="../logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                </a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-hero card shadow-sm border-0 mb-4">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                    <div>
                        <p class="eyebrow mb-1">Giao diện tách biệt cho ADMIN</p>
                        <h1 class="admin-title mb-2"><?= $view === 'suppliers' ? 'Nhà cung cấp' : 'Sản phẩm' ?></h1>
                        <p class="text-muted mb-0">Tìm kiếm cơ bản, lọc nâng cao và sắp xếp theo nhiều tiêu chí.</p>
                    </div>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="stat-pill">
                            <span>Tổng sản phẩm</span>
                            <strong><?= $productCount ?></strong>
                        </div>
                        <div class="stat-pill">
                            <span>SP hoạt động</span>
                            <strong><?= $activeProducts ?></strong>
                        </div>
                        <div class="stat-pill">
                            <span>Tổng nhà cung cấp</span>
                            <strong><?= $supplierCount ?></strong>
                        </div>
                        <div class="stat-pill">
                            <span>NC cung cấp hoạt động</span>
                            <strong><?= $activeSuppliers ?></strong>
                        </div>
                    </div>
                </div>
            </header>

            <?php renderAdminSearchBox($view, (string) $filters['q']); ?>
            <?php renderAdminFilterPanel($view, $filters, $categories); ?>

            <?php if ($view === 'products') : ?>
                <?php
                renderAdminTable(
                    ['Mã', 'Tên', 'Danh mục', 'Giá', 'Trạng thái', 'Ngày tạo'],
                    $products,
                    function (array $row): string {
                        $statusClass = $row['item_status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary';
                        return '<tr>'
                            . '<td>' . (int) $row['item_id'] . '</td>'
                            . '<td>' . adminText($row['item_name']) . '</td>'
                            . '<td>' . adminText($row['category_name'] ?? '-') . '</td>'
                            . '<td>' . adminMoney($row['unit_price']) . '</td>'
                            . '<td><span class="badge ' . $statusClass . '">' . adminText($row['item_status']) . '</span></td>'
                            . '<td>' . adminDateTime($row['added_date'] ?? null) . '</td>'
                            . '</tr>';
                    }
                );
                ?>
            <?php else : ?>
                <?php
                renderAdminTable(
                    ['Mã', 'Mã NCC', 'Tên nhà cung cấp', 'Người liên hệ', 'Trạng thái', 'Ngày tạo'],
                    $suppliers,
                    function (array $row): string {
                        $statusClass = $row['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary';
                        return '<tr>'
                            . '<td>' . (int) $row['supplier_id'] . '</td>'
                            . '<td>' . adminText($row['supplier_code']) . '</td>'
                            . '<td>' . adminText($row['supplier_name']) . '</td>'
                            . '<td>' . adminText($row['contact_name'] ?? '-') . '</td>'
                            . '<td><span class="badge ' . $statusClass . '">' . adminText($row['status']) . '</span></td>'
                            . '<td>' . adminDateTime($row['created_at'] ?? null) . '</td>'
                            . '</tr>';
                    }
                );
                ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
