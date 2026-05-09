<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/components/SearchBox.php';
require_once __DIR__ . '/components/FilterPanel.php';
require_once __DIR__ . '/components/Table.php';

$view = 'products';

$filters = [
    'q' => $_GET['q'] ?? '',
    'category_id' => $_GET['category_id'] ?? 0,
    'status' => $_GET['status'] ?? '',
    'sale_status' => $_GET['sale_status'] ?? '',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'sort' => $_GET['sort'] ?? 'date',
    'direction' => $_GET['direction'] ?? 'desc',
];

$categoriesResult = mysqli_query($conn, 'SELECT category_id, category_name FROM category ORDER BY category_name ASC');
$categories = $categoriesResult ? mysqli_fetch_all($categoriesResult, MYSQLI_ASSOC) : [];

$products = adminFetchProducts($conn, $filters);

$productCount = count($products);
$sellingProducts = adminCountValue($conn, "SELECT COUNT(*) FROM items WHERE sale_status = 'selling'");
$stoppedProducts = adminCountValue($conn, "SELECT COUNT(*) FROM items WHERE sale_status = 'stopped'");
$stockExpr = "(COALESCE((SELECT sm.stock_after FROM stock_movements sm WHERE sm.item_id = i.item_id ORDER BY sm.movement_id DESC LIMIT 1), i.stock_quantity))";
$lowStockProducts = adminCountValue($conn, "SELECT COUNT(*) FROM items i WHERE " . $stockExpr . " < 10");

$buildStatusUrl = static function (string $saleStatus) use ($filters): string {
    $query = $_GET;
    if ($saleStatus === '') {
        unset($query['sale_status']);
    } else {
        $query['sale_status'] = $saleStatus;
    }

    return $_SERVER['PHP_SELF'] . '?' . http_build_query($query);
};

$buildStockUrl = static function (?string $stockFilter) use ($filters): string {
    $query = $_GET;
    if ($stockFilter === null || $stockFilter === '') {
        unset($query['stock']);
    } else {
        $query['stock'] = $stockFilter;
    }

    return $_SERVER['PHP_SELF'] . '?' . http_build_query($query);
};

$currentSaleStatus = (string) ($filters['sale_status'] ?? '');
$currentStockFilter = (string) ($filters['stock'] ?? '');

$cardClass = static function (bool $active): string {
    return 'stat-pill text-decoration-none text-reset ' . ($active ? 'border border-primary bg-white shadow-sm' : '');
};

require_once __DIR__ . '/../src/views/layout.php';
renderAppLayoutStart($_SESSION['full_name'] ?? 'Admin', 'admin');
?>

            <header class="admin-hero card shadow-sm border-0 mb-4">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                    <div>
                        <p class="eyebrow mb-1">Giao diện tách biệt cho ADMIN</p>
                        <h1 class="admin-title mb-2">Sản phẩm</h1>
                        <p class="text-muted mb-0">Tìm kiếm cơ bản, lọc nâng cao và sắp xếp theo nhiều tiêu chí.</p>
                    </div>
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="<?= htmlspecialchars($buildStatusUrl('')) ?>" class="<?= $cardClass($currentSaleStatus === '' && $currentStockFilter === '') ?>">
                                <span>Số sản phẩm hiển thị</span>
                                <strong><?= $productCount ?></strong>
                            </a>
                            <a href="<?= htmlspecialchars($buildStatusUrl('selling')) ?>" class="<?= $cardClass($currentSaleStatus === 'selling') ?>">
                                <span>SP đang bán</span>
                                <strong><?= $sellingProducts ?></strong>
                            </a>
                        </div>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="<?= htmlspecialchars($buildStatusUrl('stopped')) ?>" class="<?= $cardClass($currentSaleStatus === 'stopped') ?>">
                                <span>SP ngừng bán</span>
                                <strong><?= $stoppedProducts ?></strong>
                            </a>
                            <a href="<?= htmlspecialchars($buildStockUrl('critical')) ?>" class="<?= $cardClass($currentStockFilter === 'critical') ?>">
                                <span>SP tồn kho thấp</span>
                                <strong><?= $lowStockProducts ?></strong>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <?php renderAdminSearchBox($view, (string) $filters['q']); ?>
            <?php renderAdminFilterPanel($view, $filters, $categories); ?>

            <?php
            renderAdminTable(
                ['Mã', 'Ảnh', 'Tên', 'Danh mục', 'Giá', 'Tồn', 'Trạng thái bán', 'Ngày tạo'],
                $products,
                function (array $row): string {
                    $saleStatus = (string) ($row['sale_status'] ?? 'selling');
                    $statusClass = $saleStatus === 'selling' ? 'text-bg-success' : 'text-bg-danger';
                    $statusLabel = $saleStatus === 'selling' ? 'Đang bán' : 'Ngừng bán';
                    
                    // Đồng bộ logic hiển thị ảnh với src/views/sanpham.php
                    $id_img   = 'img/' . (int)$row['item_id'] . '.jpg';
                    $db_img   = trim((string)($row['item_image'] ?? ''));
                    $fallback = 'img/' . (((int)$row['item_id'] % 13) + 1) . '.jpg';
                    
                    if (file_exists(__DIR__ . '/../' . $id_img)) {
                        $img_src = $id_img;
                    } elseif ($db_img !== '') {
                        $img_src = $db_img;
                    } else {
                        $img_src = $fallback;
                    }

                    $imgHtml = '<img src="../' . htmlspecialchars($img_src) . '" width="80" height="80" class="rounded shadow-sm border" style="object-fit: cover;" onerror="this.src=\'../img/1.jpg\'">';
                    
                    return '<tr>'
                        . '<td>' . (int) $row['item_id'] . '</td>'
                        . '<td>' . $imgHtml . '</td>'
                        . '<td>' . adminText($row['item_name']) . '</td>'
                        . '<td>' . adminText($row['category_name'] ?? '-') . '</td>'
                        . '<td>' . adminMoney($row['unit_price']) . '</td>'
                        . '<td>' . ((isset($row['stock_quantity']) && $row['stock_quantity'] !== null) ? (int)$row['stock_quantity'] : '-') . '</td>'
                        . '<td><span class="badge ' . $statusClass . '">' . adminText($statusLabel) . '</span></td>'
                        . '<td>' . adminDateTime($row['added_date'] ?? null) . '</td>'
                        . '</tr>';
                }
            );
            ?>
<?php
renderAppLayoutEnd('admin');
?>
