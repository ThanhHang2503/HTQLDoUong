<?php
// Báo cáo kho - nhập xuất tồn theo tháng/quý/năm
if (!can(AppPermission::MANAGE_WAREHOUSE) && !can(AppPermission::VIEW_REPORTS)) {
    requirePermission(AppPermission::MANAGE_WAREHOUSE); // giữ redirect chuẩn forbidden
}
global $conn;

$sel_year    = (int)($_GET['year']    ?? date('Y'));
$sel_quarter = (int)($_GET['quarter'] ?? 0);
$sel_month   = (int)($_GET['month']   ?? 0);

// Điều kiện thời gian
$where_receipt = "WHERE YEAR(ir.import_date) = $sel_year";
$where_export  = "WHERE YEAR(ie.export_date)  = $sel_year";
if ($sel_quarter > 0) {
    $qm_start = ($sel_quarter - 1) * 3 + 1;
    $qm_end   = $qm_start + 2;
    $where_receipt .= " AND MONTH(ir.import_date) BETWEEN $qm_start AND $qm_end";
    $where_export  .= " AND MONTH(ie.export_date) BETWEEN $qm_start AND $qm_end";
} elseif ($sel_month > 0) {
    $where_receipt .= " AND MONTH(ir.import_date) = $sel_month";
    $where_export  .= " AND MONTH(ie.export_date) = $sel_month";
}

// Tổng nhập kho
$import_sql = "SELECT COUNT(DISTINCT ir.receipt_id) AS total_receipts, SUM(ir.total_value) AS total_import_value
               FROM inventory_receipts ir $where_receipt AND ir.status='completed'";
$imp_r = mysqli_query($conn, $import_sql);
$imp_summary = $imp_r ? mysqli_fetch_assoc($imp_r) : [];

// Tổng số lượng nhập trong kỳ lọc
$import_qty_sql = "SELECT SUM(ri.quantity) AS total_import_qty
                   FROM inventory_receipt_items ri
                   JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id
                   $where_receipt AND ir.status='completed'";
$imp_qty_r = mysqli_query($conn, $import_qty_sql);
$imp_qty_summary = $imp_qty_r ? mysqli_fetch_assoc($imp_qty_r) : [];

// Tổng xuất kho
$export_sql = "SELECT COUNT(DISTINCT ie.export_id) AS total_exports, SUM(ie.total_value) AS total_export_value
               FROM inventory_exports ie $where_export";
$exp_r = mysqli_query($conn, $export_sql);
$exp_summary = $exp_r ? mysqli_fetch_assoc($exp_r) : [];

// Tồn kho hiện tại
$stock_sql = "SELECT COUNT(*) AS total_products,
    SUM(stock_quantity) AS total_units,
    SUM(stock_quantity * purchase_price) AS total_cost_value,
    SUM(stock_quantity * unit_price) AS total_sell_value
  FROM items WHERE item_status='active'";
$stock_r = mysqli_query($conn, $stock_sql);
$stock_summary = $stock_r ? mysqli_fetch_assoc($stock_r) : [];

// Nhập kho theo tháng
$import_monthly = "SELECT MONTH(ir.import_date) AS thang, COUNT(*) AS so_phieu, SUM(ir.total_value) AS tong_gia_tri
                   FROM inventory_receipts ir WHERE ir.status='completed' AND YEAR(ir.import_date)=$sel_year
                   GROUP BY MONTH(ir.import_date) ORDER BY thang";
$imp_month_r = mysqli_query($conn, $import_monthly);
$import_months = $imp_month_r ? mysqli_fetch_all($imp_month_r, MYSQLI_ASSOC) : [];

$import_value_by_month = [];
foreach ($import_months as $row) {
    $monthNumber = (int)($row['thang'] ?? 0);
    if ($monthNumber > 0) {
        $import_value_by_month[$monthNumber] = (float)($row['tong_gia_tri'] ?? 0);
    }
}

// Tổng số lượng nhập theo tháng trong năm được chọn
$import_qty_monthly = "SELECT MONTH(ir.import_date) AS thang, SUM(ri.quantity) AS tong_so_luong
                       FROM inventory_receipt_items ri
                       JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id
                       WHERE ir.status='completed' AND YEAR(ir.import_date)=$sel_year
                       GROUP BY MONTH(ir.import_date)
                       ORDER BY thang";
$imp_qty_month_r = mysqli_query($conn, $import_qty_monthly);
$import_qty_months = $imp_qty_month_r ? mysqli_fetch_all($imp_qty_month_r, MYSQLI_ASSOC) : [];

// Tổng số lượng nhập theo năm (toàn bộ dữ liệu)
$import_qty_yearly = "SELECT YEAR(ir.import_date) AS nam,
                             SUM(ri.quantity) AS tong_so_luong,
                             SUM(ri.line_total) AS tong_tien_nhap
                      FROM inventory_receipt_items ri
                      JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id
                      WHERE ir.status='completed'
                      GROUP BY YEAR(ir.import_date)
                      ORDER BY nam DESC";
$imp_qty_year_r = mysqli_query($conn, $import_qty_yearly);
$import_qty_years = $imp_qty_year_r ? mysqli_fetch_all($imp_qty_year_r, MYSQLI_ASSOC) : [];

// Xuất kho theo tháng
$export_monthly = "SELECT MONTH(ie.export_date) AS thang, COUNT(*) AS so_phieu, SUM(ie.total_value) AS tong_gia_tri
                   FROM inventory_exports ie WHERE YEAR(ie.export_date)=$sel_year
                   GROUP BY MONTH(ie.export_date) ORDER BY thang";
$exp_month_r = mysqli_query($conn, $export_monthly);
$export_months = $exp_month_r ? mysqli_fetch_all($exp_month_r, MYSQLI_ASSOC) : [];

// Top sp nhập nhiều nhất
$top_import = "SELECT i.item_name, SUM(ri.quantity) AS total_qty, SUM(ri.line_total) AS total_value
               FROM inventory_receipt_items ri
               JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id
               JOIN items i ON i.item_id = ri.item_id
               $where_receipt AND ir.status='completed'
               GROUP BY ri.item_id ORDER BY total_qty DESC LIMIT 10";
$ti_r = mysqli_query($conn, $top_import);
$top_import_items = $ti_r ? mysqli_fetch_all($ti_r, MYSQLI_ASSOC) : [];

// Tồn kho chi tiết (cảnh báo hàng thấp)
$stock_detail = "SELECT i.item_name, c.category_name, i.stock_quantity, i.purchase_price,
    (i.stock_quantity * i.purchase_price) AS cost_value, i.unit_price
  FROM items i LEFT JOIN category c ON c.category_id = i.category_id
  WHERE i.item_status = 'active'
  ORDER BY i.stock_quantity ASC";
$sd_r = mysqli_query($conn, $stock_detail);
$stock_details = $sd_r ? mysqli_fetch_all($sd_r, MYSQLI_ASSOC) : [];

$low_stock_items = [];
foreach ($stock_details as $sd) {
    if ((int)($sd['stock_quantity'] ?? 0) < 10) {
        $low_stock_items[] = $sd;
    }
}

// Chart data
$imp_chart = array_fill(1, 12, 0);
$exp_chart = array_fill(1, 12, 0);
foreach ($import_months as $im) $imp_chart[(int)$im['thang']] = (float)$im['tong_gia_tri'];
foreach ($export_months as $em) $exp_chart[(int)$em['thang']] = (float)$em['tong_gia_tri'];
?>

<div class="dash_board px-2">
    <h1 class="head-name">BÁO CÁO KHO</h1>
    <div class="head-line"></div>

    <!-- Bộ lọc -->
    <form class="row g-2 align-items-end mt-2 mb-3" method="GET" action="user_page.php">
        <input type="hidden" name="baocao_kho" value="1">
        <div class="col-md-2">
            <label class="form-label">Năm</label>
            <input type="number" class="form-control" name="year" value="<?= $sel_year ?>" min="2020" max="2040">
        </div>
        <div class="col-md-2">
            <label class="form-label">Quý</label>
            <select class="form-select" name="quarter">
                <option value="0" <?= $sel_quarter==0?'selected':'' ?>>Cả năm</option>
                <option value="1" <?= $sel_quarter==1?'selected':'' ?>>Quý 1</option>
                <option value="2" <?= $sel_quarter==2?'selected':'' ?>>Quý 2</option>
                <option value="3" <?= $sel_quarter==3?'selected':'' ?>>Quý 3</option>
                <option value="4" <?= $sel_quarter==4?'selected':'' ?>>Quý 4</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Tháng</label>
            <select class="form-select" name="month">
                <option value="0">Tất cả</option>
                <?php for ($m=1;$m<=12;$m++): ?>
                <option value="<?=$m?>" <?=$m==$sel_month?'selected':''?>>T<?=$m?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-chart-pie me-1"></i>Xem báo cáo</button>
        </div>
    </form>

    <!-- KPI -->
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card border-info text-center shadow-sm">
            <div class="card-body"><div class="text-info mb-1"><i class="fa-solid fa-file-import fa-2x"></i></div>
            <div class="fs-5 fw-bold"><?= number_format($imp_summary['total_import_value']??0,0,',','.') ?></div>
            <small><?= ($imp_summary['total_receipts']??0) ?> phiếu nhập</small></div></div></div>
        <div class="col-md-3"><div class="card border-warning text-center shadow-sm">
            <div class="card-body"><div class="text-warning mb-1"><i class="fa-solid fa-file-export fa-2x"></i></div>
            <div class="fs-5 fw-bold"><?= number_format($exp_summary['total_export_value']??0,0,',','.') ?></div>
            <small><?= ($exp_summary['total_exports']??0) ?> phiếu xuất</small></div></div></div>
        <div class="col-md-3"><div class="card border-success text-center shadow-sm">
            <div class="card-body"><div class="text-success mb-1"><i class="fa-solid fa-boxes-stacked fa-2x"></i></div>
            <div class="fs-5 fw-bold"><?= number_format($stock_summary['total_cost_value']??0,0,',','.') ?></div>
            <small>Giá trị tồn kho (giá nhập)</small></div></div></div>
        <div class="col-md-3"><div class="card border-primary text-center shadow-sm">
            <div class="card-body"><div class="text-primary mb-1"><i class="fa-solid fa-warehouse fa-2x"></i></div>
            <div class="fs-5 fw-bold"><?= number_format($stock_summary['total_units']??0) ?></div>
            <small>Tổng tồn (<?= number_format($stock_summary['total_products']??0) ?> loại SP)</small></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6"><div class="card border-secondary text-center shadow-sm">
            <div class="card-body"><div class="text-secondary mb-1"><i class="fa-solid fa-layer-group fa-2x"></i></div>
            <div class="fs-5 fw-bold"><?= number_format($imp_qty_summary['total_import_qty'] ?? 0) ?></div>
            <small>Tổng số lượng nhập trong kỳ lọc</small></div></div></div>
        <div class="col-md-6"><div class="card border-danger text-center shadow-sm">
            <div class="card-body"><div class="text-danger mb-1"><i class="fa-solid fa-fire-flame-curved fa-2x"></i></div>
            <div class="fs-5 fw-bold"><?= count($low_stock_items) ?></div>
            <small>Cảnh báo tồn kho thấp (&lt; 10)</small></div></div></div>
    </div>

    <!-- Biểu đồ nhập/xuất theo tháng -->
    <div class="card shadow-sm mb-3">
        <div class="card-header fw-bold"><i class="fa-solid fa-chart-bar me-2"></i>Giá Trị Nhập Xuất Kho Theo Tháng (<?= $sel_year ?>)</div>
        <div class="card-body"><canvas id="stockChart" height="80"></canvas></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold"><i class="fa-solid fa-calendar-days me-2"></i>Nhập Kho Theo Thời Gian (Theo Tháng - <?= $sel_year ?>)</div>
                <div class="card-body p-0" style="max-height:260px;overflow-y:auto">
                    <table class="table table-sm table-striped table-bordered mb-0 text-center">
                        <thead class="table-dark sticky-top"><tr><th>Tháng</th><th>Tổng SL nhập</th><th>Tổng tiền nhập</th></tr></thead>
                        <tbody>
                            <?php foreach ($import_qty_months as $im): ?>
                            <tr>
                                <td><?= (int)$im['thang'] ?></td>
                                <td class="fw-bold"><?= number_format($im['tong_so_luong'] ?? 0) ?></td>
                                <td class="text-primary"><?= number_format($import_value_by_month[(int)$im['thang']] ?? 0,0,',','.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($import_qty_months)): ?>
                            <tr><td colspan="3" class="text-muted">Không có dữ liệu nhập theo tháng</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold"><i class="fa-solid fa-calendar-week me-2"></i>Tổng Nhập Theo Năm</div>
                <div class="card-body p-0" style="max-height:260px;overflow-y:auto">
                    <table class="table table-sm table-striped table-bordered mb-0 text-center">
                        <thead class="table-dark sticky-top"><tr><th>Năm</th><th>Tổng SL nhập</th><th>Tổng tiền nhập</th></tr></thead>
                        <tbody>
                            <?php foreach ($import_qty_years as $iy): ?>
                            <tr>
                                <td><?= (int)$iy['nam'] ?></td>
                                <td class="fw-bold"><?= number_format($iy['tong_so_luong'] ?? 0) ?></td>
                                <td class="text-success"><?= number_format($iy['tong_tien_nhap'] ?? 0,0,',','.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($import_qty_years)): ?>
                            <tr><td colspan="3" class="text-muted">Không có dữ liệu nhập theo năm</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Top nhập nhiều -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header fw-bold"><i class="fa-solid fa-arrow-down me-2"></i>Top SP Nhập Nhiều Nhất</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped table-bordered mb-0 text-center">
                        <thead class="table-dark"><tr><th>#</th><th>Sản phẩm</th><th>SL nhập</th><th>Giá trị nhập</th></tr></thead>
                        <tbody>
                            <?php foreach ($top_import_items as $k => $ti): ?>
                            <tr>
                                <td><?= $k+1 ?></td>
                                <td class="text-start fw-bold"><?= htmlspecialchars($ti['item_name']) ?></td>
                                <td><?= number_format($ti['total_qty']) ?></td>
                                <td class="text-primary"><?= number_format($ti['total_value'],0,',','.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($top_import_items)): ?>
                            <tr><td colspan="4" class="text-muted">Không có dữ liệu</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cảnh báo tồn kho thấp -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header fw-bold text-danger"><i class="fa-solid fa-fire me-2"></i>Cảnh Báo Tồn Kho Thấp (&lt; 10)</div>
                <div class="card-body p-0" style="max-height:350px;overflow-y:auto">
                    <table class="table table-sm table-striped table-bordered mb-0 text-center">
                        <thead class="table-dark sticky-top"><tr><th>Sản phẩm</th><th>Danh mục</th><th>SL tồn</th></tr></thead>
                        <tbody>
                            <?php foreach ($low_stock_items as $lsi): ?>
                            <tr class="table-danger">
                                <td class="text-start fw-bold"><?= htmlspecialchars($lsi['item_name']) ?></td>
                                <td><?= htmlspecialchars($lsi['category_name'] ?? '') ?></td>
                                <td class="fw-bold"><?= (int)$lsi['stock_quantity'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($low_stock_items)): ?>
                            <tr><td colspan="3" class="text-muted">Không có sản phẩm tồn thấp</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tồn kho chi tiết -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header fw-bold"><i class="fa-solid fa-warehouse me-2"></i>Tồn Kho Hiện Tại (Từ Thấp Đến Cao)</div>
                <div class="card-body p-0" style="max-height:350px;overflow-y:auto">
                    <table class="table table-sm table-striped table-bordered mb-0 text-center">
                        <thead class="table-dark sticky-top"><tr><th>Sản phẩm</th><th>SL tồn</th><th>Giá trị tồn</th><th>Trạng thái</th></tr></thead>
                        <tbody>
                            <?php foreach ($stock_details as $sd): ?>
                            <tr class="<?= $sd['stock_quantity'] <= 10 ? 'table-danger' : ($sd['stock_quantity'] <= 30 ? 'table-warning' : '') ?>">
                                <td class="text-start fw-bold"><?= htmlspecialchars($sd['item_name']) ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($sd['category_name'] ?? '') ?></small></td>
                                <td class="fw-bold"><?= $sd['stock_quantity'] ?></td>
                                <td><?= number_format($sd['cost_value'],0,',','.') ?></td>
                                <td>
                                    <?php if ($sd['stock_quantity'] == 0): ?>
                                        <span class="badge text-bg-danger">Hết hàng</span>
                                    <?php elseif ($sd['stock_quantity'] <= 10): ?>
                                        <span class="badge text-bg-danger">Sắp hết</span>
                                    <?php elseif ($sd['stock_quantity'] <= 30): ?>
                                        <span class="badge text-bg-warning">Thấp</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-success">Bình thường</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('stockChart'), {
    type: 'bar',
    data: {
        labels: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'],
        datasets: [
            {
                label: 'Giá trị nhập (VND)',
                data: <?= json_encode(array_values($imp_chart)) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)', borderColor: 'rgba(54,162,235,1)', borderWidth: 1
            },
            {
                label: 'Giá trị xuất (VND)',
                data: <?= json_encode(array_values($exp_chart)) ?>,
                backgroundColor: 'rgba(255, 159, 64, 0.6)', borderColor: 'rgba(255,159,64,1)', borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { ticks: { callback: v => v.toLocaleString('vi-VN') + ' VND' } } }
    }
});
</script>
</div>
</div>
</div>
</div>
