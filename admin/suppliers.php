<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/components/SearchBox.php';
require_once __DIR__ . '/components/FilterPanel.php';
require_once __DIR__ . '/components/Table.php';

$view = 'suppliers';

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

$suppliers = adminFetchSuppliers($conn, $filters);

$supplierCount = adminCountValue($conn, 'SELECT COUNT(*) FROM suppliers');

require_once __DIR__ . '/../src/views/layout.php';
renderAppLayoutStart($_SESSION['full_name'] ?? 'Admin', 'admin');
?>

            <header class="admin-hero card shadow-sm border-0 mb-4">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                    <div>
                        <p class="eyebrow mb-1">Giao diện tách biệt cho ADMIN</p>
                        <h1 class="admin-title mb-2">Nhà cung cấp</h1>
                        <p class="text-muted mb-0">Tìm kiếm cơ bản, lọc nâng cao và sắp xếp theo nhiều tiêu chí.</p>
                    </div>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="stat-pill">
                            <span>Tổng nhà cung cấp</span>
                            <strong><?= $supplierCount ?></strong>
                        </div>
                    </div>
                </div>
            </header>

            <?php renderAdminSearchBox($view, (string) $filters['q']); ?>
            <?php renderAdminFilterPanel($view, $filters, []); ?>

            <?php
            renderAdminTable(
                ['Mã', 'Mã NCC', 'Tên nhà cung cấp', 'Người liên hệ', 'Ngày tạo'],
                $suppliers,
                function (array $row): string {
                    return '<tr>'
                        . '<td>' . (int) $row['supplier_id'] . '</td>'
                        . '<td>' . adminText($row['supplier_code']) . '</td>'
                        . '<td>' . adminText($row['supplier_name']) . '</td>'
                        . '<td>' . adminText($row['contact_name'] ?? '-') . '</td>'
                        . '<td>' . adminDateTime($row['created_at'] ?? null) . '</td>'
                        . '</tr>';
                }
            );
            ?>
<?php
renderAppLayoutEnd('admin');
?>
