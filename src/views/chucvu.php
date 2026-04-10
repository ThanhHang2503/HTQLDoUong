<?php
// Quản lý chức vụ (Manager/Admin only)
requirePermission(AppPermission::MANAGE_STAFF);
global $conn;

$msg_success = '';
$msg_error   = '';

// Thêm chức vụ
if (isset($_POST['add_position'])) {
    $name    = trim(mysqli_real_escape_string($conn, $_POST['position_name'] ?? ''));
    $salary  = (int)($_POST['base_salary'] ?? 0);
    $desc    = trim(mysqli_real_escape_string($conn, $_POST['description'] ?? ''));
    if ($name === '' || $salary <= 0) {
        $msg_error = 'Tên chức vụ và lương cơ bản là bắt buộc.';
    } else {
        mysqli_query($conn, "INSERT INTO positions (position_name, base_salary, description) VALUES ('$name', $salary, '$desc')");
        $msg_success = "Đã thêm chức vụ '$name'.";
    }
}

// Sửa chức vụ
if (isset($_POST['edit_position'])) {
    $pid    = (int)($_POST['position_id'] ?? 0);
    $name   = trim(mysqli_real_escape_string($conn, $_POST['position_name'] ?? ''));
    $salary = (int)($_POST['base_salary'] ?? 0);
    $desc   = trim(mysqli_real_escape_string($conn, $_POST['description'] ?? ''));
    $active = isset($_POST['is_active']) ? 1 : 0;
    if ($pid > 0 && $name !== '' && $salary > 0) {
        mysqli_query($conn, "UPDATE positions SET position_name='$name', base_salary=$salary,
                              description='$desc', is_active=$active WHERE position_id=$pid");
        $msg_success = 'Đã cập nhật chức vụ.';
    }
}

// Đổi chức vụ nhân viên
if (isset($_POST['change_employee_position'])) {
    $acc_id  = (int)($_POST['account_id'] ?? 0);
    $new_pos = (int)($_POST['new_position_id'] ?? 0);
    $reason  = trim(mysqli_real_escape_string($conn, $_POST['change_reason'] ?? ''));
    $date    = trim($_POST['change_date'] ?? date('Y-m-d'));
    if ($acc_id > 0 && $new_pos > 0) {
        // Đóng chức vụ hiện tại
        mysqli_query($conn, "UPDATE employee_positions_history SET end_date='$date'
                              WHERE account_id=$acc_id AND end_date IS NULL");
        // Tạo chức vụ mới
        mysqli_query($conn, "INSERT INTO employee_positions_history (account_id, position_id, start_date, reason, created_by)
                              VALUES ($acc_id, $new_pos, '$date', '$reason', " . currentUserId() . ")");
        // Cập nhật accounts
        mysqli_query($conn, "UPDATE accounts SET position_id=$new_pos WHERE account_id=$acc_id");
        $msg_success = 'Đã đổi chức vụ nhân viên thành công.';
    }
}

// Lấy danh sách chức vụ
$positions_sql = "SELECT p.*, (SELECT COUNT(*) FROM accounts a WHERE a.position_id = p.position_id AND a.status='active') AS employee_count
                  FROM positions p ORDER BY p.is_active DESC, p.base_salary DESC";
$pos_result = mysqli_query($conn, $positions_sql);
$positions = $pos_result ? mysqli_fetch_all($pos_result, MYSQLI_ASSOC) : [];

// Lấy danh sách nhân viên để đổi chức vụ
$emp_sql = "SELECT a.account_id, a.full_name, r.display_name AS role_name,
                   p.position_name AS current_position, p.position_id AS current_position_id
            FROM accounts a
            JOIN roles r ON r.id = a.role_id
            LEFT JOIN positions p ON p.position_id = a.position_id
            WHERE a.status = 'active' AND a.role_id != 1
            ORDER BY a.full_name";
$emp_result = mysqli_query($conn, $emp_sql);
$employees = $emp_result ? mysqli_fetch_all($emp_result, MYSQLI_ASSOC) : [];

// ID đang edit
$edit_pos_id = (int)($_GET['edit_id'] ?? 0);
$edit_pos = null;
if ($edit_pos_id > 0) {
    $er = mysqli_query($conn, "SELECT * FROM positions WHERE position_id = $edit_pos_id");
    $edit_pos = $er ? mysqli_fetch_assoc($er) : null;
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">QUẢN LÝ CHỨC VỤ</h1>
    <div class="head-line"></div>

    <?php if ($msg_success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg_success) ?></div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($msg_error) ?></div>
    <?php endif; ?>

    <div class="container-fluid">
        <div class="row g-3 mt-1">
            <!-- Form thêm/sửa chức vụ -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold bg-primary text-white">
                        <i class="fa-solid fa-briefcase me-2"></i><?= $edit_pos ? 'Sửa Chức Vụ' : 'Thêm Chức Vụ Mới' ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <?php if ($edit_pos): ?>
                                <input type="hidden" name="position_id" value="<?= $edit_pos['position_id'] ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên chức vụ *</label>
                                <input type="text" class="form-control" name="position_name"
                                       value="<?= htmlspecialchars($edit_pos['position_name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Lương cơ bản (VND) *</label>
                                <input type="number" class="form-control" name="base_salary" min="1000000" step="500000"
                                       value="<?= $edit_pos['base_salary'] ?? '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Mô tả</label>
                                <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($edit_pos['description'] ?? '') ?></textarea>
                            </div>
                            <?php if ($edit_pos): ?>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active"
                                       <?= $edit_pos['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Đang hoạt động</label>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="edit_position" class="btn btn-warning fw-bold flex-fill">
                                    <i class="fa-solid fa-floppy-disk me-1"></i>Cập nhật
                                </button>
                                <a href="user_page.php?chucvu" class="btn btn-outline-secondary">Hủy</a>
                            </div>
                            <?php else: ?>
                            <button type="submit" name="add_position" class="btn btn-primary w-100 fw-bold">
                                <i class="fa-solid fa-plus me-1"></i>Thêm chức vụ
                            </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Đổi chức vụ nhân viên -->
                <div class="card shadow-sm mt-3 border-warning">
                    <div class="card-header fw-bold bg-warning text-dark">
                        <i class="fa-solid fa-arrows-rotate me-2"></i>Thay Đổi Chức Vụ Nhân Viên
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-2">
                                <label class="form-label fw-bold">Nhân viên</label>
                                <select class="form-select" name="account_id" required>
                                    <option value="">-- Chọn nhân viên --</option>
                                    <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['account_id'] ?>">
                                        <?= htmlspecialchars($emp['full_name']) ?>
                                        (<?= htmlspecialchars($emp['current_position'] ?? 'Chưa có') ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold">Chức vụ mới</label>
                                <select class="form-select" name="new_position_id" required>
                                    <option value="">-- Chọn chức vụ --</option>
                                    <?php foreach ($positions as $pos): if (!$pos['is_active']) continue; ?>
                                    <option value="<?= $pos['position_id'] ?>">
                                        <?= htmlspecialchars($pos['position_name']) ?>
                                        (<?= number_format($pos['base_salary'],0,',','.') ?> VND)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold">Ngày có hiệu lực</label>
                                <input type="date" class="form-control" name="change_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold">Lý do thay đổi</label>
                                <input type="text" class="form-control" name="change_reason" placeholder="VD: Thăng chức, luân chuyển...">
                            </div>
                            <button type="submit" name="change_employee_position" class="btn btn-warning w-100 fw-bold"
                                    onclick="return confirm('Xác nhận thay đổi chức vụ?')">
                                <i class="fa-solid fa-arrows-rotate me-1"></i>Xác nhận thay đổi
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danh sách chức vụ -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold"><i class="fa-solid fa-list me-2"></i>Danh Sách Chức Vụ</div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover table-bordered mb-0 text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>Mã</th>
                                    <th>Tên chức vụ</th>
                                    <th>Lương cơ bản</th>
                                    <th>Số NV</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($positions as $pos): ?>
                                <tr class="<?= !$pos['is_active'] ? 'table-secondary' : '' ?>">
                                    <td>#<?= $pos['position_id'] ?></td>
                                    <td class="text-start fw-bold"><?= htmlspecialchars($pos['position_name']) ?></td>
                                    <td class="text-success fw-bold"><?= number_format($pos['base_salary'],0,',','.') ?> VND</td>
                                    <td><span class="badge text-bg-primary"><?= $pos['employee_count'] ?> NV</span></td>
                                    <td>
                                        <span class="badge text-bg-<?= $pos['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $pos['is_active'] ? 'Hoạt động' : 'Ngừng' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="user_page.php?chucvu&edit_id=<?= $pos['position_id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bảng nhân viên + chức vụ hiện tại -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header fw-bold"><i class="fa-solid fa-users me-2"></i>Nhân Viên & Chức Vụ Hiện Tại</div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped table-bordered mb-0 text-center">
                            <thead class="table-secondary">
                                <tr><th>Nhân viên</th><th>Vai trò</th><th>Chức vụ hiện tại</th><th>Lương cơ bản</th><th>Lịch sử</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td class="text-start fw-bold"><?= htmlspecialchars($emp['full_name']) ?></td>
                                    <td><?= htmlspecialchars($emp['role_name']) ?></td>
                                    <td><?= htmlspecialchars($emp['current_position'] ?? '<span class="text-muted">Chưa phân công</span>') ?></td>
                                    <td>
                                        <?php
                                        if ($emp['current_position_id']) {
                                            $ps = array_filter($positions, fn($p)=>$p['position_id']==$emp['current_position_id']);
                                            $ps = array_values($ps);
                                            echo $ps ? number_format($ps[0]['base_salary'],0,',','.') . ' VND' : '-';
                                        } else echo '-';
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-xs btn-outline-info btn-sm"
                                                onclick="viewHistory(<?= $emp['account_id'] ?>, '<?= htmlspecialchars($emp['full_name'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
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
    </div>
</div>

<!-- Modal xem lịch sử chức vụ -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalTitle">Lịch sử chức vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="historyModalBody">
                <div class="text-center"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewHistory(accId, name) {
    document.getElementById('historyModalTitle').textContent = 'Lịch sử chức vụ: ' + name;
    document.getElementById('historyModalBody').innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
    new bootstrap.Modal(document.getElementById('historyModal')).show();

    fetch('user_page.php?chucvu_history_api=1&account_id=' + accId)
        .then(r => r.json())
        .then(data => {
            let html = '<table class="table table-sm table-striped"><thead><tr><th>Chức vụ</th><th>Từ ngày</th><th>Đến ngày</th><th>Lý do</th></tr></thead><tbody>';
            data.forEach(h => {
                html += `<tr><td>${h.position_name}</td><td>${h.start_date}</td><td>${h.end_date||'<span class="badge bg-success">Hiện tại</span>'}</td><td>${h.reason||'-'}</td></tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('historyModalBody').innerHTML = html || '<p class="text-muted">Chưa có lịch sử.</p>';
        });
}
</script>
</div>
</div>
</div>
</div>
