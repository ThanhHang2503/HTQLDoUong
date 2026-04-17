<?php
if (!isset($_GET['id'])) {
    header('location:user_page.php?sanpham');
    exit;
}

$item_id = (int)$_GET['id'];
$sql     = "SELECT i.*, c.category_name
            FROM items i
            LEFT JOIN category c ON c.category_id = i.category_id
            WHERE i.item_id = $item_id LIMIT 1";
$result  = mysqli_query($conn, $sql);
$row     = $result ? mysqli_fetch_assoc($result) : null;

if (!$row) {
    $_SESSION['product_delete_error'] = 'Sản phẩm không tồn tại.';
    header('location:user_page.php?sanpham');
    exit;
}

// Lấy danh sách nhà cung cấp đã cung cấp sản phẩm này (qua phiếu nhập)
$supplierSql = "SELECT DISTINCT s.supplier_name, MAX(r.import_date) AS last_import_date
                FROM inventory_receipt_items ri
                INNER JOIN inventory_receipts r ON r.receipt_id = ri.receipt_id
                INNER JOIN suppliers s ON s.supplier_id = r.supplier_id
                WHERE ri.item_id = {$item_id}
                GROUP BY s.supplier_id, s.supplier_name
                ORDER BY last_import_date DESC";
$supplierRs = mysqli_query($conn, $supplierSql);
$suppliers = $supplierRs ? mysqli_fetch_all($supplierRs, MYSQLI_ASSOC) : [];

// Lấy giá nhập gần nhất
$lastImportPriceSql = "SELECT ri.import_price, r.import_date
                       FROM inventory_receipt_items ri
                       INNER JOIN inventory_receipts r ON r.receipt_id = ri.receipt_id
                       WHERE ri.item_id = {$item_id}
                       ORDER BY r.import_date DESC, ri.receipt_item_id DESC
                       LIMIT 1";
$lastImportPriceRs = mysqli_query($conn, $lastImportPriceSql);
$lastImportPrice = $lastImportPriceRs ? mysqli_fetch_assoc($lastImportPriceRs) : null;
?>

<div class="mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card">
                <div class="card-header bg-info text-white fw-bold">
                    <i class="fa-solid fa-eye me-1"></i> CHI TIẾT SẢN PHẨM
                    <span class="float-end badge bg-light text-dark font-monospace"><?= htmlspecialchars($row['item_code'] ?? '') ?></span>
                </div>
                <div class="card-body">

                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="bg-light" style="width:35%">Mã sản phẩm</th>
                                <td><span class="font-monospace"><?= htmlspecialchars($row['item_code'] ?? '') ?></span></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Tên sản phẩm</th>
                                <td><?= htmlspecialchars($row['item_name']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Danh mục</th>
                                <td><?= htmlspecialchars($row['category_name'] ?? 'Chưa phân loại') ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Mô tả</th>
                                <td><?= htmlspecialchars((string)$row['description']) ?: '<em class="text-muted">Không có mô tả</em>' ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Giá bán (VND)</th>
                                <td class="fw-bold text-primary"><?= number_format((float)$row['unit_price'], 0, ',', '.') ?> ₫</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Giá nhập gần nhất</th>
                                <td>
                                    <?php if ($lastImportPrice): ?>
                                        <?= number_format((float)$lastImportPrice['import_price'], 0, ',', '.') ?> ₫
                                        <small class="text-muted">(<?= htmlspecialchars($lastImportPrice['import_date']) ?>)</small>
                                    <?php else: ?>
                                        <em class="text-muted">Chưa có phiếu nhập</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Nhà cung cấp</th>
                                <td>
                                    <?php if (count($suppliers) > 0): ?>
                                        <?php foreach ($suppliers as $idx => $sup): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($sup['supplier_name']) ?></span>
                                            <?php if ($idx === 0 && count($suppliers) > 1): ?>
                                                <small class="text-muted">(gần nhất)</small>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <em class="text-muted">Chưa có nhà cung cấp (chưa nhập hàng)</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Tồn kho</th>
                                <td>
                                    <span class="fw-bold <?= (int)$row['stock_quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= (int)$row['stock_quantity'] ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Trạng thái bán</th>
                                <td>
                                    <?php if (($row['sale_status'] ?? 'selling') === 'selling'): ?>
                                        <span class="badge bg-success">🟢 Đang bán</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">🔴 Ngừng bán</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Trạng thái hệ thống</th>
                                <td>
                                    <?php if (($row['item_status'] ?? 'active') === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-3 d-flex gap-2">
                        <?php if (can(AppPermission::MANAGE_CATALOG)): ?>
                            <a href="user_page.php?sanpham=sua&id=<?= (int)$row['item_id'] ?>" class="btn btn-outline-warning">
                                <i class="fa-solid fa-pen me-1"></i>Chỉnh sửa
                            </a>
                        <?php endif; ?>
                        <a href="user_page.php?sanpham" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i>Quay lại
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
