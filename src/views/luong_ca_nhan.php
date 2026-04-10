<?php
// Xem lương cá nhân + in bảng lương
global $conn;
$uid = currentUserId();

$selected_year  = (int)($_GET['year']  ?? date('Y'));
$selected_month = (int)($_GET['month'] ?? 0);

// Lấy danh sách năm có lương
$years_sql = "SELECT DISTINCT salary_year FROM salary_records WHERE account_id = $uid ORDER BY salary_year DESC";
$years_result = mysqli_query($conn, $years_sql);
$years = $years_result ? mysqli_fetch_all($years_result, MYSQLI_ASSOC) : [];

// Nếu chưa có năm nào, dùng năm hiện tại
if (empty($years)) {
    $years = [['salary_year' => date('Y')]];
}

// Lấy bản ghi lương
$where_month = $selected_month > 0 ? "AND salary_month = $selected_month" : '';
$salary_sql = "SELECT sr.*, p.position_name, a.full_name
               FROM salary_records sr
               JOIN positions p ON p.position_id = sr.position_id
               JOIN accounts a ON a.account_id = sr.account_id
               WHERE sr.account_id = $uid AND sr.salary_year = $selected_year $where_month
               ORDER BY sr.salary_year DESC, sr.salary_month DESC";
$salary_result = mysqli_query($conn, $salary_sql);
$salary_records = $salary_result ? mysqli_fetch_all($salary_result, MYSQLI_ASSOC) : [];

// Tính tổng năm
$total_year = array_sum(array_column($salary_records, 'total_salary'));

// Cách tính lương
$position_sql = "SELECT p.position_name, p.base_salary, p.description
                 FROM accounts a JOIN positions p ON p.position_id = a.position_id
                 WHERE a.account_id = $uid";
$pos_r = mysqli_query($conn, $position_sql);
$my_position = $pos_r ? mysqli_fetch_assoc($pos_r) : null;

$is_print = isset($_GET['print']);
if ($is_print) {
    // chế độ in
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">
    <title>Bảng Lương</title>
    <style>
    body{font-family:Arial,sans-serif;margin:20px;font-size:13px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{border:1px solid #333;padding:6px 8px;text-align:center}
    th{background:#f0f0f0}
    .text-right{text-align:right}
    h2{text-align:center}
    .total-row{font-weight:bold;background:#fff9c4}
    </style></head><body>';
    echo '<h2>BẢNG LƯƠNG - NĂM ' . $selected_year . '</h2>';
    echo '<p style="text-align:center">Nhân viên: <b>' . htmlspecialchars($salary_records[0]['full_name'] ?? '') . '</b> | Chức vụ: <b>' . htmlspecialchars($salary_records[0]['position_name'] ?? '') . '</b></p>';
    echo '<table><thead><tr><th>Tháng</th><th>Lương cơ bản</th><th>Phụ cấp</th><th>Thưởng</th><th>Khấu trừ</th><th>Thực lĩnh</th><th>Ghi chú</th></tr></thead><tbody>';
    foreach ($salary_records as $sr) {
        echo '<tr>';
        echo '<td>Tháng ' . $sr['salary_month'] . '</td>';
        echo '<td class="text-right">' . number_format($sr['base_salary'],0,',','.') . '</td>';
        echo '<td class="text-right">' . number_format($sr['allowance'],0,',','.') . '</td>';
        echo '<td class="text-right">' . number_format($sr['bonus'],0,',','.') . '</td>';
        echo '<td class="text-right">' . number_format($sr['deductions'],0,',','.') . '</td>';
        echo '<td class="text-right"><b>' . number_format($sr['total_salary'],0,',','.') . '</b></td>';
        echo '<td>' . htmlspecialchars($sr['notes'] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '<tr class="total-row"><td colspan="5" style="text-align:right">TỔNG LƯƠNG NĂM:</td>';
    echo '<td class="text-right">' . number_format($total_year,0,',','.') . '</td><td></td></tr>';
    echo '</tbody></table>';
    echo '<p style="margin-top:20px;font-size:11px">Ngày in: ' . date('d/m/Y H:i') . '</p>';
    echo '<script>window.print()</script></body></html>';
    exit;
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">BẢNG LƯƠNG CỦA TÔI</h1>
    <div class="head-line"></div>

    <!-- Cách tính lương -->
    <?php if ($my_position): ?>
    <div class="alert alert-info mt-3">
        <h6 class="fw-bold"><i class="fa-solid fa-circle-info me-2"></i>Cách tính lương của bạn (<?= htmlspecialchars($my_position['position_name']) ?>)</h6>
        <p class="mb-1"><b>Công thức:</b> Lương thực lĩnh = Lương cơ bản + Phụ cấp + Thưởng − Khấu trừ</p>
        <p class="mb-0">
            <b>Lương cơ bản hiện tại:</b>
            <span class="text-success fw-bold"><?= number_format($my_position['base_salary'],0,',','.') ?> VND/tháng</span>
            (theo chức vụ <?= htmlspecialchars($my_position['position_name']) ?>)
        </p>
    </div>
    <?php endif; ?>

    <!-- Bộ lọc -->
    <form class="row g-2 align-items-end mt-2" method="GET" action="user_page.php">
        <input type="hidden" name="luong_ca_nhan" value="1">
        <div class="col-md-3">
            <label class="form-label">Năm</label>
            <select class="form-select" name="year">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y['salary_year'] ?>" <?= $y['salary_year'] == $selected_year ? 'selected' : '' ?>>
                        Năm <?= $y['salary_year'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tháng (0 = tất cả)</label>
            <select class="form-select" name="month">
                <option value="0" <?= $selected_month == 0 ? 'selected':'' ?>>Cả năm</option>
                <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $m == $selected_month ? 'selected':'' ?>>Tháng <?= $m ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-filter me-1"></i>Xem</button>
        </div>
        <div class="col-md-2">
            <a class="btn btn-outline-secondary w-100"
               href="user_page.php?luong_ca_nhan&year=<?= $selected_year ?>&month=<?= $selected_month ?>&print=1"
               target="_blank">
                <i class="fa-solid fa-print me-1"></i>In bảng lương
            </a>
        </div>
    </form>

    <!-- Bảng lương -->
    <div class="mt-3">
        <?php if (empty($salary_records)): ?>
            <div class="alert alert-warning">Chưa có dữ liệu lương cho kỳ đã chọn.</div>
        <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header fw-bold">
                Bảng lương năm <?= $selected_year ?>
                <?= $selected_month > 0 ? ' - Tháng ' . $selected_month : ' (Cả năm)' ?>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover table-bordered mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Tháng</th>
                            <th>Chức vụ</th>
                            <th>Lương cơ bản</th>
                            <th>Phụ cấp</th>
                            <th>Thưởng</th>
                            <th>Khấu trừ</th>
                            <th class="text-success">Thực lĩnh</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salary_records as $sr): ?>
                        <tr>
                            <td class="fw-bold">Tháng <?= $sr['salary_month'] ?>/<?= $sr['salary_year'] ?></td>
                            <td><?= htmlspecialchars($sr['position_name']) ?></td>
                            <td><?= number_format($sr['base_salary'],0,',','.') ?></td>
                            <td class="text-primary"><?= number_format($sr['allowance'],0,',','.') ?></td>
                            <td class="text-success"><?= number_format($sr['bonus'],0,',','.') ?></td>
                            <td class="text-danger"><?= $sr['deductions'] > 0 ? '-' . number_format($sr['deductions'],0,',','.') : '0' ?></td>
                            <td class="text-success fw-bold fs-6"><?= number_format($sr['total_salary'],0,',','.') ?> VND</td>
                            <td><?= htmlspecialchars($sr['notes'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-warning fw-bold">
                        <tr>
                            <td colspan="6" class="text-end">TỔNG LƯƠNG <?= $selected_month > 0 ? '' : 'NĂM ' . $selected_year ?>:</td>
                            <td class="text-success fs-5"><?= number_format($total_year,0,',','.') ?> VND</td>
                            <td></td>
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
</div>
</div>
