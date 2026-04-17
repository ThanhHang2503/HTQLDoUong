<?php
// Lấy danh sách sản phẩm active (bao gồm purchase_price để auto-fill form)
$productsSql = "SELECT item_id, item_code, item_name, stock_quantity, unit_price, purchase_price
                FROM items
                WHERE item_status = 'active'
                ORDER BY item_name ASC";
$productsRs = mysqli_query($conn, $productsSql);
$products = mysqli_fetch_all($productsRs, MYSQLI_ASSOC);

// Lấy danh sách nhà cung cấp active
$suppliersSql = "SELECT supplier_id, supplier_name
                 FROM suppliers
                 WHERE status = 'active'
                 ORDER BY supplier_name ASC";
$suppliersRs = mysqli_query($conn, $suppliersSql);
$suppliers = mysqli_fetch_all($suppliersRs, MYSQLI_ASSOC);

// Lấy danh sách danh mục (dùng cho modal thêm sản phẩm mới)
$categoriesSql = "SELECT category_id, category_name FROM category ORDER BY category_name ASC";
$categoriesRs = mysqli_query($conn, $categoriesSql);
$categories = $categoriesRs ? mysqli_fetch_all($categoriesRs, MYSQLI_ASSOC) : [];

// (Phần GET PHP render form Sửa/Chi tiết đã chuyển sang Modal + API AJAX)

// ---------
// PHP Filter & Lịch sử phiếu nhập
// ---------
$filterProduct = isset($_GET['filter_product']) ? trim($_GET['filter_product']) : '';
$filterSupplier = isset($_GET['filter_supplier']) ? (int)$_GET['filter_supplier'] : 0;
$filterCreator = isset($_GET['filter_creator']) ? (int)$_GET['filter_creator'] : 0;
$filterDateFrom = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
$filterDateTo = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';

$filterConditions = [];
$joinDetail = false;

$baseSelect = "SELECT r.receipt_id, r.receipt_code, r.import_date, r.total_value, s.supplier_name, a.full_name AS created_by_name";
$baseFrom = "FROM inventory_receipts r
             INNER JOIN suppliers s ON s.supplier_id = r.supplier_id
             INNER JOIN accounts a ON a.account_id = r.created_by";

if ($filterProduct !== '') {
    $joinDetail = true;
    $safeProduct = mysqli_real_escape_string($conn, $filterProduct);
    $filterConditions[] = "i.item_name LIKE '%{$safeProduct}%'";
}
if ($filterSupplier > 0) {
    $filterConditions[] = "r.supplier_id = {$filterSupplier}";
}
if ($filterCreator > 0) {
    $filterConditions[] = "r.created_by = {$filterCreator}";
}
if ($filterDateFrom !== '') {
    $safeDateFrom = mysqli_real_escape_string($conn, $filterDateFrom);
    $filterConditions[] = "DATE(r.import_date) >= '{$safeDateFrom}'";
}
if ($filterDateTo !== '') {
    $safeDateTo = mysqli_real_escape_string($conn, $filterDateTo);
    $filterConditions[] = "DATE(r.import_date) <= '{$safeDateTo}'";
}

if ($joinDetail) {
    $baseSelect = "SELECT DISTINCT r.receipt_id, r.receipt_code, r.import_date, r.total_value, s.supplier_name, a.full_name AS created_by_name";
    $baseFrom .= " INNER JOIN inventory_receipt_items d ON d.receipt_id = r.receipt_id
                   INNER JOIN items i ON i.item_id = d.item_id";
}

$whereClause = count($filterConditions) > 0 ? "WHERE " . implode(' AND ', $filterConditions) : "";
$limitClause = count($filterConditions) > 0 ? "" : "LIMIT 20";

$receiptsSql = "{$baseSelect} {$baseFrom} {$whereClause} ORDER BY r.receipt_id DESC {$limitClause}";
$receiptsRs = mysqli_query($conn, $receiptsSql);
$receipts = mysqli_fetch_all($receiptsRs, MYSQLI_ASSOC);

// Lấy danh sách nhân viên để filter
$creatorsSql = "SELECT account_id, full_name FROM accounts WHERE system_status = 'active' ORDER BY full_name ASC";
$creatorsRs = mysqli_query($conn, $creatorsSql);
$creators = mysqli_fetch_all($creatorsRs, MYSQLI_ASSOC);

// Build options HTML để dùng trong JS template (tránh PHP loop trong JS)

ob_start();
foreach ($products as $p):
    $optLabel = '[' . htmlspecialchars($p['item_code'] ?? '') . '] ' . htmlspecialchars($p['item_name']) . ' (Tồn: ' . (int)$p['stock_quantity'] . ')';
?>
<option value="<?= (int)$p['item_id'] ?>"><?= $optLabel ?></option>
<?php endforeach;
$productOptionsHtml = ob_get_clean();
?>

<div class="dash_board px-2">
    <h1 class="head-name">PHIẾU NHẬP KHO</h1>
    <div class="head-line"></div>

    <div class="container-fluid my-3">
        <?php if (isset($_SESSION['warehouse_receipt_success'])): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($_SESSION['warehouse_receipt_success']) ?>
            </div>
            <?php unset($_SESSION['warehouse_receipt_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['warehouse_receipt_error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($_SESSION['warehouse_receipt_error']) ?>
            </div>
            <?php unset($_SESSION['warehouse_receipt_error']); ?>
        <?php endif; ?>
    </div>

    <div class="container-fluid text-end mb-3">
        <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalCreateReceipt">
            <i class="fa-solid fa-plus"></i> Thêm phiếu nhập
        </button>
    </div>

    <!-- Filter & List Area -->
    <div class="container-fluid">




            <div class="card mb-3">
                <div class="card-header fw-bold bg-light">
                    <i class="fa-solid fa-filter me-1"></i> Tìm kiếm và Lọc phiếu nhập
                </div>
                <div class="card-body">
                    <form method="GET" action="user_page.php" id="filter-receipt-form">
                        <input type="hidden" name="phieunhap" value="1">
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label class="form-label mb-0 text-muted small">Tên sản phẩm</label>
                                <input type="text" class="form-control form-control-sm" name="filter_product" value="<?= htmlspecialchars($filterProduct) ?>" placeholder="Tên sản phẩm bao gồm...">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label mb-0 text-muted small">Nhà cung cấp</label>
                                <select class="form-select form-select-sm" name="filter_supplier">
                                    <option value="">-- Tất cả --</option>
                                    <?php foreach ($suppliers as $s): ?>
                                        <option value="<?= (int)$s['supplier_id'] ?>" <?= $filterSupplier === (int)$s['supplier_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['supplier_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label mb-0 text-muted small">Người lập</label>
                                <select class="form-select form-select-sm" name="filter_creator">
                                    <option value="">-- Tất cả --</option>
                                    <?php foreach ($creators as $c): ?>
                                        <option value="<?= (int)$c['account_id'] ?>" <?= $filterCreator === (int)$c['account_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label mb-0 text-muted small">Từ ngày</label>
                                <input type="date" class="form-control form-control-sm" name="filter_date_from" value="<?= htmlspecialchars($filterDateFrom) ?>">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label mb-0 text-muted small">Đến ngày</label>
                                <input type="date" class="form-control form-control-sm" name="filter_date_to" value="<?= htmlspecialchars($filterDateTo) ?>">
                            </div>
                            <div class="col-12 mt-1 d-flex justify-content-end gap-2">
                                <a href="user_page.php?phieunhap" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-magnifying-glass"></i> Lọc
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-bold">Danh sách phiếu nhập <?= count($filterConditions) > 0 ? '(Đã lọc)' : 'gần đây' ?></div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0 text-center">
                        <thead>
                            <tr>
                                <th>Mã phiếu</th>
                                <th>Ngày nhập</th>
                                <th>Nhà cung cấp</th>
                                <th>Tổng giá trị</th>
                                <th>Người tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($receipts as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['receipt_code']) ?></td>
                                    <td><?= htmlspecialchars($r['import_date']) ?></td>
                                    <td><?= htmlspecialchars($r['supplier_name']) ?></td>
                                    <td><?= number_format((float)$r['total_value'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($r['created_by_name']) ?></td>
                                    <td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info btn-view-receipt" data-id="<?= (int)$r['receipt_id'] ?>" title="Xem Chi tiết">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary btn-edit-receipt" data-id="<?= (int)$r['receipt_id'] ?>" title="Sửa thông tin">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
</div>

<!-- Modal Thêm phiếu nhập -->
<div class="modal fade" id="modalCreateReceipt" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-1"></i> Tạo phiếu nhập mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form method="POST" action="user_page.php?phieunhap" id="warehouse-create-receipt-form" onsubmit="return validateCreateReceiptForm()">
                    <input type="hidden" name="warehouse_receipt_submit" value="1">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nhà cung cấp (*)</label>
                            <select class="form-select shadow-sm" name="supplier_id" required>
                                <option value="">-- Chọn nhà cung cấp --</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= (int)$s['supplier_id'] ?>">
                                        <?= htmlspecialchars($s['supplier_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ngày nhập (*)</label>
                            <input class="form-control shadow-sm" type="date" name="import_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="card mb-3 shadow-sm">
                        <div class="card-header fw-bold bg-white">Danh sách sản phẩm nhập</div>
                        <div class="card-body pt-2" id="create-receipt-lines">
                            <div class="row g-2 align-items-end receipt-line-row mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Sản phẩm</label>
                                    <select class="form-select item-select" name="item_id[]" required>
                                        <option value="">-- Chọn sản phẩm --</option>
                                        <?= $productOptionsHtml ?>
                                        <option value="__new__" class="text-success fw-bold">＋ Thêm sản phẩm mới...</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Số lượng</label>
                                    <input class="form-control" type="number" min="1" name="quantity[]" placeholder="SL..." required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Giá nhập</label>
                                    <input class="form-control" type="number" min="0" step="1000" name="import_price[]" placeholder="VND" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Giá bán</label>
                                    <input class="form-control" type="number" min="0" step="1000" name="unit_price[]" placeholder="VND" required>
                                </div>
                                <div class="col-md-1">
                                    <button class="btn btn-outline-danger w-100 remove-receipt-line" type="button" title="Xóa dòng này">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white pb-3 border-0">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-create-receipt-line">
                                <i class="fa-solid fa-plus"></i> Thêm dòng sản phẩm
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Ghi chú</label>
                        <textarea class="form-control shadow-sm" name="note" rows="2" placeholder="Ghi chú phiếu nhập..."></textarea>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            <i class="fa-solid fa-file-import me-1"></i> Hoàn tất tạo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sửa/View Phiếu Nhập -->
<div class="modal fade" id="modalEditReceipt" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalEditReceiptTitle"><i class="fa-solid fa-pen-to-square me-1"></i> Sửa thông tin phiếu nhập</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <!-- Nội dung được load qua AJAX -->
                <div id="modalEditBodyLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>

                <form method="POST" action="user_page.php?phieunhap" id="warehouse-edit-receipt-form" style="display:none;" onsubmit="return validateEditReceiptForm()">
                    <input type="hidden" name="warehouse_receipt_update_submit" value="1">
                    <input type="hidden" name="receipt_id" id="edit_modal_receipt_id" value="0">

                    <div class="alert alert-info border-0 shadow-sm mb-3">
                        <strong>Mã phiếu: </strong> <span id="edit_modal_receipt_code" class="font-monospace fw-bold fs-5"></span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nhà cung cấp (Có thể sửa)</label>
                            <select class="form-select shadow-sm" name="supplier_id" id="edit_modal_supplier_id" required>
                                <option value="">-- Chọn nhà cung cấp --</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= (int)$s['supplier_id'] ?>">
                                        <?= htmlspecialchars($s['supplier_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ngày nhập (Có thể sửa)</label>
                            <input class="form-control shadow-sm" type="date" name="import_date" id="edit_modal_import_date" required>
                        </div>
                    </div>

                    <div class="card mb-3 shadow-sm border-warning">
                        <div class="card-header fw-bold bg-warning text-dark opacity-75">
                            Chi tiết sản phẩm (KHÔNG được phép sửa nội dung này)
                        </div>
                        <div class="card-body p-0">
                            <!-- Show as a read-only table instead of inputs -->
                            <table class="table table-striped mb-0 text-center">
                                <thead>
                                    <tr>
                                        <th class="text-start">Tên sản phẩm</th>
                                        <th>Số lượng</th>
                                        <th class="text-end">Giá nhập</th>
                                        <th class="text-end">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody id="edit_modal_lines_table">
                                    <!-- AJAX rows go here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Ghi chú (Có thể sửa)</label>
                        <textarea class="form-control shadow-sm" name="note" id="edit_modal_note" rows="2" placeholder="Ghi chú phiếu nhập..."></textarea>
                    </div>

                    <div class="text-end mt-4 form-actions d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-primary fw-bold" id="btnPrintReceipt" onclick="printReceiptDetail()">
                            <i class="fa-solid fa-print me-1"></i> In phiếu
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4" id="btnUpdateReceipt">
                            <i class="fa-solid fa-save me-1"></i> Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Modal thêm sản phẩm mới -->
<div class="modal fade" id="modalNewItem" tabindex="-1" aria-labelledby="modalNewItemLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNewItemLabel"><i class="fa-solid fa-box me-2"></i>Thêm sản phẩm mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modal-item-alert" class="alert d-none"></div>
                <div class="mb-2">
                    <label class="form-label fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="new_item_name" placeholder="Nhập tên sản phẩm">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold">Danh mục</label>
                    <select class="form-select" id="new_item_category">
                        <option value="0">-- Không phân loại --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold">Mô tả <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="new_item_description" placeholder="Mô tả ngắn về sản phẩm">
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label fw-bold">Giá nhập (VND)</label>
                        <input type="number" class="form-control" id="new_item_purchase_price" min="0" step="1000" placeholder="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Giá bán (VND) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="new_item_unit_price" min="1" step="1000" placeholder="0">
                    </div>
                </div>
                <small class="text-muted mt-1 d-block"><span class="text-danger">*</span> Bắt buộc nhập. Tồn kho ban đầu = 0, sẽ được cộng khi lưu phiếu nhập.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success fw-bold" id="btn-save-new-item">
                    <i class="fa-solid fa-plus me-1"></i>Thêm sản phẩm
                </button>
            </div>
        </div>
    </div>
</div>

<template id="receipt-line-template">
    <div class="row g-2 align-items-end receipt-line-row mb-2">
        <div class="col-md-4">
            <label class="form-label">Sản phẩm</label>
            <select class="form-select item-select" name="item_id[]" required>
                <option value="">-- Chọn sản phẩm --</option>
                <?= $productOptionsHtml ?>
                <option value="__new__" class="text-success fw-bold">＋ Thêm sản phẩm mới...</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Số lượng</label>
            <input class="form-control" type="number" min="1" name="quantity[]" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Giá nhập</label>
            <input class="form-control" type="number" min="0" step="1000" name="import_price[]" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Giá bán</label>
            <input class="form-control" type="number" min="0" step="1000" name="unit_price[]" required>
        </div>
        <div class="col-md-1">
            <button class="btn btn-outline-danger w-100 remove-receipt-line" type="button">X</button>
        </div>
    </div>
</template>

<script>
(function () {
    const template       = document.getElementById('receipt-line-template');
    const addCreateBtn   = document.getElementById('add-create-receipt-line');
    const addEditBtn     = document.getElementById('add-edit-receipt-line');
    const createContainer = document.getElementById('create-receipt-lines');
    const editContainer  = document.getElementById('edit-receipt-lines');

    // Map sản phẩm: item_id → { purchase_price, unit_price } để auto-fill
    const PRODUCT_MAP = (function() {
        const map = {};
        <?php foreach ($products as $p): ?>
        map[<?= (int)$p['item_id'] ?>] = {
            purchase_price: <?= (float)$p['purchase_price'] ?>,
            unit_price: <?= (float)$p['unit_price'] ?>
        };
        <?php endforeach; ?>
        return map;
    })();

    // Select đang chờ (khi user chọn "Thêm mới...")
    let pendingSelect = null;

    // ────── Thêm dòng sản phẩm ──────
    function addLine(container) {
        if (!container || !template) return;
        const node = template.content.cloneNode(true);
        container.appendChild(node);
        // Bind change event cho select vừa thêm
        const selects = container.querySelectorAll('.item-select');
        bindNewItemTrigger(selects[selects.length - 1]);
    }

    function bindRemove(container) {
        if (!container) return;
        container.addEventListener('click', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            if (!target.classList.contains('remove-receipt-line')) return;
            const row = target.closest('.receipt-line-row');
            if (!row) return;
            const rows = container.querySelectorAll('.receipt-line-row');
            if (rows.length <= 1) return;
            row.remove();
        });
    }

    if (addCreateBtn) {
        addCreateBtn.addEventListener('click', function () { addLine(createContainer); });
    }
    if (addEditBtn) {
        addEditBtn.addEventListener('click', function () { addLine(editContainer); });
    }

    bindRemove(createContainer);
    bindRemove(editContainer);

    // Bind cho các select đã render sẵn (PHP render)
    document.querySelectorAll('.item-select').forEach(bindNewItemTrigger);

    // ────── Auto-fill giá nhập / giá bán khi chọn sản phẩm ──────
    function fillPricesForRow(sel) {
        const itemId = parseInt(sel.value, 10);
        if (!itemId || isNaN(itemId)) return;
        const info = PRODUCT_MAP[itemId];
        if (!info) return;

        const row = sel.closest('.receipt-line-row');
        if (!row) return;

        // Điền giá nhập (import_price) nếu ô chưa có giá trị hoặc đang là 0
        const importInput = row.querySelector('input[name="import_price[]"]');
        if (importInput && (!importInput.value || parseFloat(importInput.value) === 0)) {
            importInput.value = info.purchase_price > 0 ? info.purchase_price : '';
        }

        // Điền giá bán (unit_price) nếu ô chưa có giá trị hoặc đang là 0
        const unitInput = row.querySelector('input[name="unit_price[]"]');
        if (unitInput && (!unitInput.value || parseFloat(unitInput.value) === 0)) {
            unitInput.value = info.unit_price > 0 ? info.unit_price : '';
        }
    }

    // ────── Xử lý chọn sản phẩm (bao gồm "Thêm mới...") ──────
    function bindNewItemTrigger(sel) {
        if (!sel) return;
        sel.addEventListener('change', function () {
            const val = this.value;
            if (val === '__new__') {
                pendingSelect = this;
                this.value = ''; // reset về trống trong khi chờ
                openNewItemModal();
                return;
            } 
            
            if (val && val !== '') {
                // Kiểm tra xem sản phẩm đã có ở dòng khác chưa
                const container = this.closest('#create-receipt-lines') || this.closest('#edit-receipt-lines');
                if (container) {
                    const allSelects = container.querySelectorAll('.item-select');
                    let count = 0;
                    allSelects.forEach(s => {
                        if (s.value === val) count++;
                    });

                    if (count > 1) {
                        alert('Sản phẩm đã tồn tại trong phiếu nhập');
                        this.value = '';
                        return;
                    }
                }

                // Sản phẩm hợp lệ → auto-fill thông tin
                fillPricesForRow(this);
            }
        });
    }

    function openNewItemModal() {
        // Reset form modal
        document.getElementById('new_item_name').value = '';
        document.getElementById('new_item_category').value = '0';
        document.getElementById('new_item_description').value = '';
        document.getElementById('new_item_purchase_price').value = '';
        document.getElementById('new_item_unit_price').value = '';
        const alertEl = document.getElementById('modal-item-alert');
        alertEl.className = 'alert d-none';
        alertEl.textContent = '';

        const modal = new bootstrap.Modal(document.getElementById('modalNewItem'));
        modal.show();
    }

    // ────── Lưu sản phẩm mới qua AJAX ──────
    document.getElementById('btn-save-new-item').addEventListener('click', function () {
        const alertEl = document.getElementById('modal-item-alert');
        const btn     = this;

        const item_name      = document.getElementById('new_item_name').value.trim();
        const category_id    = document.getElementById('new_item_category').value;
        const description    = document.getElementById('new_item_description').value.trim();
        const purchase_price = document.getElementById('new_item_purchase_price').value || '0';
        const unit_price     = document.getElementById('new_item_unit_price').value;

        // Client-side validate
        if (!item_name) {
            showAlert(alertEl, 'danger', 'Vui lòng nhập tên sản phẩm.');
            return;
        }
        if (!description) {
            showAlert(alertEl, 'danger', 'Vui lòng nhập mô tả sản phẩm.');
            return;
        }
        if (!unit_price || parseFloat(unit_price) <= 0) {
            showAlert(alertEl, 'danger', 'Giá bán phải lớn hơn 0.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang lưu...';

        const formData = new FormData();
        formData.append('create_item_api', '1');
        formData.append('item_name', item_name);
        formData.append('category_id', category_id);
        formData.append('description', description);
        formData.append('purchase_price', purchase_price);
        formData.append('unit_price', unit_price);

        fetch('user_page.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert(alertEl, 'success', data.message);

                    // Thêm option mới vào TẤT CẢ select sản phẩm trên trang
                    addOptionToAllSelects(data.item_id, data.item_code, data.item_name);

                    // Chọn sản phẩm vừa tạo vào select đang chờ
                    if (pendingSelect) {
                        pendingSelect.value = String(data.item_id);
                        pendingSelect = null;
                    }

                    // Đóng modal sau 1 giây
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('modalNewItem'))?.hide();
                    }, 900);
                } else {
                    showAlert(alertEl, 'danger', data.message || 'Có lỗi xảy ra.');
                }
            })
            .catch(() => showAlert(alertEl, 'danger', 'Lỗi kết nối. Vui lòng thử lại.'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Thêm sản phẩm';
            });
    });

    // ────── Helper: thêm option vào tất cả select có class item-select ──────
    function addOptionToAllSelects(itemId, itemCode, itemName) {
        const itemText = '[' + (itemCode || '') + '] ' + itemName + ' (Tồn: 0)';
        document.querySelectorAll('.item-select').forEach(function (sel) {
            // Kiểm tra chưa có option này
            if (sel.querySelector('option[value="' + itemId + '"]')) return;

            // Chèn trước option "__new__"
            const newOpt = document.createElement('option');
            newOpt.value = itemId;
            newOpt.textContent = itemText;

            const newItemOpt = sel.querySelector('option[value="__new__"]');
            if (newItemOpt) {
                sel.insertBefore(newOpt, newItemOpt);
            } else {
                sel.appendChild(newOpt);
            }
        });

        // Cũng cập nhật template để dòng mới thêm sau cũng có option này
        if (template) {
            const templateSel = template.content.querySelector('.item-select');
            if (templateSel && !templateSel.querySelector('option[value="' + itemId + '"]')) {
                const newOpt = document.createElement('option');
                newOpt.value = itemId;
                newOpt.textContent = itemText;
                const newItemOpt = templateSel.querySelector('option[value="__new__"]');
                if (newItemOpt) {
                    templateSel.insertBefore(newOpt, newItemOpt);
                } else {
                    templateSel.appendChild(newOpt);
                }
            }
        }
    }

    function showAlert(el, type, msg) {
        el.className = 'alert alert-' + type;
        el.textContent = msg;
    }

    // ────── Validate form trước khi submit ──────
    function validateReceiptForm(form) {
        const selects = form.querySelectorAll('select[name="item_id[]"]');
        let valid = true;
        selects.forEach(function (sel) {
            if (!sel.value || sel.value === '' || sel.value === '__new__') {
                sel.classList.add('is-invalid');
                valid = false;
            } else {
                sel.classList.remove('is-invalid');
            }
        });
        if (!valid) {
            alert('Vui lòng chọn sản phẩm hợp lệ cho tất cả các dòng trước khi lưu phiếu.');
        }
        return valid;
    }

    const createForm = document.getElementById('warehouse-create-receipt-form');
    if (createForm) {
        createForm.addEventListener('submit', function (e) {
            if (!validateReceiptForm(this)) e.preventDefault();
        });
    }
    const editForm = document.getElementById('warehouse-edit-receipt-form');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            if (!validateReceiptForm(this)) e.preventDefault();
        });
    }
    // ────── Validation & Edit/View Modal Handler ──────
    
    window.validateCreateReceiptForm = function() {
        const lines = document.querySelectorAll('#create-receipt-lines .receipt-line-row');
        if (lines.length === 0) {
            alert('Vui lòng thêm ít nhất một sản phẩm vào phiếu nhập.');
            return false;
        }
        let isValid = true;
        lines.forEach(row => {
            const item = row.querySelector('.item-select').value;
            const qty = row.querySelector('input[name="quantity[]"]').value;
            const price = row.querySelector('input[name="import_price[]"]').value;
            if (!item || item === '__new__') isValid = false;
            if (!qty || parseFloat(qty) <= 0) isValid = false;
            if (!price || parseFloat(price) < 0) isValid = false;
        });
        if (!isValid) {
            alert('Vui lòng kiểm tra lại thông tin sản phẩm (chưa chọn sản phẩm, số lượng <= 0, hoặc giá nhập < 0).');
            return false;
        }
        return true;
    };

    window.validateEditReceiptForm = function() {
        const supplier = document.getElementById('edit_modal_supplier_id').value;
        const date = document.getElementById('edit_modal_import_date').value;
        if (!supplier || !date) {
            alert('Vui lòng điền đầy đủ nhà cung cấp và ngày nhập.');
            return false;
        }
        return true;
    };

    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.btn-edit-receipt');
        const viewBtn = e.target.closest('.btn-view-receipt');
        
        if (editBtn || viewBtn) {
            const isViewMode = !!viewBtn;
            const btnId = isViewMode ? viewBtn.dataset.id : editBtn.dataset.id;
            if (!btnId) return;

            // Reset UI Modal
            document.getElementById('warehouse-edit-receipt-form').style.display = 'none';
            document.getElementById('modalEditBodyLoading').style.display = 'block';
            document.getElementById('edit_modal_lines_table').innerHTML = '';
            
            // Show Modal
            const modalEl = document.getElementById('modalEditReceipt');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            fetch('user_page.php?get_receipt_api=1&receipt_id=' + btnId)
                .then(res => res.json())
                .then(res => {
                    if (!res.success) {
                        alert(res.message || 'Lỗi hệ thống hoặc phiếu nhập không tồn tại.');
                        modal.hide();
                        return;
                    }

                    // Populate Form
                    const r = res.data;
                    document.getElementById('edit_modal_receipt_id').value = r.receipt_id;
                    document.getElementById('edit_modal_receipt_code').textContent = r.receipt_code;
                    document.getElementById('edit_modal_supplier_id').value = r.supplier_id;
                    document.getElementById('edit_modal_import_date').value = r.import_date;
                    document.getElementById('edit_modal_note').value = r.note || '';

                    // Render table lines
                    let html = '';
                    if (r.items && r.items.length > 0) {
                        let totalValue = 0;
                        r.items.forEach(item => {
                            const sum = parseFloat(item.quantity) * parseFloat(item.import_price);
                            totalValue += sum;
                            html += `<tr>
                                <td class="text-start">${item.item_name}</td>
                                <td>${item.quantity}</td>
                                <td class="text-end text-primary">${Number(item.import_price).toLocaleString()} ₫</td>
                                <td class="text-end fw-bold text-danger">${Number(sum).toLocaleString()} ₫</td>
                            </tr>`;
                        });
                        html += `<tr>
                            <td colspan="3" class="text-end fw-bold text-uppercase">Tổng cộng</td>
                            <td class="text-end fw-bold text-danger fs-5">${Number(totalValue).toLocaleString()} ₫</td>
                        </tr>`;
                    } else {
                        html = '<tr><td colspan="4" class="text-muted">Không có sản phẩm nào.</td></tr>';
                    }
                    document.getElementById('edit_modal_lines_table').innerHTML = html;

                    // Configure View Mode vs Edit Mode
                    const titleStr = isViewMode ? '<i class="fa-solid fa-eye me-1"></i> Xem chi tiết phiếu nhập' : '<i class="fa-solid fa-pen-to-square me-1"></i> Sửa thông tin chung phiếu nhập';
                    document.getElementById('modalEditReceiptTitle').innerHTML = titleStr;

                    const form = document.getElementById('warehouse-edit-receipt-form');
                    const lockFields = isViewMode;

                    // Disable inputs if view mode
                    const inputs = ['edit_modal_supplier_id', 'edit_modal_import_date', 'edit_modal_note'];
                    inputs.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            if (el.tagName === 'SELECT') {
                                el.style.pointerEvents = lockFields ? 'none' : 'auto';
                                el.style.backgroundColor = lockFields ? '#e9ecef' : '';
                            } else {
                                el.readOnly = lockFields;
                            }
                        }
                    });

                    // Hide save button if view mode
                    const btnSave = document.getElementById('btnUpdateReceipt');
                    if (lockFields) {
                        btnSave.style.display = 'none';
                    } else {
                        btnSave.style.display = 'inline-block';
                    }

                    // Reveal form
                    document.getElementById('modalEditBodyLoading').style.display = 'none';
                    form.style.display = 'block';

                    // Đảm bảo nút in hiển thị
                    document.getElementById('btnPrintReceipt').style.display = 'inline-block';
                })
                .catch(err => {
                    alert('Lỗi kết nối API. Vui lòng thử lại.');
                    modal.hide();
                });
        }
    });

    // ────── Hàm in chi tiết phiếu nhập ──────
    window.printReceiptDetail = function() {
        const code = document.getElementById('edit_modal_receipt_code').textContent;
        const supplierSelect = document.getElementById('edit_modal_supplier_id');
        const supplier = supplierSelect.options[supplierSelect.selectedIndex].text;
        const date = document.getElementById('edit_modal_import_date').value;
        const note = document.getElementById('edit_modal_note').value;
        const tableHtml = document.getElementById('edit_modal_lines_table').innerHTML;

        const printWin = window.open('', '_blank', 'width=900,height=800');
        printWin.document.write(`
            <html>
            <head>
                <title>In Phiếu Nhập - ${code}</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
                <style>
                    body { padding: 40px; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
                    .print-header { text-align: center; border-bottom: 2px solid #333; margin-bottom: 30px; padding-bottom: 10px; }
                    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
                    .info-item b { width: 120px; display: inline-block; }
                    th { background-color: #f8f9fa !important; }
                    .footer-note { margin-top: 40px; border-top: 1px dashed #ccc; padding-top: 20px; }
                    @media print {
                        .no-print { display: none; }
                        body { padding: 0; }
                    }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h2 class="fw-bold mb-1">CỬA HÀNG COFFEE SHOP</h2>
                    <p class="mb-0">Hệ Thống Quản Lý Kho & Bán Hàng</p>
                    <h4 class="mt-3 text-uppercase fw-bold">PHIẾU NHẬP KHO</h4>
                </div>

                <div class="info-grid">
                    <div class="info-item"><b>Mã số phiếu:</b> <span class="font-monospace fw-bold">${code}</span></div>
                    <div class="info-item"><b>Ngày nhập:</b> ${date}</div>
                    <div class="info-item"><b>Nhà cung cấp:</b> ${supplier}</div>
                    <div class="info-item"><b>Ghi chú:</b> ${note || '...'}</div>
                </div>

                <table class="table table-bordered text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Tên sản phẩm</th>
                            <th width="15%">Số lượng</th>
                            <th width="20%">Giá nhập</th>
                            <th width="20%">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableHtml}
                    </tbody>
                </table>

                <div class="footer-note d-flex justify-content-between">
                    <div class="text-center" style="width: 200px;">
                        <p class="mb-5">Người lập phiếu</p>
                        <br><br>
                        <b>(Ký tên)</b>
                    </div>
                    <div class="text-center" style="width: 200px;">
                        <p class="mb-5">Người giao hàng</p>
                        <br><br>
                        <b>(Ký tên)</b>
                    </div>
                </div>

                <div class="mt-5 text-center small text-muted">
                    <i>Ngày in: ${new Date().toLocaleString('vi-VN')}</i>
                </div>

                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(() => window.close(), 500);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWin.document.close();
    };

})();
</script>
