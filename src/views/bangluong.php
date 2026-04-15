<?php
// Bảng lương - Manager tính và lưu lương cho nhân viên
requirePermission(AppPermission::MANAGE_STAFF);
global $conn;

$msg_success = '';
$msg_error   = '';

$sel_month = (int)($_GET['month'] ?? date('m'));
$sel_year  = (int)($_GET['year'] ?? date('Y'));
$sel_year  = max(2020, min($sel_year, 2030));

/**
 * Tính khấu trừ tự động dựa trên số ngày nghỉ và chức vụ tại thời điểm nghỉ
 * Công thức: Σ (Lương CB của chức vụ lúc đó / 30) * số ngày nghỉ tương ứng
 */
function getAutoDeduction($conn, $accountId, $month, $year) {
    $startOfMonth = sprintf("%04d-%02d-01", $year, $month);
    $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
    
    // Lấy tất cả các ngày nghỉ được chấp thuận trong tháng (để hiển thị gợi ý)
    $sql = "SELECT from_date, to_date FROM leave_requests 
            WHERE account_id = $accountId AND status = 'chấp thuận'
            AND from_date <= '$endOfMonth' AND to_date >= '$startOfMonth'";
    $res = mysqli_query($conn, $sql);
    
    $leaveDates = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $curr = max(strtotime($row['from_date']), strtotime($startOfMonth));
            $last = min(strtotime($row['to_date']), strtotime($endOfMonth));
            while ($curr <= $last) {
                $leaveDates[] = date('Y-m-d', $curr);
                $curr = strtotime('+1 day', $curr);
            }
        }
    }
    $leaveDates = array_unique($leaveDates);
    
    // Trả về 0 cho số tiền (vì đã trừ vào base salary qua số ngày làm)
    return ['total' => 0, 'days' => count($leaveDates)];
}

/**
 * Tính lương cơ bản gộp (Pro-rated) dựa trên lịch sử chức vụ
 * Công thức: Σ (Số ngày giữ chức vụ / 30) * Lương CB của chức vụ đó
 */
function getProRatedBaseSalary($conn, $accountId, $month, $year) {
    $startOfMonth = sprintf("%04d-%02d-01", $year, $month);
    $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
    $totalDaysInMonth = (int)date('t', strtotime($startOfMonth));

    // 1. Lấy ngày nghỉ không lương (không tính lương cho các ngày này)
    $leaveSql = "SELECT from_date, to_date FROM leave_requests 
                 WHERE account_id = $accountId AND status = 'chấp thuận' AND leave_type = 'không lương'
                 AND from_date <= '$endOfMonth' AND to_date >= '$startOfMonth'";
    $leaveRes = mysqli_query($conn, $leaveSql);
    $unpaidLeaveDates = [];
    if ($leaveRes) {
        while ($l = mysqli_fetch_assoc($leaveRes)) {
            $curr = max(strtotime($l['from_date']), strtotime($startOfMonth));
            $last = min(strtotime($l['to_date']), strtotime($endOfMonth));
            while ($curr <= $last) {
                $unpaidLeaveDates[date('Y-m-d', $curr)] = true;
                $curr = strtotime('+1 day', $curr);
            }
        }
    }

    // 2. Lấy lịch sử chức vụ
    $historySql = "SELECT eph.start_date, eph.end_date, p.base_salary
                   FROM employee_positions_history eph
                   JOIN positions p ON p.position_id = eph.position_id
                   WHERE eph.account_id = $accountId
                   AND eph.start_date <= '$endOfMonth'
                   AND (eph.end_date IS NULL OR eph.end_date >= '$startOfMonth')
                   ORDER BY eph.start_date ASC";
    $histRes = mysqli_query($conn, $historySql);
    $history = $histRes ? mysqli_fetch_all($histRes, MYSQLI_ASSOC) : [];

    // Nếu không có lịch sử, dùng chức vụ hiện tại
    if (empty($history)) {
        $sql = "SELECT p.base_salary FROM accounts a 
                JOIN positions p ON p.position_id = a.position_id 
                WHERE a.account_id = $accountId";
        $cp_res = mysqli_query($conn, $sql);
        $cp = mysqli_fetch_assoc($cp_res);
        $history[] = [
            'start_date' => '2000-01-01',
            'end_date' => '2099-12-31',
            'base_salary' => $cp['base_salary'] ?? 0
        ];
    }

    $workingDaysByPos = []; 
    $totalWorkingDays = 0;

    for ($d = 1; $d <= $totalDaysInMonth; $d++) {
        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $d);
        $ts = strtotime($dateStr);

        $currentPos = null;
        foreach ($history as $h) {
            $sTs = strtotime($h['start_date']);
            $eTs = $h['end_date'] ? strtotime($h['end_date']) : strtotime('2099-12-31');
            if ($ts >= $sTs && $ts <= $eTs) {
                $currentPos = $h;
                break;
            }
        }

        if ($currentPos && !isset($unpaidLeaveDates[$dateStr])) {
            $sal = (int)$currentPos['base_salary'];
            if (!isset($workingDaysByPos[$sal])) {
                $workingDaysByPos[$sal] = ['days' => 0, 'base_salary' => $sal];
            }
            $workingDaysByPos[$sal]['days']++;
            $totalWorkingDays++;
        }
    }

    $finalBaseSalary = 0;
    if ($totalWorkingDays >= 28) {
        $weightedSum = 0;
        foreach ($workingDaysByPos as $pos) {
            $weightedSum += ($pos['days'] / $totalWorkingDays) * $pos['base_salary'];
        }
        $finalBaseSalary = $weightedSum;
    } else {
        foreach ($workingDaysByPos as $pos) {
            $finalBaseSalary += ($pos['days'] * $pos['base_salary'] / 30);
        }
    }

    return [
        'total' => round($finalBaseSalary),
        'working_days' => $totalWorkingDays,
        'is_prorated' => ($totalWorkingDays < 28 || count($workingDaysByPos) > 1)
    ];
}

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

    if ($acc_id > 0) {
        $base_salary = (int)($_POST['base_salary_val'] ?? 0); // Lấy giá trị thực tế truyền từ UI
        $call = "CALL CalculateEmployeeSalary($acc_id, $month, $year, $base_salary, $allowance, $bonus, $deductions, $by, '$notes')";
        if (mysqli_multi_query($conn, $call)) {
            do { $r = mysqli_store_result($conn); if ($r) mysqli_free_result($r); } while (mysqli_more_results($conn) && mysqli_next_result($conn));
            $msg_success = 'Đã tính và lưu lương thành công.';
        } else {
            $msg_error = 'Lỗi: ' . mysqli_error($conn);
        }
    }
}

// Xóa bản ghi lương
if (isset($_POST['delete_salary'])) {
    $sid = (int)($_POST['salary_record_id'] ?? 0);
    mysqli_query($conn, "DELETE FROM salary_records WHERE salary_record_id=$sid");
    $msg_success = 'Đã xóa bản ghi lương.';
}

// Danh sách nhân viên (để tính lương)
$emp_sql = "SELECT a.account_id, a.full_name, r.display_name AS role_name,
                   p.position_name, p.base_salary
            FROM accounts a
            JOIN roles r ON r.id = a.role_id
            LEFT JOIN positions p ON p.position_id = a.position_id
            WHERE a.hr_status = 'active' AND a.role_id != 1
            ORDER BY a.full_name";
$emp_result = mysqli_query($conn, $emp_sql);
$employees = [];
if ($emp_result) {
    while ($e = mysqli_fetch_assoc($emp_result)) {
        // Khấu trừ nghỉ
        $deductInfo = getAutoDeduction($conn, $e['account_id'], $sel_month, $sel_year);
        $e['auto_deduction'] = $deductInfo['total'];
        $e['leave_days'] = $deductInfo['days'];

        // Lương cơ bản gộp (Pro-rated)
        $proRateInfo = getProRatedBaseSalary($conn, $e['account_id'], $sel_month, $sel_year);
        $e['base_salary'] = $proRateInfo['total'];
        $e['working_days'] = $proRateInfo['working_days'];
        $e['is_prorated'] = $proRateInfo['is_prorated'];

        $employees[] = $e;
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
            <a class="btn btn-outline-secondary"
               href="user_page.php?bangluong&month=<?=$sel_month?>&year=<?=$sel_year?>&print=1">
                <i class="fa-solid fa-print me-1"></i>In bảng lương
            </a>
        </div>
    </div>

    <!-- Tóm tắt thống kê -->
    <div class="row g-2 mb-3">
        <div class="col-md-2">
            <div class="card text-center border-primary">
                <div class="card-body p-2">
                    <div class="fs-5 fw-bold text-primary"><?= count($salary_records) ?></div>
                    <small>Đã tính lương</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-warning">
                <div class="card-body p-2">
                    <div class="fs-5 fw-bold text-warning"><?= count($missing_employees) ?></div>
                    <small>Chưa tính lương</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body p-2">
                    <div class="fs-6 fw-bold text-success"><?= number_format($total_pay,0,',','.') ?></div>
                    <small>Tổng quỹ lương (VND)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body p-2">
                    <div class="fs-6 fw-bold text-info"><?= number_format($total_bonus,0,',','.') ?></div>
                    <small>Tổng thưởng (VND)</small>
                </div>
            </div>
        </div>
    </div>

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
                                        data-prorated="<?= $emp['is_prorated'] ? '1' : '0' ?>"
                                        data-pos="<?= htmlspecialchars($emp['position_name'] ?? '') ?>"
                                        data-days="<?= $emp['working_days'] ?>">

                                    <?= htmlspecialchars($emp['full_name']) ?>
                                    <?= $emp['is_prorated'] ? ' (Gộp)' : '' ?>
                                    <?= in_array($emp['account_id'], $has_salary) ? '✓' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Lương cơ bản (tự động) <span id="prorateHint" class="text-info small ms-2"></span></label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" id="baseSalaryDisplay" disabled>
                                <span class="input-group-text bg-light" id="workingDaysDisplay">0 ngày</span>
                            </div>
                            <input type="hidden" name="base_salary_val" id="baseSalaryHidden">
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Phụ cấp (VND)</label>
                            <input type="number" class="form-control" name="allowance" id="allowanceInput"
                                   value="500000" min="0" step="100000" onchange="calcTotal()">
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Thưởng (VND)</label>
                            <input type="number" class="form-control" name="bonus" id="bonusInput"
                                   value="0" min="0" step="100000" onchange="calcTotal()">
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Khấu trừ (VND) <span id="leaveHint" class="text-danger small ms-2"></span></label>
                            <input type="number" class="form-control" name="deductions" id="deductInput"
                                   value="0" min="0" step="1" onchange="calcTotal()">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Thực lĩnh (ước tính)</label>
                            <input type="text" class="form-control fw-bold text-success bg-light" id="totalDisplay" disabled>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Ghi chú</label>
                            <input type="text" class="form-control" name="notes" placeholder="Ghi chú lương tháng này...">
                        </div>
                        <button type="submit" name="save_salary" class="btn btn-success w-100 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Lưu lương tháng <?= $sel_month ?>/<?= $sel_year ?>
                        </button>
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
    const isProrated = option.dataset.prorated === '1';

    document.getElementById('baseSalaryDisplay').value = salary.toLocaleString('vi-VN') + ' VND';
    document.getElementById('workingDaysDisplay').textContent = workingDays + ' ngày làm';
    document.getElementById('baseSalaryHidden').value = salary; 
    document.getElementById('deductInput').value = autoDeduct;
    
    if (leaveDays > 0) {
        document.getElementById('leaveHint').textContent = `(Có ${leaveDays} ngày nghỉ)`;
    } else {
        document.getElementById('leaveHint').textContent = '';
    }

    if (isProrated) {
        document.getElementById('prorateHint').textContent = '(Đã tính theo logic ≥28 ngày)';
    } else {
        document.getElementById('prorateHint').textContent = '';
    }

    window._baseSalary = salary;
    calcTotal();
}
function calcTotal() {
    const base = window._baseSalary || 0;
    const allow = parseInt(document.getElementById('allowanceInput').value) || 0;
    const bonus = parseInt(document.getElementById('bonusInput').value) || 0;
    const deduct = parseInt(document.getElementById('deductInput').value) || 0;
    const total = base + allow + bonus - deduct;
    document.getElementById('totalDisplay').value = total.toLocaleString('vi-VN') + ' VND';
}
</script>
</div>
</div>
</div>
</div>
