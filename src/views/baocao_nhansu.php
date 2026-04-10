<?php
// Báo cáo nhân sự - lương, thưởng, tình hình NV theo tháng/năm
requirePermission(AppPermission::MANAGE_STAFF);
global $conn;

$sel_year  = (int)($_GET['year']  ?? date('Y'));
$sel_month = (int)($_GET['month'] ?? 0);

$where_salary = "WHERE sr.salary_year = $sel_year" . ($sel_month > 0 ? " AND sr.salary_month = $sel_month" : '');
$where_leave  = "WHERE YEAR(lr.from_date) = $sel_year" . ($sel_month > 0 ? " AND MONTH(lr.from_date) = $sel_month" : '');

// 1. Tổng quỹ lương
$wage_sql = "SELECT COUNT(DISTINCT sr.account_id) AS paid_count,
    SUM(sr.base_salary) AS total_base,
    SUM(sr.allowance) AS total_allow,
    SUM(sr.bonus) AS total_bonus,
    SUM(sr.deductions) AS total_deduct,
    SUM(sr.total_salary) AS total_pay
  FROM salary_records sr $where_salary";
$wage_r = mysqli_query($conn, $wage_sql);
$wage_summary = $wage_r ? mysqli_fetch_assoc($wage_r) : [];

// 2. Tổng NV active
$total_emp_r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM accounts WHERE status='active' AND role_id!=1");
$total_emp = (int)mysqli_fetch_assoc($total_emp_r)['c'];

// 3. Lương từng tháng trong năm (chart)
$monthly_wage = "SELECT sr.salary_month AS thang, SUM(sr.total_salary) AS tong_luong, SUM(sr.bonus) AS tong_thuong
                 FROM salary_records sr WHERE sr.salary_year=$sel_year
                 GROUP BY sr.salary_month ORDER BY thang";
$mw_r = mysqli_query($conn, $monthly_wage);
$monthly_wages = $mw_r ? mysqli_fetch_all($mw_r, MYSQLI_ASSOC) : [];

// 4. Đơn nghỉ phép theo tháng
$leave_stats = "SELECT lr.status, COUNT(*) AS count
                FROM leave_requests lr $where_leave
                GROUP BY lr.status";
$ls_r = mysqli_query($conn, $leave_stats);
$leave_stat = ['chờ duyệt'=>0,'chấp thuận'=>0,'từ chối'=>0,'hủy'=>0];
if ($ls_r) while ($row = mysqli_fetch_assoc($ls_r)) $leave_stat[$row['status']] = (int)$row['count'];

// 5. Chi tiết lương theo NV từng tháng
$detail_sql = "SELECT sr.salary_month, a.full_name, p.position_name,
    sr.base_salary, sr.allowance, sr.bonus, sr.deductions, sr.total_salary
  FROM salary_records sr
  JOIN accounts a ON a.account_id=sr.account_id
  JOIN positions p ON p.position_id=sr.position_id
  $where_salary
  ORDER BY sr.salary_month, a.full_name";
$det_r = mysqli_query($conn, $detail_sql);
$salary_detail = $det_r ? mysqli_fetch_all($det_r, MYSQLI_ASSOC) : [];

// 6. Thống kê nghỉ phép của từng NV
$leave_by_emp = "SELECT a.full_name, p.position_name,
    SUM(CASE WHEN lr.status='chấp thuận' THEN DATEDIFF(lr.to_date, lr.from_date)+1 ELSE 0 END) AS ngay_nghi_phep,
    COUNT(CASE WHEN lr.status='chờ duyệt' THEN 1 END) AS don_cho_duyet
  FROM leave_requests lr
  JOIN accounts a ON a.account_id=lr.account_id
  LEFT JOIN positions p ON p.position_id=a.position_id
  $where_leave
  GROUP BY lr.account_id
  ORDER BY ngay_nghi_phep DESC";
$lbe_r = mysqli_query($conn, $leave_by_emp);
$leave_by_emp_data = $lbe_r ? mysqli_fetch_all($lbe_r, MYSQLI_ASSOC) : [];

// 7. Biến động chức vụ trong năm
$move_sql = "SELECT MONTH(eph.start_date) AS thang, COUNT(*) AS bien_dong
             FROM employee_positions_history eph
             WHERE YEAR(eph.start_date) = $sel_year
             GROUP BY MONTH(eph.start_date)";
$mo_r = mysqli_query($conn, $move_sql);
$movements = $mo_r ? mysqli_fetch_all($mo_r, MYSQLI_ASSOC) : [];

// Chart data
$wage_chart = array_fill(1, 12, 0);
$bonus_chart = array_fill(1, 12, 0);
foreach ($monthly_wages as $mw) {
    $wage_chart[(int)$mw['thang']]  = (float)$mw['tong_luong'];
    $bonus_chart[(int)$mw['thang']] = (float)$mw['tong_thuong'];
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">BÁO CÁO NHÂN SỰ</h1>
    <div class="head-line"></div>

    <!-- Bộ lọc -->
    <form class="row g-2 align-items-end mt-2 mb-3" method="GET" action="user_page.php">
        <input type="hidden" name="baocao_nhansu" value="1">
        <div class="col-md-2">
            <label class="form-label">Năm</label>
            <input type="number" class="form-control" name="year" value="<?= $sel_year ?>" min="2020" max="2040">
        </div>
        <div class="col-md-2">
            <label class="form-label">Tháng (0=cả năm)</label>
            <select class="form-select" name="month">
                <option value="0">Cả năm</option>
                <?php for ($m=1;$m<=12;$m++): ?>
                <option value="<?=$m?>" <?=$m==$sel_month?'selected':''?>>Tháng <?=$m?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-chart-line me-1"></i>Xem báo cáo</button>
        </div>
    </form>

    <!-- KPI Summary -->
    <div class="row g-3 mb-3">
        <div class="col-md-2"><div class="card border-primary text-center shadow-sm">
            <div class="card-body p-3"><div class="text-primary"><i class="fa-solid fa-users fa-2x"></i></div>
            <div class="fs-4 fw-bold"><?= $total_emp ?></div><small>Nhân viên đang làm</small></div></div></div>
        <div class="col-md-2"><div class="card border-success text-center shadow-sm">
            <div class="card-body p-3"><div class="text-success"><i class="fa-solid fa-money-bill fa-2x"></i></div>
            <div class="fs-6 fw-bold text-success"><?= number_format($wage_summary['total_pay']??0,0,',','.') ?></div>
            <small>Tổng quỹ lương VND</small></div></div></div>
        <div class="col-md-2"><div class="card border-warning text-center shadow-sm">
            <div class="card-body p-3"><div class="text-warning"><i class="fa-solid fa-gift fa-2x"></i></div>
            <div class="fs-6 fw-bold text-warning"><?= number_format($wage_summary['total_bonus']??0,0,',','.') ?></div>
            <small>Tổng thưởng VND</small></div></div></div>
        <div class="col-md-2"><div class="card border-info text-center shadow-sm">
            <div class="card-body p-3"><div class="text-info"><i class="fa-solid fa-calendar-xmark fa-2x"></i></div>
            <div class="fs-4 fw-bold"><?= $leave_stat['chấp thuận'] ?></div>
            <small>Đơn nghỉ phép được duyệt</small></div></div></div>
        <div class="col-md-2"><div class="card border-warning text-center shadow-sm">
            <div class="card-body p-3"><div class="text-warning"><i class="fa-solid fa-clock fa-2x"></i></div>
            <div class="fs-4 fw-bold"><?= $leave_stat['chờ duyệt'] ?></div>
            <small>Đơn đang chờ duyệt</small></div></div></div>
        <div class="col-md-2"><div class="card text-center shadow-sm">
            <div class="card-body p-3"><div class="text-muted"><i class="fa-solid fa-id-badge fa-2x"></i></div>
            <div class="fs-4 fw-bold"><?= ($wage_summary['paid_count']??0) ?></div>
            <small>NV đã tính lương kỳ này</small></div></div></div>
    </div>

    <!-- Chart lương -->
    <div class="card shadow-sm mb-3">
        <div class="card-header fw-bold"><i class="fa-solid fa-chart-bar me-2"></i>Tổng Quỹ Lương & Thưởng Theo Tháng (Năm <?= $sel_year ?>)</div>
        <div class="card-body"><canvas id="wageChart" height="80"></canvas></div>
    </div>

    <div class="row g-3">
        <!-- Chi tiết lương -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header fw-bold"><i class="fa-solid fa-table me-2"></i>Chi Tiết Lương
                    <?= $sel_month > 0 ? "Tháng $sel_month/$sel_year" : "Năm $sel_year" ?>
                </div>
                <div class="card-body p-0" style="max-height:400px;overflow-y:auto">
                    <?php if (empty($salary_detail)): ?>
                        <p class="p-3 text-muted">Chưa có dữ liệu lương.</p>
                    <?php else: ?>
                    <table class="table table-sm table-striped table-bordered mb-0 text-center">
                        <thead class="table-dark sticky-top">
                            <tr><th>T</th><th>Nhân viên</th><th>Chức vụ</th><th>Lương CB</th><th>Phụ cấp</th><th>Thưởng</th><th>Khấu trừ</th><th>Thực lĩnh</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salary_detail as $sd): ?>
                            <tr>
                                <td><?= $sd['salary_month'] ?></td>
                                <td class="text-start fw-bold"><?= htmlspecialchars($sd['full_name']) ?></td>
                                <td><?= htmlspecialchars($sd['position_name']) ?></td>
                                <td><?= number_format($sd['base_salary'],0,',','.') ?></td>
                                <td class="text-primary"><?= number_format($sd['allowance'],0,',','.') ?></td>
                                <td class="text-success"><?= number_format($sd['bonus'],0,',','.') ?></td>
                                <td class="text-danger"><?= $sd['deductions'] > 0 ? '-'.number_format($sd['deductions'],0,',','.') : '0' ?></td>
                                <td class="fw-bold text-success"><?= number_format($sd['total_salary'],0,',','.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-warning fw-bold">
                            <tr><td colspan="7" class="text-end">TỔNG:</td>
                                <td><?= number_format($wage_summary['total_pay']??0,0,',','.') ?></td></tr>
                        </tfoot>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Nghỉ phép NV -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header fw-bold"><i class="fa-solid fa-calendar me-2"></i>Thống Kê Nghỉ Phép</div>
                <div class="card-body p-0">
                    <?php if (empty($leave_by_emp_data)): ?>
                        <p class="p-3 text-muted">Không có dữ liệu nghỉ phép.</p>
                    <?php else: ?>
                    <table class="table table-sm table-striped mb-0 text-center">
                        <thead class="table-secondary">
                            <tr><th>Nhân viên</th><th>Ngày đã nghỉ</th><th>Chờ duyệt</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leave_by_emp_data as $lb): ?>
                            <tr>
                                <td class="text-start fw-bold"><?= htmlspecialchars($lb['full_name']) ?></td>
                                <td><?= $lb['ngay_nghi_phep'] ?></td>
                                <td><?= $lb['don_cho_duyet'] > 0 ?
                                    '<span class="badge text-bg-warning">'.$lb['don_cho_duyet'].'</span>' :
                                    '<span class="text-muted">0</span>'
                                ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('wageChart'), {
    type: 'bar',
    data: {
        labels: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'],
        datasets: [
            {
                label: 'Tổng lương (VND)', data: <?= json_encode(array_values($wage_chart)) ?>,
                backgroundColor: 'rgba(54,162,235,0.6)', borderColor: 'rgba(54,162,235,1)', borderWidth: 1
            },
            {
                label: 'Tổng thưởng (VND)', data: <?= json_encode(array_values($bonus_chart)) ?>,
                backgroundColor: 'rgba(255,205,86,0.7)', borderColor: 'rgba(255,205,86,1)', borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true, plugins: { legend: { position: 'top' } },
        scales: { y: { ticks: { callback: v => v.toLocaleString('vi-VN') + ' VND' } } }
    }
});
</script>
</div>
</div>
</div>
</div>
