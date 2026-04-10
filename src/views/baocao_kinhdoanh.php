<?php
// Báo cáo kinh doanh - doanh thu, lợi nhuận, thống kê xuất hàng
requirePermission(AppPermission::VIEW_REPORTS);
global $conn;

$sel_year    = (int)($_GET['year']    ?? date('Y'));
$sel_quarter = (int)($_GET['quarter'] ?? 0); // 0 = cả năm
$sel_month   = (int)($_GET['month']   ?? 0); // 0 = cả năm/quý

// Xây dựng điều kiện WHERE
$where_revenue = "WHERE iv.status != 'cancelled' AND YEAR(iv.creation_time) = $sel_year";
$where_export  = "WHERE YEAR(ie.export_date) = $sel_year";
if ($sel_quarter > 0) {
    $qm_start = ($sel_quarter - 1) * 3 + 1;
    $qm_end   = $qm_start + 2;
    $where_revenue .= " AND MONTH(iv.creation_time) BETWEEN $qm_start AND $qm_end";
    $where_export  .= " AND MONTH(ie.export_date) BETWEEN $qm_start AND $qm_end";
} elseif ($sel_month > 0) {
    $where_revenue .= " AND MONTH(iv.creation_time) = $sel_month";
    $where_export  .= " AND MONTH(ie.export_date) = $sel_month";
}

// 1. Thống kê doanh thu & lợi nhuận tổng
$revenue_sql = "SELECT
    COUNT(DISTINCT iv.invoice_id) AS total_invoices,
    SUM(iv.total) AS total_revenue,
    SUM(iv.discount) AS total_discount,
    SUM(id.quantity * i.purchase_price) AS total_cost
  FROM invoices iv
  LEFT JOIN invoice_details id ON id.invoice_id = iv.invoice_id
  LEFT JOIN items i ON i.item_id = id.item_id
  $where_revenue";
$rev_r = mysqli_query($conn, $revenue_sql);
$rev_summary = $rev_r ? mysqli_fetch_assoc($rev_r) : [];

$total_revenue = (float)($rev_summary['total_revenue'] ?? 0);
$total_cost    = (float)($rev_summary['total_cost'] ?? 0);
$total_profit  = $total_revenue - $total_cost;
$profit_margin = $total_revenue > 0 ? round($total_profit / $total_revenue * 100, 1) : 0;

// 2. Doanh thu theo tháng (chart data)
$monthly_sql = "SELECT MONTH(iv.creation_time) AS thang,
    SUM(iv.total) AS doanh_thu,
    SUM(id.quantity * i.purchase_price) AS chi_phi,
    COUNT(DISTINCT iv.invoice_id) AS so_hd
  FROM invoices iv
  LEFT JOIN invoice_details id ON id.invoice_id = iv.invoice_id
  LEFT JOIN items i ON i.item_id = id.item_id
  WHERE iv.status != 'cancelled' AND YEAR(iv.creation_time) = $sel_year
  GROUP BY MONTH(iv.creation_time)
  ORDER BY thang";
$month_r = mysqli_query($conn, $monthly_sql);
$monthly_data = $month_r ? mysqli_fetch_all($month_r, MYSQLI_ASSOC) : [];

// 3. Top sản phẩm bán chạy
$top_items_sql = "SELECT i.item_name, c.category_name,
    SUM(id.quantity) AS total_qty,
    SUM(id.quantity * id.unit_price) AS revenue,
    SUM(id.quantity * i.purchase_price) AS cost,
    SUM(id.quantity * (id.unit_price - i.purchase_price)) AS profit
  FROM invoice_details id
  JOIN invoices iv ON iv.invoice_id = id.invoice_id
  JOIN items i ON i.item_id = id.item_id
  LEFT JOIN category c ON c.category_id = i.category_id
  $where_revenue
  GROUP BY i.item_id
  ORDER BY total_qty DESC
  LIMIT 10";
$top_r = mysqli_query($conn, $top_items_sql);
$top_items = $top_r ? mysqli_fetch_all($top_r, MYSQLI_ASSOC) : [];

// 4. Doanh thu theo nhân viên bán hàng
$staff_sql = "SELECT a.full_name,
    COUNT(DISTINCT iv.invoice_id) AS so_hd,
    SUM(iv.total) AS doanh_thu
  FROM invoices iv
  JOIN accounts a ON a.account_id = iv.account_id
  $where_revenue
  GROUP BY iv.account_id
  ORDER BY doanh_thu DESC";
$staff_r = mysqli_query($conn, $staff_sql);
$staff_stats = $staff_r ? mysqli_fetch_all($staff_r, MYSQLI_ASSOC) : [];

// 5. Thống kê xuất kho riêng (phiếu xuất)
$export_sql = "SELECT COUNT(*) AS total_exports, SUM(total_value) AS total_export_value,
    SUM((SELECT SUM(ei.quantity*ei.purchase_price) FROM inventory_export_items ei WHERE ei.export_id=ie.export_id)) AS export_cost
  FROM inventory_exports ie $where_export";
$exp_r = mysqli_query($conn, $export_sql);
$exp_summary = $exp_r ? mysqli_fetch_assoc($exp_r) : [];

// Chart data arrays
$chart_months  = array_fill(1, 12, 0);
$chart_revenue = array_fill(1, 12, 0);
$chart_profit  = array_fill(1, 12, 0);
foreach ($monthly_data as $md) {
    $m = (int)$md['thang'];
    $chart_revenue[$m] = (float)$md['doanh_thu'];
    $chart_profit[$m]  = (float)$md['doanh_thu'] - (float)$md['chi_phi'];
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">BÁO CÁO KINH DOANH</h1>
    <div class="head-line"></div>

    <!-- Bộ lọc -->
    <form class="row g-2 align-items-end mt-2 mb-3" method="GET" action="user_page.php">
        <input type="hidden" name="baocao_kinhdoanh" value="1">
        <div class="col-md-2">
            <label class="form-label">Năm</label>
            <input type="number" class="form-control" name="year" value="<?= $sel_year ?>" min="2020" max="2040">
        </div>
        <div class="col-md-2">
            <label class="form-label">Quý (0=tất cả)</label>
            <select class="form-select" name="quarter">
                <option value="0" <?= $sel_quarter==0?'selected':'' ?>>Cả năm</option>
                <option value="1" <?= $sel_quarter==1?'selected':'' ?>>Quý 1 (T1-T3)</option>
                <option value="2" <?= $sel_quarter==2?'selected':'' ?>>Quý 2 (T4-T6)</option>
                <option value="3" <?= $sel_quarter==3?'selected':'' ?>>Quý 3 (T7-T9)</option>
                <option value="4" <?= $sel_quarter==4?'selected':'' ?>>Quý 4 (T10-T12)</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Tháng (0=tất cả)</label>
            <select class="form-select" name="month">
                <option value="0">Tất cả</option>
                <?php for ($m=1;$m<=12;$m++): ?>
                <option value="<?=$m?>" <?=$m==$sel_month?'selected':''?>>Tháng <?=$m?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-chart-bar me-1"></i>Xem báo cáo</button>
        </div>
    </form>

    <!-- Tóm tắt -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-primary text-center shadow-sm">
                <div class="card-body">
                    <div class="text-primary mb-1"><i class="fa-solid fa-receipt fa-2x"></i></div>
                    <div class="fs-4 fw-bold"><?= number_format($rev_summary['total_invoices'] ?? 0) ?></div>
                    <small class="text-muted">Hóa đơn</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success text-center shadow-sm">
                <div class="card-body">
                    <div class="text-success mb-1"><i class="fa-solid fa-coins fa-2x"></i></div>
                    <div class="fs-5 fw-bold text-success"><?= number_format($total_revenue,0,',','.') ?></div>
                    <small class="text-muted">Doanh thu VND</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning text-center shadow-sm">
                <div class="card-body">
                    <div class="text-warning mb-1"><i class="fa-solid fa-cart-shopping fa-2x"></i></div>
                    <div class="fs-5 fw-bold text-warning"><?= number_format($total_cost,0,',','.') ?></div>
                    <small class="text-muted">Chi phí hàng hóa VND</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-<?= $total_profit >= 0 ? 'success' : 'danger' ?> text-center shadow-sm">
                <div class="card-body">
                    <div class="text-<?= $total_profit >= 0 ? 'success' : 'danger' ?> mb-1"><i class="fa-solid fa-chart-line fa-2x"></i></div>
                    <div class="fs-5 fw-bold text-<?= $total_profit >= 0 ? 'success' : 'danger' ?>"><?= number_format($total_profit,0,',','.') ?></div>
                    <small class="text-muted">Lợi nhuận gộp (<?= $profit_margin ?>%)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ doanh thu theo tháng -->
    <div class="card shadow-sm mb-3">
        <div class="card-header fw-bold"><i class="fa-solid fa-chart-column me-2"></i>Doanh Thu & Lợi Nhuận Theo Tháng (Năm <?= $sel_year ?>)</div>
        <div class="card-body">
            <canvas id="revenueChart" height="80"></canvas>
        </div>
    </div>

    <div class="row g-3">
        <!-- Top sản phẩm -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header fw-bold"><i class="fa-solid fa-trophy me-2"></i>Top 10 Sản Phẩm Bán Chạy</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped table-bordered mb-0 text-center">
                        <thead class="table-dark">
                            <tr><th>#</th><th>Sản phẩm</th><th>SL bán</th><th>Doanh thu</th><th>Lợi nhuận</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_items as $k => $ti): ?>
                            <tr>
                                <td><?= $k+1 ?></td>
                                <td class="text-start"><b><?= htmlspecialchars($ti['item_name']) ?></b>
                                    <br><small class="text-muted"><?= htmlspecialchars($ti['category_name'] ?? '') ?></small></td>
                                <td><?= number_format($ti['total_qty']) ?></td>
                                <td class="text-success"><?= number_format($ti['revenue'],0,',','.') ?></td>
                                <td class="text-<?= $ti['profit'] >= 0 ? 'success' : 'danger' ?> fw-bold">
                                    <?= number_format($ti['profit'],0,',','.') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($top_items)): ?>
                            <tr><td colspan="5" class="text-muted">Không có dữ liệu</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Doanh thu theo nhân viên + báo cáo tháng chi tiết -->
        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-bold"><i class="fa-solid fa-users me-2"></i>Doanh Thu Theo Nhân Viên</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0 text-center">
                        <thead class="table-secondary">
                            <tr><th>Nhân viên</th><th>Số HĐ</th><th>Doanh thu</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_stats as $ss): ?>
                            <tr>
                                <td class="text-start fw-bold"><?= htmlspecialchars($ss['full_name']) ?></td>
                                <td><?= $ss['so_hd'] ?></td>
                                <td class="text-success"><?= number_format($ss['doanh_thu'],0,',','.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Chi tiết doanh thu từng tháng -->
            <div class="card shadow-sm">
                <div class="card-header fw-bold"><i class="fa-solid fa-table me-2"></i>Bảng Doanh Thu Từng Tháng</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped table-bordered mb-0 text-center">
                        <thead class="table-secondary"><tr><th>Tháng</th><th>Doanh thu</th><th>Lợi nhuận</th><th>SL HĐ</th></tr></thead>
                        <tbody>
                            <?php foreach ($monthly_data as $md):
                                $lnhuan = (float)$md['doanh_thu'] - (float)$md['chi_phi'];
                            ?>
                            <tr>
                                <td>T<?= $md['thang'] ?></td>
                                <td class="text-success"><?= number_format($md['doanh_thu'],0,',','.') ?></td>
                                <td class="text-<?= $lnhuan >= 0 ? 'success' : 'danger' ?>"><?= number_format($lnhuan,0,',','.') ?></td>
                                <td><?= $md['so_hd'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($monthly_data)): ?>
                            <tr><td colspan="4" class="text-muted">Không có dữ liệu</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN + render -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels = ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'];
const revenueData = <?= json_encode(array_values($chart_revenue)) ?>;
const profitData  = <?= json_encode(array_values($chart_profit)) ?>;

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Doanh thu (VND)',
                data: revenueData,
                backgroundColor: 'rgba(53, 162, 235, 0.6)',
                borderColor: 'rgba(53, 162, 235, 1)',
                borderWidth: 1,
                order: 2
            },
            {
                label: 'Lợi nhuận gộp (VND)',
                data: profitData,
                type: 'line',
                borderColor: 'rgba(75, 192, 92, 1)',
                backgroundColor: 'rgba(75, 192, 92, 0.1)',
                fill: true,
                tension: 0.3,
                order: 1
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { ticks: { callback: v => v.toLocaleString('vi-VN') + ' VND' } }
        }
    }
});
</script>
</div>
</div>
</div>
</div>
