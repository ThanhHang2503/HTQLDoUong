<?php
// Xem/sửa hồ sơ cá nhân của chính mình
global $conn;
$uid = currentUserId();

// Lấy thông tin hiện tại
$sql = "SELECT a.account_id, a.full_name, a.email, a.phone, a.address, a.birth_date,
               a.gender, a.hire_date, r.display_name AS role_display, p.position_name,
               p.base_salary, a.created_at
        FROM accounts a
        JOIN roles r ON r.id = a.role_id
        LEFT JOIN positions p ON p.position_id = a.position_id
        WHERE a.account_id = $uid";
$result = mysqli_query($conn, $sql);
$profile = mysqli_fetch_assoc($result);

// Xử lý cập nhật thông tin
if (isset($_POST['update_profile'])) {
    $full_name = trim(htmlspecialchars($_POST['full_name'] ?? ''));
    $phone     = trim(mysqli_real_escape_string($conn, $_POST['phone'] ?? ''));
    $address   = trim(mysqli_real_escape_string($conn, $_POST['address'] ?? ''));
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender    = in_array($_POST['gender'] ?? '', ['nam','nữ','khác']) ? $_POST['gender'] : null;

    if ($full_name === '') {
        setNotify('error', 'Họ tên không được để trống.');
    } else {
        $safe_name = mysqli_real_escape_string($conn, $full_name);
        $bd_sql = $birth_date ? "'$birth_date'" : 'NULL';
        $gd_sql = $gender ? "'$gender'" : 'NULL';
        $upd = "UPDATE accounts SET full_name='$safe_name', phone='$phone',
                address='$address', birth_date=$bd_sql, gender=$gd_sql
                WHERE account_id = $uid";
        mysqli_query($conn, $upd);
        $_SESSION['full_name'] = $full_name;
        setNotify('success', 'Cập nhật thông tin thành công!');
    }
    redirect('user_page.php?profile'); 
    exit;
}

// Đổi mật khẩu
if (isset($_POST['change_password'])) {
    $old_pass = md5($_POST['old_password'] ?? '');
    $new_pass = trim($_POST['new_password'] ?? '');
    $cnf_pass = trim($_POST['confirm_password'] ?? '');

    $chk = mysqli_query($conn, "SELECT account_id FROM accounts WHERE account_id=$uid AND password='$old_pass'");
    if (mysqli_num_rows($chk) === 0) {
        setNotify('error', 'Mật khẩu hiện tại không đúng.');
    } elseif (strlen($new_pass) < 6) {
        setNotify('error', 'Mật khẩu mới phải có ít nhất 6 ký tự.');
    } elseif ($new_pass !== $cnf_pass) {
        setNotify('error', 'Xác nhận mật khẩu không khớp.');
    } else {
        $np = md5($new_pass);
        mysqli_query($conn, "UPDATE accounts SET password='$np' WHERE account_id=$uid");
        setNotify('success', 'Đổi mật khẩu thành công!');
    }
    redirect('user_page.php?profile'); 
    exit;
}

// Lấy lịch sử chức vụ
$pos_sql = "SELECT eph.start_date, eph.end_date, p.position_name, p.base_salary, eph.reason
            FROM employee_positions_history eph
            JOIN positions p ON p.position_id = eph.position_id
            WHERE eph.account_id = $uid
            ORDER BY eph.start_date DESC";
$pos_result = mysqli_query($conn, $pos_sql);
$pos_history = $pos_result ? mysqli_fetch_all($pos_result, MYSQLI_ASSOC) : [];
?>

<div class="dash_board px-2">
    <h1 class="head-name">HỒ SƠ CÁ NHÂN</h1>
    <div class="head-line"></div>



    <div class="container-fluid">
        <div class="row g-3 mt-2">
            <!-- Thông tin cơ bản -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold bg-primary text-white">
                        <i class="fa-solid fa-user-pen me-2"></i>Cập Nhật Thông Tin
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Họ và Tên *</label>
                                    <input type="text" class="form-control" name="full_name"
                                           value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Email (không đổi được)</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Số điện thoại</label>
                                    <input type="text" class="form-control" name="phone"
                                           value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Ngày sinh</label>
                                    <input type="date" class="form-control" name="birth_date"
                                           value="<?= htmlspecialchars($profile['birth_date'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Giới tính</label>
                                    <select class="form-select" name="gender">
                                        <option value="">-- Chọn --</option>
                                        <option value="nam"  <?= ($profile['gender'] ?? '') === 'nam'  ? 'selected' : '' ?>>Nam</option>
                                        <option value="nữ"   <?= ($profile['gender'] ?? '') === 'nữ'   ? 'selected' : '' ?>>Nữ</option>
                                        <option value="khác" <?= ($profile['gender'] ?? '') === 'khác' ? 'selected' : '' ?>>Khác</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Ngày vào làm</label>
                                    <input type="text" class="form-control" value="<?= $profile['hire_date'] ? date('d/m/Y', strtotime($profile['hire_date'])) : 'N/A' ?>" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Địa chỉ</label>
                                    <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" name="update_profile" class="btn btn-primary fw-bold">
                                    <i class="fa-solid fa-floppy-disk me-1"></i>Lưu thay đổi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Đổi mật khẩu -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header fw-bold bg-warning text-dark">
                        <i class="fa-solid fa-lock me-2"></i>Đổi Mật Khẩu
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Mật khẩu hiện tại</label>
                                    <input type="password" class="form-control" name="old_password" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Mật khẩu mới (≥6 ký tự)</label>
                                    <input type="password" class="form-control" name="new_password" minlength="6" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Xác nhận mật khẩu mới</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" name="change_password" class="btn btn-warning fw-bold">
                                    <i class="fa-solid fa-key me-1"></i>Đổi mật khẩu
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Thông tin vai trò + lịch sử chức vụ -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold bg-success text-white">
                        <i class="fa-solid fa-id-card me-2"></i>Thông Tin Công Việc
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><th style="width:50%">Vai trò hệ thống</th><td><?= htmlspecialchars($profile['role_display'] ?? '-') ?></td></tr>
                            <tr><th>Chức vụ hiện tại</th><td><?= htmlspecialchars($profile['position_name'] ?? 'Chưa phân công') ?></td></tr>
                            <?php if (currentRole() !== AppRole::ADMIN): ?>
                            <tr><th>Lương cơ bản</th><td class="text-success fw-bold"><?= $profile['base_salary'] ? number_format($profile['base_salary'],0,',','.') . ' VND' : '-' ?></td></tr>
                            <?php endif; ?>
                            <tr><th>Tham gia từ</th><td><?= $profile['hire_date'] ? date('d/m/Y', strtotime($profile['hire_date'])) : 'N/A' ?></td></tr>
                            <tr><th>ID Tài khoản</th><td>#<?= $profile['account_id'] ?></td></tr>
                        </table>
                    </div>
                </div>

                <!-- Lịch sử chức vụ -->
                <?php if (currentRole() !== AppRole::ADMIN): ?>
                <div class="card shadow-sm mt-3">
                    <div class="card-header fw-bold">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>Lịch Sử Chức Vụ
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($pos_history)): ?>
                            <p class="p-3 mb-0 text-muted">Chưa có lịch sử chức vụ.</p>
                        <?php else: ?>
                        <table class="table table-sm table-striped mb-0">
                            <thead><tr><th>Chức vụ</th><th>Từ</th><th>Đến</th></tr></thead>
                            <tbody>
                                <?php foreach ($pos_history as $ph): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ph['position_name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($ph['start_date'])) ?></td>
                                    <td><?= $ph['end_date'] ? date('d/m/Y', strtotime($ph['end_date'])) : '<span class="badge text-bg-success">Hiện tại</span>' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
</div>
</div>
</div>
