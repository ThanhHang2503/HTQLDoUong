<?php
// Phiếu xuất kho
requirePermission(AppPermission::MANAGE_WAREHOUSE);
global $conn;

$msg_success = '';
$msg_error   = '';
$uid = currentUserId();

// Tạo phiếu xuất
if (isset($_POST['create_export'])) {
    $export_date = trim($_POST['export_date'] ?? date('Y-m-d'));
    $note  = trim(mysqli_real_escape_string($conn, $_POST['note'] ?? ''));
    $reason = trim(mysqli_real_escape_string($conn, $_POST['reason'] ?? 'bán hàng'));
    $items_ids  = $_POST['item_ids']  ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $prices     = $_POST['prices']    ?? [];

    if (empty($items_ids)) {
        $msg_error = 'Vui lòng thêm ít nhất một sản phẩm.';
    } else {
        // Generate export code
        $export_code = 'PX-' . date('Ymd') . '-' . sprintf('%03d', rand(1, 999));
        $total = 0;

        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "INSERT INTO inventory_exports (export_code, export_date, reason, note, created_by)
                                 VALUES ('$export_code', '$export_date', '$reason', '$note', $uid)");
            $export_id = (int)mysqli_insert_id($conn);

            foreach ($items_ids as $k => $item_id) {
                $item_id  = (int)$item_id;
                $qty      = (int)($quantities[$k] ?? 0);
                $price    = (float)($prices[$k] ?? 0);
                if ($item_id <= 0 || $qty <= 0) continue;

                // Kiểm tra tồn kho
                $stock_r = mysqli_query($conn, "SELECT stock_quantity, purchase_price FROM items WHERE item_id=$item_id FOR UPDATE");
                $stock_row = mysqli_fetch_assoc($stock_r);
                if (!$stock_row || $stock_row['stock_quantity'] < $qty) {
                    throw new Exception("Sản phẩm #$item_id không đủ số lượng tồn kho.");
                }
                $purchase_price = (float)$stock_row['purchase_price'];
                $line_total = $qty * $price;
                $total += $line_total;

                mysqli_query($conn, "INSERT INTO inventory_export_items (export_id, item_id, quantity, unit_price, purchase_price, line_total)
                                     VALUES ($export_id, $item_id, $qty, $price, $purchase_price, $line_total)");

                // Cập nhật tồn kho
                $old_stock = $stock_row['stock_quantity'];
                $new_stock = $old_stock - $qty;
                mysqli_query($conn, "UPDATE items SET stock_quantity=$new_stock WHERE item_id=$item_id");

                // Ghi movement
                mysqli_query($conn, "INSERT INTO stock_movements (item_id, movement_type, quantity_change, stock_before, stock_after,
                                      unit_cost, reference_type, reference_id, note, created_by)
                                     VALUES ($item_id, 'export', -$qty, $old_stock, $new_stock, $price, 'inventory_export', $export_id, '$note', $uid)");
            }
            // Cập nhật total
            mysqli_query($conn, "UPDATE inventory_exports SET total_value=$total WHERE export_id=$export_id");
            mysqli_commit($conn);
            $msg_success = "Tạo phiếu xuất $export_code thành công! Tổng giá trị: " . number_format($total,0,',','.') . ' VND.';
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $msg_error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Xóa phiếu xuất (hoàn kho)
if (isset($_POST['delete_export'])) {
    $eid = (int)($_POST['export_id'] ?? 0);
    // Lấy các items trong phiếu
    $items_r = mysqli_query($conn, "SELECT ei.item_id, ei.quantity FROM inventory_export_items ei WHERE ei.export_id=$eid");
    $items_to_restore = $items_r ? mysqli_fetch_all($items_r, MYSQLI_ASSOC) : [];

    mysqli_begin_transaction($conn);
    try {
        foreach ($items_to_restore as $it) {
            $item_id = (int)$it['item_id'];
            $qty = (int)$it['quantity'];
            $sr = mysqli_query($conn, "SELECT stock_quantity FROM items WHERE item_id=$item_id");
            $old = (int)mysqli_fetch_assoc($sr)['stock_quantity'];
            $new = $old + $qty;
            mysqli_query($conn, "UPDATE items SET stock_quantity=$new WHERE item_id=$item_id");
            mysqli_query($conn, "INSERT INTO stock_movements (item_id, movement_type, quantity_change, stock_before, stock_after,
                                  unit_cost, reference_type, reference_id, note, created_by)
                                 VALUES ($item_id, 'export_delete', $qty, $old, $new, 0, 'inventory_export', $eid, 'Xóa phiếu xuất', $uid)");
        }
        mysqli_query($conn, "DELETE FROM inventory_exports WHERE export_id=$eid");
        mysqli_commit($conn);
        $msg_success = 'Đã xóa phiếu xuất và hoàn kho.';
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $msg_error = 'Lỗi: ' . $e->getMessage();
    }
}

// Lấy danh sách phiếu xuất (lọc theo tháng/năm nếu có)
$filter_year  = (int)($_GET['year'] ?? 0);
$filter_month = (int)($_GET['month'] ?? 0);
$where = 'WHERE 1=1';
if ($filter_year > 0)  $where .= " AND YEAR(ie.export_date) = $filter_year";
if ($filter_month > 0) $where .= " AND MONTH(ie.export_date) = $filter_month";

$exports_sql = "SELECT ie.export_id, ie.export_code, ie.export_date, ie.reason,
                       ie.total_value, ie.note, ie.created_at,
                       a.full_name AS created_by_name,
                       (SELECT COUNT(*) FROM inventory_export_items WHERE export_id=ie.export_id) AS item_count
                FROM inventory_exports ie
                JOIN accounts a ON a.account_id = ie.created_by
                $where
                ORDER BY ie.export_date DESC, ie.created_at DESC
                LIMIT 50";
$exports_result = mysqli_query($conn, $exports_sql);
$exports = $exports_result ? mysqli_fetch_all($exports_result, MYSQLI_ASSOC) : [];

// Lấy sản phẩm còn hàng
$items_sql = "SELECT item_id, item_name, stock_quantity, unit_price, purchase_price FROM items
              WHERE item_status='active' AND stock_quantity > 0
              ORDER BY item_name";
$items_result = mysqli_query($conn, $items_sql);
$available_items = $items_result ? mysqli_fetch_all($items_result, MYSQLI_ASSOC) : [];

// Detail phiếu xuất
$detail_id = (int)($_GET['detail'] ?? 0);
$detail_items = [];
$detail_info  = null;
if ($detail_id > 0) {
    $dir = mysqli_query($conn, "SELECT ie.*, a.full_name AS created_by_name FROM inventory_exports ie
                                 JOIN accounts a ON a.account_id=ie.created_by WHERE ie.export_id=$detail_id");
    $detail_info = $dir ? mysqli_fetch_assoc($dir) : null;
    $dit = mysqli_query($conn, "SELECT ei.*, i.item_name FROM inventory_export_items ei
                                  JOIN items i ON i.item_id=ei.item_id WHERE ei.export_id=$detail_id");
    $detail_items = $dit ? mysqli_fetch_all($dit, MYSQLI_ASSOC) : [];
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">PHIẾU XUẤT KHO</h1>
    <div class="head-line"></div>

    <?php if ($msg_success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg_success) ?></div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($msg_error) ?></div>
    <?php endif; ?>

    <div class="container-fluid">
        <div class="row g-3 mt-1">
            <!-- Form tạo phiếu xuất -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold bg-warning text-dark">
                        <i class="fa-solid fa-file-export me-2"></i>Tạo Phiếu Xuất Kho
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="exportForm">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold">Ngày xuất</label>
                                    <input type="date" class="form-control" name="export_date"
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold">Lý do xuất</label>
                                    <select class="form-select" name="reason">
                                        <option value="bán hàng">Bán hàng</option>
                                        <option value="hư hỏng">Hư hỏng/thanh lý</option>
                                        <option value="chuyển kho">Chuyển kho</option>
                                        <option value="khác">Khác</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ghi chú</label>
                                <input type="text" class="form-control" name="note" placeholder="Ghi chú phiếu xuất...">
                            </div>

                            <!-- Danh sách sản phẩm xuất -->
                            <label class="form-label fw-bold">Sản phẩm xuất</label>
                            <div id="exportItems">
                                <div class="export-item-row border rounded p-2 mb-2">
                                    <div class="row g-1 align-items-end">
                                        <div class="col-5">
                                            <label class="form-label form-label-sm">Sản phẩm</label>
                                            <select class="form-select form-select-sm" name="item_ids[]" required onchange="fillPrice(this)">
                                                <option value="">-- Chọn SP --</option>
                                                <?php foreach ($available_items as $it): ?>
                                                <option value="<?= $it['item_id'] ?>"
                                                        data-price="<?= $it['unit_price'] ?>"
                                                        data-stock="<?= $it['stock_quantity'] ?>">
                                                    <?= htmlspecialchars($it['item_name']) ?>
                                                    (còn <?= $it['stock_quantity'] ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label form-label-sm">Số lượng</label>
                                            <input type="number" class="form-control form-control-sm" name="quantities[]" min="1" value="1" required>
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label form-label-sm">Giá xuất (VND)</label>
                                            <input type="number" class="form-control form-control-sm" name="prices[]" min="0" step="500" required>
                                        </div>
                                        <div class="col-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">✕</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-3" onclick="addRow()">
                                <i class="fa-solid fa-plus me-1"></i>Thêm sản phẩm
                            </button>
                            <button type="submit" name="create_export" class="btn btn-warning fw-bold w-100">
                                <i class="fa-solid fa-file-export me-1"></i>Tạo phiếu xuất
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danh sách phiếu xuất -->
            <div class="col-lg-7">
                <!-- Bộ lọc -->
                <form class="card shadow-sm mb-3 p-2" method="GET" action="user_page.php">
                    <input type="hidden" name="phieuxuat" value="1">
                    <div class="row g-2 align-items-end">
                        <div class="col">
                            <label class="form-label mb-0">Tháng</label>
                            <select class="form-select form-select-sm" name="month">
                                <option value="0">Tất cả</option>
                                <?php for ($m=1;$m<=12;$m++): ?>
                                <option value="<?=$m?>" <?=$m==$filter_month?'selected':''?>>T<?=$m?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label mb-0">Năm</label>
                            <input type="number" class="form-control form-control-sm" name="year" value="<?= $filter_year ?: date('Y') ?>">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-primary" type="submit"><i class="fa-solid fa-filter"></i></button>
                        </div>
                    </div>
                </form>

                <div class="card shadow-sm">
                    <div class="card-header fw-bold"><i class="fa-solid fa-list me-2"></i>Danh Sách Phiếu Xuất (50 gần nhất)</div>
                    <div class="card-body p-0">
                        <?php if (empty($exports)): ?>
                            <p class="p-3 mb-0 text-muted">Chưa có phiếu xuất nào.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover table-bordered mb-0 text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>Mã phiếu</th>
                                    <th>Ngày xuất</th>
                                    <th>Lý do</th>
                                    <th>SL SP</th>
                                    <th>Tổng giá trị</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exports as $ex): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= htmlspecialchars($ex['export_code']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($ex['export_date'])) ?></td>
                                    <td><?= htmlspecialchars($ex['reason'] ?? '') ?></td>
                                    <td><?= $ex['item_count'] ?></td>
                                    <td class="text-success fw-bold"><?= number_format($ex['total_value'],0,',','.') ?></td>
                                    <td>
                                        <a href="user_page.php?phieuxuat&detail=<?= $ex['export_id'] ?>"
                                           class="btn btn-sm btn-outline-info"><i class="fa-solid fa-eye"></i></a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Xóa phiếu xuất và hoàn kho?')">
                                            <input type="hidden" name="export_id" value="<?= $ex['export_id'] ?>">
                                            <button type="submit" name="delete_export" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chi tiết phiếu -->
                <?php if ($detail_info): ?>
                <div class="card shadow-sm mt-3 border-info">
                    <div class="card-header fw-bold text-info">
                        <i class="fa-solid fa-file-lines me-2"></i>Chi Tiết Phiếu: <?= htmlspecialchars($detail_info['export_code']) ?>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-6"><b>Ngày xuất:</b> <?= date('d/m/Y', strtotime($detail_info['export_date'])) ?></div>
                            <div class="col-6"><b>Người lập:</b> <?= htmlspecialchars($detail_info['created_by_name']) ?></div>
                            <div class="col-6"><b>Lý do:</b> <?= htmlspecialchars($detail_info['reason'] ?? '') ?></div>
                            <div class="col-6"><b>Ghi chú:</b> <?= htmlspecialchars($detail_info['note'] ?? '-') ?></div>
                        </div>
                        <table class="table table-sm table-bordered text-center mb-0">
                            <thead class="table-secondary">
                                <tr><th>Sản phẩm</th><th>SL</th><th>Giá xuất</th><th>Giá nhập CB</th><th>Thành tiền</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail_items as $di): ?>
                                <tr>
                                    <td class="text-start"><?= htmlspecialchars($di['item_name']) ?></td>
                                    <td><?= $di['quantity'] ?></td>
                                    <td><?= number_format($di['unit_price'],0,',','.') ?></td>
                                    <td class="text-muted"><?= number_format($di['purchase_price'],0,',','.') ?></td>
                                    <td class="text-success fw-bold"><?= number_format($di['line_total'],0,',','.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="fw-bold">
                                <tr>
                                    <td colspan="4" class="text-end">TỔNG GIÁ TRỊ XUẤT:</td>
                                    <td class="text-success"><?= number_format($detail_info['total_value'],0,',','.') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Template row HTML
function getItemRowHtml() {
    const items = <?= json_encode(array_map(fn($it)=>['id'=>$it['item_id'],'name'=>$it['item_name'],'price'=>$it['unit_price'],'stock'=>$it['stock_quantity']], $available_items)) ?>;
    let opts = '<option value="">-- Chọn SP --</option>';
    items.forEach(it => {
        opts += `<option value="${it.id}" data-price="${it.price}" data-stock="${it.stock}">${it.name} (còn ${it.stock})</option>`;
    });
    return `<div class="export-item-row border rounded p-2 mb-2">
    <div class="row g-1 align-items-end">
        <div class="col-5"><label class="form-label form-label-sm">Sản phẩm</label>
            <select class="form-select form-select-sm" name="item_ids[]" required onchange="fillPrice(this)">${opts}</select></div>
        <div class="col-3"><label class="form-label form-label-sm">Số lượng</label>
            <input type="number" class="form-control form-control-sm" name="quantities[]" min="1" value="1" required></div>
        <div class="col-3"><label class="form-label form-label-sm">Giá xuất (VND)</label>
            <input type="number" class="form-control form-control-sm" name="prices[]" min="0" step="500" required></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">✕</button></div>
    </div></div>`;
}
function addRow() { document.getElementById('exportItems').insertAdjacentHTML('beforeend', getItemRowHtml()); }
function removeRow(btn) { btn.closest('.export-item-row').remove(); }
function fillPrice(sel) {
    const price = sel.options[sel.selectedIndex]?.dataset?.price || 0;
    sel.closest('.export-item-row').querySelector('input[name="prices[]"]').value = price;
}
</script>
</div>
</div>
</div>
</div>
