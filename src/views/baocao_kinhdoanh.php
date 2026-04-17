<?php
// Báo cáo kinh doanh - doanh thu, lợi nhuận, thống kê xuất hàng
if (!can(AppPermission::VIEW_REPORTS) && !can(AppPermission::PROCESS_ORDERS)) {
    requirePermission(AppPermission::VIEW_REPORTS);
}
global $conn;

$sel_year    = (int)($_GET['year']    ?? date('Y'));
$sel_month   = (int)($_GET['month']   ?? 0); // 0 = cả năm

// Xây dựng điều kiện WHERE
$where_revenue = "WHERE iv.status != 'cancelled' AND YEAR(iv.creation_time) = $sel_year";
$where_export  = "WHERE YEAR(ie.export_date) = $sel_year";
if ($sel_month > 0) {
    $where_revenue .= " AND MONTH(iv.creation_time) = $sel_month";
    $where_export  .= " AND MONTH(ie.export_date) = $sel_month";
}

// 1. Thống kê doanh thu & lợi nhuận tổng
$revenue_sql = "SELECT
    COUNT(DISTINCT iv.invoice_id) AS total_invoices,
    SUM(iv.total) AS total_revenue,
    SUM(iv.discount) AS total_discount
  FROM invoices iv
  $where_revenue";
$rev_r = mysqli_query($conn, $revenue_sql);
$rev_summary = $rev_r ? mysqli_fetch_assoc($rev_r) : [];

$where_import = "WHERE status != 'cancelled' AND YEAR(import_date) = $sel_year";
if ($sel_month > 0) {
    $where_import .= " AND MONTH(import_date) = $sel_month";
}
$import_sql = "SELECT SUM(total_value) as total_cost FROM inventory_receipts $where_import";
$imp_r = mysqli_query($conn, $import_sql);
$imp_summary = $imp_r ? mysqli_fetch_assoc($imp_r) : [];

$total_revenue = (float)($rev_summary['total_revenue'] ?? 0);
$total_cost    = (float)($imp_summary['total_cost'] ?? 0);
$total_profit  = $total_revenue - $total_cost;
$profit_margin = $total_revenue > 0 ? round($total_profit / $total_revenue * 100, 1) : 0;

// 2. Doanh thu theo tháng (chart data)
$monthly_sql = "SELECT MONTH(iv.creation_time) AS thang,
    SUM(iv.total) AS doanh_thu,
    COUNT(DISTINCT iv.invoice_id) AS so_hd
  FROM invoices iv
  WHERE iv.status != 'cancelled' AND YEAR(iv.creation_time) = $sel_year
  GROUP BY MONTH(iv.creation_time)";
$month_r = mysqli_query($conn, $monthly_sql);
$monthly_data = $month_r ? mysqli_fetch_all($month_r, MYSQLI_ASSOC) : [];

// 2b. Chi phí nhập theo tháng
$import_monthly_sql = "SELECT MONTH(import_date) AS thang,
    SUM(total_value) AS chi_phi_nhap
  FROM inventory_receipts
  WHERE status != 'cancelled' AND YEAR(import_date) = $sel_year
  GROUP BY MONTH(import_date)";
$import_month_r = mysqli_query($conn, $import_monthly_sql);
$import_monthly_data = $import_month_r ? mysqli_fetch_all($import_month_r, MYSQLI_ASSOC) : [];

// 3. Top sản phẩm bán chạy
$top_items_sql = "SELECT i.item_id, i.item_name, c.category_name,
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



// Chart data arrays
$chart_months  = array_fill(1, 12, 0);
$chart_revenue = array_fill(1, 12, 0);
$chart_cost    = array_fill(1, 12, 0);
$chart_profit  = array_fill(1, 12, 0);

// Map array data to months
$monthly_table_data = [];
for ($m=1; $m<=12; $m++) {
    $monthly_table_data[$m] = ['thang' => $m, 'doanh_thu' => 0, 'chi_phi_nhap' => 0, 'so_hd' => 0];
}
foreach ($monthly_data as $md) {
    $m = (int)$md['thang'];
    $chart_revenue[$m] = (float)$md['doanh_thu'];
    $monthly_table_data[$m]['doanh_thu'] = (float)$md['doanh_thu'];
    $monthly_table_data[$m]['so_hd'] = (int)$md['so_hd'];
}
foreach ($import_monthly_data as $imd) {
    $m = (int)$imd['thang'];
    $chart_cost[$m] = (float)$imd['chi_phi_nhap'];
    $monthly_table_data[$m]['chi_phi_nhap'] = (float)$imd['chi_phi_nhap'];
}
for ($m=1; $m<=12; $m++) {
    $chart_profit[$m] = $chart_revenue[$m] - $chart_cost[$m];
}

// 5. Top 5 khách hàng mua nhiều nhất
$top_customers_sql = "SELECT c.customer_id, c.customer_name, c.phone_number, c.email,
    COUNT(iv.invoice_id) AS total_orders,
    SUM(iv.total) AS total_spent
  FROM invoices iv
  JOIN customers c ON c.customer_id = iv.customer_id
  $where_revenue
  GROUP BY c.customer_id
  ORDER BY total_spent DESC
  LIMIT 5";
$cust_r = mysqli_query($conn, $top_customers_sql);
$top_customers = $cust_r ? mysqli_fetch_all($cust_r, MYSQLI_ASSOC) : [];
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
        <div class="col-md-4">
            <div class="card border-primary text-center shadow-sm">
                <div class="card-body">
                    <div class="text-primary mb-1"><i class="fa-solid fa-receipt fa-2x"></i></div>
                    <div class="fs-4 fw-bold"><?= number_format($rev_summary['total_invoices'] ?? 0) ?></div>
                    <small class="text-muted">Hóa đơn</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success text-center shadow-sm">
                <div class="card-body">
                    <div class="text-success mb-1"><i class="fa-solid fa-coins fa-2x"></i></div>
                    <div class="fs-5 fw-bold text-success"><?= number_format($total_revenue,0,',','.') ?></div>
                    <small class="text-muted">Doanh thu VND</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning text-center shadow-sm">
                <div class="card-body">
                    <div class="text-warning mb-1"><i class="fa-solid fa-cart-shopping fa-2x"></i></div>
                    <div class="fs-5 fw-bold text-warning"><?= number_format($total_cost,0,',','.') ?></div>
                    <small class="text-muted">Chi phí hàng hóa VND</small>
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
                            <tr><th>#</th><th>Sản phẩm</th><th>SL bán</th><th>Doanh thu</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_items as $k => $ti): ?>
                            <tr>
                                <td><?= $k+1 ?></td>
                                <td class="text-start"><b><?= htmlspecialchars($ti['item_name']) ?></b>
                                    <br><small class="text-muted"><?= htmlspecialchars($ti['category_name'] ?? '') ?></small></td>
                                <td><?= number_format($ti['total_qty']) ?></td>
                                <td class="text-success fw-bold">
                                    <?= number_format($ti['revenue'],0,',','.') ?> đ
                                    <button type="button" class="btn btn-sm btn-outline-info border-0 ms-1 no-print" 
                                            onclick="showSalesDetail(<?= $ti['item_id'] ?>, '<?= htmlspecialchars(addslashes($ti['item_name'])) ?>')" 
                                            title="Xem chi tiết hóa đơn">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </button>
                                </td>

                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($top_items)): ?>
                            <tr><td colspan="4" class="text-muted">Không có dữ liệu</td></tr>
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
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-bold"><i class="fa-solid fa-table me-2"></i>Bảng Doanh Thu Từng Tháng</div>
                <div class="card-body p-0">
                    <div class="top-cust-scroll" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-striped table-bordered mb-0 text-center">
                            <thead class="table-secondary"><tr><th>Th/Năm</th><th>Doanh thu</th><th>Chi phí</th><th>Lợi nhuận</th></tr></thead>
                            <tbody>
                                <?php 
                                $has_data = false;
                                foreach ($monthly_table_data as $md):
                                    if ($md['doanh_thu'] > 0 || $md['chi_phi_nhap'] > 0):
                                        $has_data = true;
                                        $lnhuan = $md['doanh_thu'] - $md['chi_phi_nhap'];
                                ?>
                                <tr>
                                    <td><?= $md['thang'] ?>/<?= $sel_year ?></td>
                                    <td class="text-success fw-bold"><?= number_format($md['doanh_thu'],0,',','.') ?></td>
                                    <td class="text-warning fw-bold"><?= number_format($md['chi_phi_nhap'],0,',','.') ?></td>
                                    <td class="<?= $lnhuan >= 0 ? 'text-primary' : 'text-danger' ?> fw-bold"><?= number_format($lnhuan,0,',','.') ?></td>
                                </tr>
                                <?php endif; endforeach; ?>
                                <?php if (!$has_data): ?>
                                <tr><td colspan="4" class="text-muted">Không có dữ liệu</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top 5 Khách hàng mua nhiều nhất -->
            <div class="card shadow-sm">
                <div class="card-header fw-bold bg-primary text-white"><i class="fa-solid fa-crown me-2"></i>Top 5 Khách Hàng Thân Thiết</div>
                <div class="card-body p-0">
                    <div class="top-cust-scroll" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-striped mb-0 text-center">
                            <thead class="table-light">
                                <tr><th>Khách hàng</th><th>Số đơn</th><th>Tổng chi tiêu</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_customers as $tc): ?>
                                <tr>
                                    <td class="text-start">
                                        <div class="fw-bold"><?= htmlspecialchars($tc['customer_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($tc['phone_number'] ?: $tc['email'] ?: '-') ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= $tc['total_orders'] ?></span></td>
                                    <td class="text-success fw-bold"><?= number_format($tc['total_spent'],0,',','.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($top_customers)): ?>
                                <tr><td colspan="3" class="text-muted">Không có dữ liệu</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
const costData    = <?= json_encode(array_values($chart_cost)) ?>;
const profitData  = <?= json_encode(array_values($chart_profit)) ?>;

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                type: 'bar',
                label: 'Doanh thu (VND)',
                data: revenueData,
                backgroundColor: 'rgba(53, 162, 235, 0.6)',
                borderColor: 'rgba(53, 162, 235, 1)',
                borderWidth: 1
            },
            {
                type: 'bar',
                label: 'Chi phí hàng hóa (VND)',
                data: costData,
                backgroundColor: 'rgba(255, 159, 64, 0.6)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            },
            {
                type: 'line',
                label: 'Lợi nhuận (VND)',
                data: profitData,
                backgroundColor: 'rgba(75, 192, 192, 1)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: false,
                tension: 0.1
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
<!-- Modal Chi Tiết Bán Hàng -->
<div class="modal fade" id="salesDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg border-0">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold" id="salesDetailTitle">Chi tiết bán hàng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã HĐ</th>
                                <th>Khách hàng</th>
                                <th>Giá bán</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody id="salesDetailBody"></tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">TỔNG CỘNG:</td>
                                <td id="salesDetailTotal" class="text-danger">0đ</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function showSalesDetail(itemId, itemName) {
    const modal = new bootstrap.Modal(document.getElementById('salesDetailModal'));
    const title = document.getElementById('salesDetailTitle');
    const tbody = document.getElementById('salesDetailBody');
    const totalEl = document.getElementById('salesDetailTotal');
    
    title.innerText = `Chi tiết bán hàng: ${itemName}`;
    tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...</td></tr>';
    totalEl.innerText = '0đ';
    modal.show();

    try {
        const year = <?= $sel_year ?>;
        const month = <?= $sel_month ?>;
        const res = await fetch(`api/business/product_sales_details.php?item_id=${itemId}&year=${year}&month=${month}`);
        const result = await res.json();

        if (result.success) {
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-muted">Không có dữ liệu hóa đơn</td></tr>';
                return;
            }

            let html = '';
            let grandTotal = 0;
            result.data.forEach(row => {
                grandTotal += row.line_total;
                html += `<tr>
                    <td><code>#${row.invoice_id}</code></td>
                    <td class="text-start">${row.customer}</td>
                    <td>${Number(row.unit_price).toLocaleString('vi-VN')}đ</td>
                    <td>${row.quantity}</td>
                    <td class="fw-bold">${Number(row.line_total).toLocaleString('vi-VN')}đ</td>
                </tr>`;
            });
            tbody.innerHTML = html;
            totalEl.innerText = grandTotal.toLocaleString('vi-VN') + 'đ';
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="py-4 text-danger">Lỗi: ${result.message}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" class="py-4 text-danger">Lỗi kết nối: ${err}</td></tr>`;
    }
}
</script>
</div>
</div>
</div>
</div>
