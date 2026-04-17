<?php
// Báo cáo nhân sự - lương, thưởng, tình hình NV theo tháng/năm
global $conn;

$sel_year  = (int)($_GET['year']  ?? date('Y'));
$sel_month = (int)($_GET['month'] ?? date('n')); // Mặc định: tháng hiện tại

// Tháng hiện tại (dùng riêng cho thống kê nghỉ phép)
$cur_month = (int)date('n');
$cur_year  = (int)date('Y');

$where_salary = "WHERE sr.salary_year = $sel_year" . ($sel_month > 0 ? " AND sr.salary_month = $sel_month" : '');
// Nghỉ phép luôn thống kê theo tháng hiện tại
$where_leave  = "WHERE MONTH(lr.from_date) = $cur_month AND YEAR(lr.from_date) = $cur_year";

// 1. Tổng quỹ lương (loại trừ admin role_id=1)
$wage_sql = "SELECT COUNT(DISTINCT sr.account_id) AS paid_count,
    SUM(sr.base_salary) AS total_base,
    SUM(sr.allowance) AS total_allow,
    SUM(sr.bonus) AS total_bonus,
    SUM(sr.deductions) AS total_deduct,
    SUM(sr.total_salary) AS total_pay
  FROM salary_records sr
  JOIN accounts a ON a.account_id = sr.account_id AND a.role_id != 1
  $where_salary";
$wage_r = mysqli_query($conn, $wage_sql);
$wage_summary = $wage_r ? mysqli_fetch_assoc($wage_r) : [];

// 2. Thống kê số lượng nhân sự
$emp_status_r = mysqli_query($conn, "SELECT hr_status, COUNT(*) as c FROM accounts WHERE role_id!=1 GROUP BY hr_status");
$total_active = 0;
$total_inactive = 0;
if ($emp_status_r) {
    while($row = mysqli_fetch_assoc($emp_status_r)) {
        if ($row['hr_status'] === 'active') $total_active = (int)$row['c'];
        else $total_inactive += (int)$row['c'];
    }
}
$total_emp = $total_active + $total_inactive;

// 2b. Thống kê theo phòng ban / chức vụ (chỉ tính loại đang làm)
$emp_pos_r = mysqli_query($conn, "SELECT p.position_name, COUNT(a.account_id) as c FROM accounts a LEFT JOIN positions p ON a.position_id = p.position_id WHERE a.hr_status='active' AND a.role_id!=1 GROUP BY p.position_name ORDER BY c DESC");
$emp_by_pos = $emp_pos_r ? mysqli_fetch_all($emp_pos_r, MYSQLI_ASSOC) : [];

// 3. Lương từng tháng trong năm (chart - loại trừ admin)
$monthly_wage = "SELECT sr.salary_month AS thang, SUM(sr.total_salary) AS tong_luong, SUM(sr.bonus) AS tong_thuong
                 FROM salary_records sr
                 JOIN accounts a ON a.account_id = sr.account_id AND a.role_id != 1
                 WHERE sr.salary_year=$sel_year
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

// 4b. Tổng số ngày nghỉ trong tháng hiện tại (CHỈ TÍNH CHẤP THUẬN)
$leave_by_month_sql = "
    SELECT MONTH(from_date) as t, SUM(DATEDIFF(to_date, from_date) + 1) as days
    FROM leave_requests 
    WHERE MONTH(from_date) = $cur_month AND YEAR(from_date) = $cur_year AND status = 'chấp thuận'
    GROUP BY MONTH(from_date)
";
$lbm_r = mysqli_query($conn, $leave_by_month_sql);
$leave_by_month = array_fill(1, 12, 0);
if ($lbm_r) {
    while($row = mysqli_fetch_assoc($lbm_r)) {
        $leave_by_month[(int)$row['t']] = (int)$row['days'];
    }
}

// 5. Thống kê nghỉ phép của từng NV (Ai nghỉ nhiều nhất)
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
$most_leave_emp = !empty($leave_by_emp_data) && $leave_by_emp_data[0]['ngay_nghi_phep'] > 0 ? $leave_by_emp_data[0] : null;

// 6. Chi tiết lương theo NV từng tháng (loại trừ admin)
$detail_sql = "SELECT sr.salary_month, a.full_name, p.position_name,
    sr.base_salary, sr.allowance, sr.bonus, sr.deductions, sr.total_salary
  FROM salary_records sr
  JOIN accounts a ON a.account_id=sr.account_id AND a.role_id != 1
  JOIN positions p ON p.position_id=sr.position_id
  $where_salary
  ORDER BY sr.salary_month, a.full_name";
$det_r = mysqli_query($conn, $detail_sql);
$salary_detail = $det_r ? mysqli_fetch_all($det_r, MYSQLI_ASSOC) : [];

// Chart data
$wage_chart = array_fill(1, 12, 0);
$bonus_chart = array_fill(1, 12, 0);
foreach ($monthly_wages as $mw) {
    $wage_chart[(int)$mw['thang']]  = (float)$mw['tong_luong'];
    $bonus_chart[(int)$mw['thang']] = (float)$mw['tong_thuong'];
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">BÁO CÁO NHÂN SỰ TOÀN DIỆN</h1>
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
                <option value="0" <?=0==$sel_month?'selected':''?>>Cả năm</option>
                <?php for ($m=1;$m<=12;$m++): ?>
                <option value="<?=$m?>" <?=$m==$sel_month?'selected':''?>>Tháng <?=$m?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter me-1"></i>Lọc báo cáo</button>
        </div>
    </form>

    <!-- KPI Summary Row 1 (Nhân lực) -->
    <h5 class="fw-bold mb-2 text-primary border-bottom pb-2"><i class="fa-solid fa-user-group me-2"></i>Tổng Quan Lực Lượng Nhân Sự</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary text-center shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-primary"><i class="fa-solid fa-users fa-2x"></i></div>
                    <div class="fs-3 fw-bold"><?= $total_emp ?></div>
                    <small class="fw-bold">Tổng số nhân viên</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success text-center shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-success"><i class="fa-solid fa-user-check fa-2x"></i></div>
                    <div class="fs-3 fw-bold text-success"><?= $total_active ?></div>
                    <small class="fw-bold">Đang làm việc</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary text-center shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-secondary"><i class="fa-solid fa-user-xmark fa-2x"></i></div>
                    <div class="fs-3 fw-bold text-secondary"><?= $total_inactive ?></div>
                    <small class="fw-bold">Đã nghỉ việc</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info text-center shadow-sm h-100">
                <div class="card-body p-2" style="max-height:100px; overflow-y:auto">
                    <small class="fw-bold text-info border-bottom d-block mb-1">Mật độ chức vụ (đang làm)</small>
                    <?php if (empty($emp_by_pos)): ?>
                        <em class="text-muted" style="font-size: 13px;">Chưa gán chức vụ</em>
                    <?php else: ?>
                        <?php foreach($emp_by_pos as $ep): ?>
                            <div class="d-flex justify-content-between px-2" style="font-size: 13px;">
                                <span><?= htmlspecialchars($ep['position_name'] ?: 'Chưa rõ') ?></span>
                                <span class="badge bg-info rounded-pill"><?= $ep['c'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Row 2 (Nghỉ phép) -->
    <h5 class="fw-bold mb-2 text-danger border-bottom pb-2"><i class="fa-solid fa-bed me-2"></i>Báo Cáo Tình Hình Nghỉ Phép</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-danger text-center shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-danger"><i class="fa-solid fa-face-frown fa-2x"></i></div>
                    <?php if ($most_leave_emp): ?>
                    <div class="fs-5 fw-bold text-danger mt-1 text-truncate" title="<?= htmlspecialchars($most_leave_emp['full_name']) ?>">
                        <?= htmlspecialchars($most_leave_emp['full_name']) ?>
                    </div>
                    <small class="fw-bold">Nghỉ nhiều nhất (<?= $most_leave_emp['ngay_nghi_phep'] ?> ngày)</small>
                    <?php else: ?>
                    <div class="fs-5 fw-bold text-muted mt-1">Không có</div>
                    <small>Nghỉ nhiều nhất</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning text-center shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-warning"><i class="fa-solid fa-business-time fa-2x"></i></div>
                    <div class="fs-4 fw-bold text-warning"><?= array_sum($leave_by_month) ?></div>
                    <small class="fw-bold">Tổng số ngày hệ thống đã nghỉ</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body p-0">
                    <canvas id="leaveMonthChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Row 3 (Quỹ lương) -->
    <h5 class="fw-bold mb-2 text-success border-bottom pb-2"><i class="fa-solid fa-money-check-dollar me-2"></i>Bảng Phân Bổ Ngân Sách Quỹ Lương</h5>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-success text-center shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-success"><i class="fa-solid fa-sack-dollar fa-2x"></i></div>
                    <div class="fs-4 fw-bold text-success"><?= number_format($wage_summary['total_pay']??0,0,',','.') ?> đ</div>
                    <small class="fw-bold">Tổng quỹ lương xuất chi (VND)</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning text-center shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-warning"><i class="fa-solid fa-gift fa-2x"></i></div>
                    <div class="fs-4 fw-bold text-warning"><?= number_format($wage_summary['total_bonus']??0,0,',','.') ?> đ</div>
                    <small class="fw-bold">Tổng thưởng phát sinh (VND)</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted"><i class="fa-solid fa-file-invoice-dollar fa-2x"></i></div>
                    <div class="fs-4 fw-bold"><?= ($wage_summary['paid_count']??0) ?></div>
                    <small class="fw-bold">Số NV đã được phát lương</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart lương -->
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold"><i class="fa-solid fa-chart-bar me-2"></i>Biểu Đồ Biến Động Ngân Sách Lương Thưởng (Năm <?= $sel_year ?>)</div>
        <div class="card-body"><canvas id="wageChart" height="160"></canvas></div>
    </div>

    <div class="row g-3 mb-5">
        <!-- Chi tiết lương -->
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold text-bg-dark"><i class="fa-solid fa-table me-2"></i>Sổ Sách Bảng Lương Chi Tiết
                    <?= $sel_month > 0 ? "— Tháng $sel_month/$sel_year" : "— Năm $sel_year" ?>
                </div>
                <div class="card-body p-0" style="max-height:400px;overflow-y:auto">
                    <?php if (empty($salary_detail)): ?>
                        <p class="p-4 text-center text-muted m-0">Chưa có dữ liệu tính lương trong kỳ này.</p>
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

        <!-- Chi tiết Nghỉ phép NV -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold text-bg-danger"><i class="fa-solid fa-clipboard-user me-2"></i>Danh Sách Nghỉ Phép</div>
                <div class="card-body p-0" style="max-height:400px;overflow-y:auto">
                    <?php if (empty($leave_by_emp_data)): ?>
                        <p class="p-4 text-center text-muted m-0">Không có dữ liệu đơn từ.</p>
                    <?php else: ?>
                    <table class="table table-sm table-striped mb-0 text-center">
                        <thead class="table-secondary sticky-top">
                            <tr><th>Nhân viên</th><th>Số ngày nghỉ</th><th>Bị treo (Đơn)</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leave_by_emp_data as $index => $lb): ?>
                            <tr>
                                <td class="text-start fw-bold">
                                    <?php if($index === 0 && $lb['ngay_nghi_phep'] > 0): ?>
                                    <i class="fa-solid fa-medal text-warning me-1" title="Nghỉ kỷ lục"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($lb['full_name']) ?>
                                </td>
                                <td class="text-danger fw-bold"><?= $lb['ngay_nghi_phep'] ?></td>
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
// Chart Quỹ Lương
new Chart(document.getElementById('wageChart'), {
    type: 'bar',
    data: {
        labels: ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'],
        datasets: [
            {
                label: 'Tổng lương xuất chi (VND)', data: <?= json_encode(array_values($wage_chart)) ?>,
                backgroundColor: 'rgba(54,162,235,0.6)', borderColor: 'rgba(54,162,235,1)', borderWidth: 1
            },
            {
                label: 'Tổng quỹ thưởng (VND)', data: <?= json_encode(array_values($bonus_chart)) ?>,
                backgroundColor: 'rgba(255,205,86,0.7)', borderColor: 'rgba(255,205,86,1)', borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } },
        scales: { y: { ticks: { callback: v => v.toLocaleString('vi-VN') + ' đ' } } }
    }
});

// Chart Phân Bổ Nghỉ Phép
new Chart(document.getElementById('leaveMonthChart'), {
    type: 'line',
    data: {
        labels: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'],
        datasets: [{
            label: 'Tổng Số Ngày Nghỉ (hợp lệ)',
            data: <?= json_encode(array_values($leave_by_month)) ?>,
            borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.1)',
            borderWidth: 2, tension: 0.3, fill: true, pointBackgroundColor: '#dc3545'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Xu Hướng Nghỉ Phép Trong Năm' }
        },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        layout: { padding: 10 }
    }
});
</script>
