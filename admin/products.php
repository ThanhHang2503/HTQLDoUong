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

$productCount = adminCountValue($conn, 'SELECT COUNT(*) FROM items');
$activeProducts = adminCountValue($conn, "SELECT COUNT(*) FROM items WHERE item_status = 'active'");

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
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="stat-pill">
                            <span>Tổng sản phẩm</span>
                            <strong><?= $productCount ?></strong>
                        </div>
                        <div class="stat-pill">
                            <span>SP hoạt động</span>
                            <strong><?= $activeProducts ?></strong>
                        </div>
                    </div>
                </div>
            </header>

            <?php renderAdminSearchBox($view, (string) $filters['q']); ?>
            <?php renderAdminFilterPanel($view, $filters, $categories); ?>

            <?php
            renderAdminTable(
                ['Mã', 'Ảnh', 'Tên', 'Danh mục', 'Giá', 'Trạng thái', 'Ngày tạo'],
                $products,
                function (array $row): string {
                    $statusClass = $row['item_status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary';
                    
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
                        . '<td><span class="badge ' . $statusClass . '">' . adminText($row['item_status']) . '</span></td>'
                        . '<td>' . adminDateTime($row['added_date'] ?? null) . '</td>'
                        . '</tr>';
                }
            );
            ?>
<?php
renderAppLayoutEnd('admin');
?>
