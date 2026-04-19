<?php
// Bảng lương - Manager tính và lưu lương cho nhân viên
requirePermission(AppPermission::MANAGE_STAFF);

use HR\Services\SalaryService;
use HR\Repositories\SalaryRepository;

global $conn;
$salaryRepo = new SalaryRepository($conn);
$salaryService = new SalaryService($conn);

$curMonth = (int)date('n');
$curYear = (int)date('Y');

$msg_success = '';
$msg_error   = '';

$sel_month = (int)($_GET['month'] ?? $curMonth);
$sel_year  = (int)($_GET['year'] ?? $curYear);
$sel_year  = max(2020, min($sel_year, 2030));

$isPastMonth = ($sel_year < $curYear) || ($sel_year == $curYear && $sel_month < $curMonth);

// Chặn tháng tương lai (xét theo tháng và năm)
$cur_m = (int)date('n');
$cur_y = (int)date('Y');
$future_blocked = false;
if ($sel_year > $cur_y || ($sel_year == $cur_y && $sel_month > $cur_m)) {
    $future_blocked = true;
    $msg_error = 'Không thể tính lương cho tháng tương lai.';
}

$startOfMonth = sprintf("%04d-%02d-01", $sel_year, $sel_month);
$endOfMonth = date('Y-m-t', strtotime($startOfMonth));
$totalDaysInMonth = (int)date('t', strtotime($startOfMonth));


// Lưu/cập nhật lương
if (isset($_POST['save_salary'])) {
    $acc_id     = (int)($_POST['account_id'] ?? 0);
    $month      = (int)($_POST['salary_month'] ?? $sel_month);
    $year       = (int)($_POST['salary_year'] ?? $sel_year);
    $allowance  = (int)($_POST['allowance'] ?? 0);
    $bonus      = (int)($_POST['bonus'] ?? 0);
    $deductions = (int)($_POST['deductions'] ?? 0);
    $notes      = trim(mysqli_real_escape_string($conn, $_POST['notes'] ?? ''));
    $by         = currentUserId();

    // Chặn tháng tương lai ở backend
    $cur_m = (int)date('n');
    $cur_y = (int)date('Y');
    if ($year > $cur_y || ($year == $cur_y && $month > $cur_m)) {
        $msg_error = 'Không thể tính lương cho tháng tương lai (Backend blocked).';
    } elseif ($acc_id > 0) {
        $salaryData = [
            'allowance' => $allowance,
            'bonus' => $bonus,
            'deductions' => $deductions,
            'notes' => $notes
        ];
        
        $saveResult = $salaryService->saveSalary($acc_id, $month, $year, $salaryData);
        
        if ($saveResult['success']) {
            $msg_success = $saveResult['message'];
        } else {
            $msg_error = $saveResult['error'];
        }
    }
}

// Xóa bản ghi lương
if (isset($_POST['delete_salary'])) {
    $sid = (int)($_POST['salary_record_id'] ?? 0);
    mysqli_query($conn, "DELETE FROM salary_records WHERE salary_record_id=$sid");
    $msg_success = 'Đã xóa bản ghi lương.';
}

// Danh sách nhân viên (để tính lương) - Lọc theo tháng phát sinh làm việc
$employees = [];
if (!$future_blocked) {
    $emp_sql = "SELECT a.account_id, a.full_name, a.hire_date, a.resignation_date, r.display_name AS role_name,
                       p.position_name, p.base_salary,
                       sr.status as salary_status, sr.salary_record_id
                FROM accounts a
                JOIN roles r ON r.id = a.role_id
                LEFT JOIN positions p ON p.position_id = a.position_id
                LEFT JOIN salary_records sr ON a.account_id = sr.account_id 
                   AND sr.salary_month = $sel_month AND sr.salary_year = $sel_year
                WHERE a.role_id != 1
                  AND a.hire_date <= '$endOfMonth'
                  AND (a.resignation_date IS NULL OR a.resignation_date >= '$startOfMonth')
                ORDER BY a.full_name";
    $emp_result = mysqli_query($conn, $emp_sql);
    if ($emp_result) {
        while ($e = mysqli_fetch_assoc($emp_result)) {
            // Sử dụng SalaryService để tính toán chuẩn hóa
            $calc = $salaryService->calculateSalary($e['account_id'], $sel_month, $sel_year);
            
            if ($calc['success']) {
                $e['base_salary'] = $calc['pro_rated_base'];
                $e['working_days'] = $calc['actual_active_days'];
                $e['effective_days'] = $calc['effective_days'];
                $e['prorate_reason'] = $calc['actual_active_days'] < $totalDaysInMonth ? 'pro_rated' : '';
                
                // Hiển thị gợi ý nghỉ phép từ bảng leave_requests (chỉ để tham khảo)
                $leave_sql = "SELECT COUNT(*) as days FROM leave_requests 
                             WHERE account_id = {$e['account_id']} AND status = 'chấp thuận'
                             AND from_date <= '$endOfMonth' AND to_date >= '$startOfMonth'";
                $l_res = mysqli_query($conn, $leave_sql);
                $l_data = mysqli_fetch_assoc($l_res);
                $e['leave_days'] = (int)($l_data['days'] ?? 0);
                $e['auto_deduction'] = 0; // Đã khấu trừ trực tiếp vào base_salary

                // CHỈ HIỂN THỊ NẾU CÓ NGÀY LÀM VIỆC (Requirement #4)
                if ($e['working_days'] > 0) {
                    $employees[] = $e;
                }
            }
        }
    }
}

// Bản ghi lương tháng đã chọn (loại trừ admin role_id=1)
$salary_sql = "SELECT sr.salary_record_id, sr.account_id, sr.salary_month, sr.salary_year,
                      sr.base_salary, sr.allowance, sr.bonus, sr.deductions, sr.total_salary,
                      sr.notes, sr.updated_at,
                      a.full_name, r.display_name AS role_name, p.position_name
               FROM salary_records sr
               JOIN accounts a ON a.account_id = sr.account_id AND a.role_id != 1
               JOIN roles r ON r.id = a.role_id
               JOIN positions p ON p.position_id = sr.position_id
               WHERE sr.salary_month = $sel_month AND sr.salary_year = $sel_year
               ORDER BY a.full_name";
$salary_result = mysqli_query($conn, $salary_sql);
$salary_records = $salary_result ? mysqli_fetch_all($salary_result, MYSQLI_ASSOC) : [];

// Tính lại bảng nhân viên chưa có lương tháng này
$has_salary = array_column($salary_records, 'account_id');
$missing_employees = array_filter($employees, fn($e) => !in_array($e['account_id'], $has_salary));

// Thống kê tổng
$total_base   = array_sum(array_column($salary_records, 'base_salary'));
$total_allow  = array_sum(array_column($salary_records, 'allowance'));
$total_bonus  = array_sum(array_column($salary_records, 'bonus'));
$total_deduct = array_sum(array_column($salary_records, 'deductions'));
$total_pay    = array_sum(array_column($salary_records, 'total_salary'));

// Nếu in
if (isset($_GET['print'])) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html><html lang="vi">
    <head><meta charset="UTF-8"><title>Bảng Lương <?= $sel_month ?>/<?= $sel_year ?></title>
    <style>
    body{font-family:Arial,sans-serif;margin:20px;font-size:12px}
    h2,h3{text-align:center}table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #333;padding:5px 8px;text-align:center}
    th{background:#f0f0f0}tfoot tr{font-weight:bold;background:#fff9c4}
    .tr{text-align:right}
    </style></head><body>
    <h2>BẢNG LƯƠNG THÁNG <?= $sel_month ?>/<?= $sel_year ?></h2>
    <table>
    <thead><tr><th>#</th><th>Họ tên</th><th>Chức vụ</th><th>Lương CB</th><th>Phụ cấp</th><th>Thưởng</th><th>Khấu trừ</th><th>Thực lĩnh</th><th>Ghi chú</th></tr></thead>
    <tbody>
    <?php $i=1; foreach ($salary_records as $sr): ?>
    <tr><td><?=$i++?></td><td style="text-align:left"><?=htmlspecialchars($sr['full_name'])?></td>
    <td><?=htmlspecialchars($sr['position_name'])?></td>
    <td class="tr"><?=number_format($sr['base_salary'],0,',','.')?></td>
    <td class="tr"><?=number_format($sr['allowance'],0,',','.')?></td>
    <td class="tr"><?=number_format($sr['bonus'],0,',','.')?></td>
    <td class="tr"><?=number_format($sr['deductions'],0,',','.')?></td>
    <td class="tr"><b><?=number_format($sr['total_salary'],0,',','.')?></b></td>
    <td><?=htmlspecialchars($sr['notes']??'')?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="3">TỔNG CỘNG</td>
    <td class="tr"><?=number_format($total_base,0,',','.')?></td>
    <td class="tr"><?=number_format($total_allow,0,',','.')?></td>
    <td class="tr"><?=number_format($total_bonus,0,',','.')?></td>
    <td class="tr"><?=number_format($total_deduct,0,',','.')?></td>
    <td class="tr"><?=number_format($total_pay,0,',','.')?></td>
    <td></td></tr></tfoot>
    </table>
    <script>
    // Dùng afterprint để redirect sau khi hộp thoại in đóng lại
    window.addEventListener('afterprint', function() {
        window.location.href = 'user_page.php?bangluong&month=<?= $sel_month ?>&year=<?= $sel_year ?>&print_success=1';
    });
    window.print();
    </script></body></html>
    <?php
    exit;
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">BẢNG LƯƠNG NHÂN VIÊN</h1>
    <div class="head-line"></div>

    <?php if ($msg_success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($msg_success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($msg_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['print_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i>
            <strong>In bảng lương thành công!</strong> Bảng lương tháng <?= $sel_month ?>/<?= $sel_year ?> đã được in.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Bộ lọc tháng/năm -->
    <div class="row g-2 align-items-end mt-2 mb-3">
        <div class="col-auto">
            <form class="d-flex gap-2 align-items-end" method="GET" action="user_page.php">
                <input type="hidden" name="bangluong" value="1">
                <div>
                    <label class="form-label mb-0">Tháng</label>
                    <select class="form-select" name="month" style="width:110px">
                        <?php for ($m=1;$m<=12;$m++): ?>
                        <option value="<?=$m?>" <?=$m==$sel_month?'selected':''?>>Tháng <?=$m?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label mb-0">Năm</label>
                    <input type="number" class="form-control" name="year" value="<?=$sel_year?>" style="width:90px">
                </div>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Xem</button>
            </form>
        </div>
        <div class="col-auto ms-auto">
            <?php if (!$future_blocked): ?>
            <a class="btn btn-outline-secondary"
               href="user_page.php?bangluong&month=<?=$sel_month?>&year=<?=$sel_year?>&print=1">
                <i class="fa-solid fa-print me-1"></i>In bảng lương
            </a>
            <?php endif; ?>
        </div>
    </div>

    </div>

    <?php if ($future_blocked): ?>
        <div class="alert alert-info py-4 text-center">
            <i class="fa-solid fa-calendar-xmark fa-3x mb-3 text-secondary"></i>
            <h3>Dữ liệu bảng lương không khả dụng</h3>
            <p class="mb-0">Bạn đang chọn tháng <strong><?= $sel_month ?>/<?= $sel_year ?></strong> nằm trong tương lai. <br>Hệ thống chỉ cho phép xem và tính lương cho tháng hiện tại hoặc các tháng trước đó.</p>
        </div>
    <?php else: ?>
    <div class="row g-3">
        <!-- Form tính lương -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header fw-bold bg-success text-white">
                    <i class="fa-solid fa-calculator me-2"></i>Tính Lương Nhân Viên
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="salaryForm">
                        <input type="hidden" name="salary_month" value="<?= $sel_month ?>">
                        <input type="hidden" name="salary_year" value="<?= $sel_year ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nhân viên</label>
                            
                            <!-- Search Box -->
                            <div class="input-group input-group-sm mb-2 shadow-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="empSearch" placeholder="Tìm tên nhân viên..." onkeyup="filterEmployees()">
                                <button class="btn btn-outline-secondary" type="button" onclick="clearEmpSearch()"><i class="fa-solid fa-xmark"></i></button>
                            </div>

                            <select class="form-select" name="account_id" id="empSelect" required onchange="fillBaseSalary(this)">
                                <option value="">-- Chọn nhân viên --</option>
                                <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['account_id'] ?>"
                                        data-salary="<?= $emp['base_salary'] ?>"
                                        data-deduction="<?= $emp['auto_deduction'] ?>"
                                        data-leave="<?= $emp['leave_days'] ?>"
                                        data-prorate-reason="<?= $emp['prorate_reason'] ?>"
                                        data-pos="<?= htmlspecialchars($emp['position_name'] ?? '') ?>"
                                        data-days="<?= $emp['working_days'] ?>" data-status="<?= $emp['salary_status'] ?>">

                                    <?= htmlspecialchars($emp['full_name']) ?>
                                    <?= $emp['prorate_reason'] === 'pro_rated' ? ' (Tỷ lệ)' : '' ?>
                                    <?php if ($emp['salary_status'] === 'finalized'): ?>
                                        (ĐÃ CHỐT ✓)
                                    <?php elseif ($emp['salary_status'] === 'draft'): ?>
                                        (TẠM TÍNH)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="empSalaryStatus">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Lương tính theo ngày làm thực tế <span id="prorateHint" class="text-info small ms-2"></span></label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" id="baseSalaryDisplay" disabled>
                                <input type="text" class="form-control bg-light" id="workingDaysDisplay" disabled style="width: 140px;">
                            </div>
                            <input type="hidden" name="base_salary_val" id="baseSalaryHidden">
                            <small class="text-muted" style="font-size: 0.75rem;">Công thức: (Lương CB / <?= $totalDaysInMonth ?> ngày) × số ngày làm (Active)</small>
                        </div>
                           <div class="mb-2">
                            <label class="form-label fw-bold">Phụ cấp (VND) <span id="allowanceFmt" class="text-muted small ms-2"></span></label>
                            <input type="number" class="form-control" name="allowance" id="allowanceInput"
                                value="500000" min="0" step="100000" oninput="calcTotal()" onchange="calcTotal()">
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Thưởng (VND) <span id="bonusFmt" class="text-muted small ms-2"></span></label>
                            <input type="number" class="form-control" name="bonus" id="bonusInput"
                                value="0" min="0" step="100000" oninput="calcTotal()" onchange="calcTotal()">
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Khấu trừ (VND) <span id="leaveHint" class="text-danger small ms-2"></span> <span id="deductFmt" class="text-muted small ms-1"></span></label>
                            <input type="number" class="form-control" name="deductions" id="deductInput"
                                value="0" min="0" step="100000" oninput="calcTotal()" onchange="calcTotal()">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Thực lĩnh (ước tính)</label>
                            <input type="text" class="form-control fw-bold text-success bg-light" id="totalDisplay" disabled>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Ghi chú</label>
                            <input type="text" class="form-control" name="notes" id="notesInput" placeholder="Ghi chú lương tháng này...">
                        </div>
                        <div class="mt-3" id="salaryActionContainer">
                            <button type="submit" name="save_salary" id="btnSaveSalary" class="btn <?= $isPastMonth ? 'btn-success' : 'btn-primary' ?> fw-bold w-100 py-2">
                                <i class="fa-solid <?= $isPastMonth ? 'fa-lock' : 'fa-save' ?> me-1"></i> 
                                <?= $isPastMonth ? "Chốt lương tháng $sel_month/$sel_year" : "Lưu lương tạm tính tháng $sel_month/$sel_year" ?>
                            </button>
                            <div id="lockedNotify" class="alert alert-warning mt-2 d-none" style="font-size: 0.85rem;">
                                <i class="fa-solid fa-shield-halved me-1"></i> Bản ghi lương này đã được <b>Chốt chính thức</b> và không thể chỉnh sửa.
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bảng lương đã tính -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    <i class="fa-solid fa-table me-2"></i>Bảng Lương Tháng <?= $sel_month ?>/<?= $sel_year ?>
                    <?php if (count($missing_employees) > 0): ?>
                    <span class="badge text-bg-warning ms-2"><?= count($missing_employees) ?> NV chưa tính</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($salary_records)): ?>
                        <div class="p-3 text-muted">Chưa có bản ghi lương nào cho tháng <?= $sel_month ?>/<?= $sel_year ?>.
                        <br>Hãy sử dụng form bên trái để tính lương cho từng nhân viên.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered mb-0 text-center table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Nhân viên</th>
                                <th>Lương CB</th>
                                <th>Phụ cấp</th>
                                <th>Thưởng</th>
                                <th>Khấu trừ</th>
                                <th class="text-success">Thực lĩnh</th>
                                <th>Ghi chú</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salary_records as $sr): ?>
                            <tr>
                                <td class="text-start">
                                    <b><?= htmlspecialchars($sr['full_name']) ?></b><br>
                                    <small class="text-muted"><?= htmlspecialchars($sr['position_name']) ?></small>
                                </td>
                                <td><?= number_format($sr['base_salary'],0,',','.') ?></td>
                                <td class="text-primary"><?= number_format($sr['allowance'],0,',','.') ?></td>
                                <td class="text-success"><?= number_format($sr['bonus'],0,',','.') ?></td>
                                <td class="text-danger"><?= $sr['deductions'] > 0 ? '-' . number_format($sr['deductions'],0,',','.') : '0' ?></td>
                                <td class="fw-bold text-success"><?= number_format($sr['total_salary'],0,',','.') ?></td>
                                <td><?= htmlspecialchars($sr['notes'] ?? '') ?></td>
                                <td>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Xóa bản ghi lương này?')">
                                        <input type="hidden" name="salary_record_id" value="<?= $sr['salary_record_id'] ?>">
                                        <button type="submit" name="delete_salary" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-warning fw-bold">
                            <tr>
                                <td class="text-end">TỔNG:</td>
                                <td><?= number_format($total_base,0,',','.') ?></td>
                                <td><?= number_format($total_allow,0,',','.') ?></td>
                                <td><?= number_format($total_bonus,0,',','.') ?></td>
                                <td><?= number_format($total_deduct,0,',','.') ?></td>
                                <td class="text-success"><?= number_format($total_pay,0,',','.') ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Nhân viên chưa được tính lương -->
            <?php if (!empty($missing_employees)): ?>
            <div class="card shadow-sm mt-3 border-warning">
                <div class="card-header fw-bold text-warning">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>Nhân Viên Chưa Tính Lương
                </div>
                <div class="card-body py-2">
                    <?php foreach ($missing_employees as $me): ?>
                    <span class="badge text-bg-warning me-1 mb-1 fs-6"><?= htmlspecialchars($me['full_name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function filterEmployees() {
    const input = document.getElementById('empSearch');
    const filter = input.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    const select = document.getElementById('empSelect');
    const options = select.getElementsByTagName('option');

    for (let i = 1; i < options.length; i++) {
        let txtValue = options[i].textContent || options[i].innerText;
        let normalizedValue = txtValue.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        if (normalizedValue.indexOf(filter) > -1) {
            options[i].style.display = "";
        } else {
            options[i].style.display = "none";
        }
    }
}

function clearEmpSearch() {
    document.getElementById('empSearch').value = '';
    filterEmployees();
}

function fillBaseSalary(sel) {
    const option = sel.options[sel.selectedIndex];
    if (!option.value) {
        document.getElementById('baseSalaryDisplay').value = '';
        document.getElementById('baseSalaryHidden').value = '';
        document.getElementById('deductInput').value = 0;
        document.getElementById('leaveHint').textContent = '';
        document.getElementById('prorateHint').textContent = '';
        window._baseSalary = 0;
        calcTotal();
        return;
    }

    const salary = parseInt(option.dataset.salary) || 0;
    const autoDeduct = parseInt(option.dataset.deduction) || 0;
    const leaveDays = parseInt(option.dataset.leave) || 0;
    const workingDays = parseInt(option.dataset.days) || 0;
    const reason = option.dataset.prorateReason;

    document.getElementById('baseSalaryDisplay').value = salary.toLocaleString('vi-VN') + ' VND';
    document.getElementById('workingDaysDisplay').value = workingDays + ' ngày làm';
    document.getElementById('baseSalaryHidden').value = salary; 
    document.getElementById('deductInput').value = autoDeduct;
    
    if (leaveDays > 0) {
        document.getElementById('leaveHint').textContent = `(Có ${leaveDays} ngày nghỉ)`;
    } else {
        document.getElementById('leaveHint').textContent = '';
    }

    if (reason === 'pro_rated') {
        document.getElementById('prorateHint').textContent = '(Theo tỷ lệ thời gian làm)';
        document.getElementById('prorateHint').className = 'text-info small ms-2';
    } else {
        document.getElementById('prorateHint').textContent = '(Làm đủ tháng)';
        document.getElementById('prorateHint').className = 'text-success small ms-2';
    }

    window._baseSalary = salary;
    
    // Kiểm tra trạng thái đã chốt chưa
    const status = option.dataset.status || '';
    document.getElementById('empSalaryStatus').value = status;
    
    const isFinalized = (status === 'finalized');
    const inputs = ['allowanceInput', 'bonusInput', 'deductInput', 'notesInput', 'btnSaveSalary'];
    
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            if (id === 'btnSaveSalary') {
                el.style.display = isFinalized ? 'none' : 'block';
            } else {
                el.disabled = isFinalized;
            }
        }
    });

    document.getElementById('lockedNotify').classList.toggle('d-none', !isFinalized);

    calcTotal();
}

function formatMoney(value) {
    const amount = parseInt(value, 10) || 0;
    return amount.toLocaleString('vi-VN') + ' VND';
}

function updateMoneyHints() {
    const allowance = document.getElementById('allowanceInput');
    const bonus = document.getElementById('bonusInput');
    const deduct = document.getElementById('deductInput');

    document.getElementById('allowanceFmt').textContent = formatMoney(allowance ? allowance.value : 0);
    document.getElementById('bonusFmt').textContent = formatMoney(bonus ? bonus.value : 0);
    document.getElementById('deductFmt').textContent = formatMoney(deduct ? deduct.value : 0);
}

function calcTotal() {
    const base = window._baseSalary || 0;
    const allow = parseInt(document.getElementById('allowanceInput').value) || 0;
    const bonus = parseInt(document.getElementById('bonusInput').value) || 0;
    const deduct = parseInt(document.getElementById('deductInput').value) || 0;
    const total = base + allow + bonus - deduct;
    document.getElementById('totalDisplay').value = total.toLocaleString('vi-VN') + ' VND';
    updateMoneyHints();
}

document.addEventListener('DOMContentLoaded', function () {
    updateMoneyHints();
});
</script>
</div>
</div>
</div>
</div>
