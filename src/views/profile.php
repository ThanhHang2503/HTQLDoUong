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

// 2. Không xử lý POST tại đây nữa, đã chuyển sang AJAX (api/user/profile.php)

// Lấy lịch sử chức vụ
$pos_sql = "SELECT eph.start_date, eph.end_date, p.position_name, p.base_salary, eph.reason
            FROM employee_positions_history eph
            JOIN positions p ON p.position_id = eph.position_id
            WHERE eph.account_id = $uid
            ORDER BY eph.start_date ASC"; // Sorted ASC for logic check
$pos_result = mysqli_query($conn, $pos_sql);
$pos_history = $pos_result ? mysqli_fetch_all($pos_result, MYSQLI_ASSOC) : [];

// Check if we need to show a virtual "Hire" entry
$first_hist_date = !empty($pos_history) ? $pos_history[0]['start_date'] : null;
$show_hire_virtual = false;
if ($profile['hire_date'] && ($first_hist_date === null || $profile['hire_date'] < $first_hist_date)) {
    $show_hire_virtual = true;
}

// Re-sort for display (Descending)
usort($pos_history, function($a, $b) {
    return strcmp($b['start_date'], $a['start_date']);
});
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
                        <form id="formUpdateProfile">
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
                                    <label class="form-label fw-bold">Số điện thoại (9-11 số)</label>
                                    <input type="text" class="form-control" name="phone"
                                           value="<?= htmlspecialchars($profile['phone'] ?? '') ?>"
                                           pattern="[0-9]{9,11}" title="Chỉ nhập số, độ dài 9-11 ký tự">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Ngày sinh (Phải đủ 18 tuổi)</label>
                                    <input type="date" class="form-control" name="birth_date"
                                           value="<?= htmlspecialchars($profile['birth_date'] ?? '') ?>"
                                           max="<?= (date('Y') - 18) . '-12-31' ?>">
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
                            <div id="profileMsg" class="mt-2"></div>
                            <div class="mt-3">
                                <button type="submit" id="btnSaveProfile" class="btn btn-primary fw-bold">
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
                        <form id="formChangePassword">
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
                            <div id="passwordMsg" class="mt-2"></div>
                            <div class="mt-3">
                                <button type="submit" id="btnSavePassword" class="btn btn-warning fw-bold">
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
                                    <td><?= $ph['end_date'] ? date('d/m/Y', strtotime($ph['end_date'])) : '<span class="badge text-bg-success">...</span>' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if ($show_hire_virtual): ?>
                                <tr class="table-info">
                                    <td colspan="2"><i class="fa-solid fa-star me-1"></i> Ngày vào làm hạch toán</td>
                                    <td><?= date('d/m/Y', strtotime($profile['hire_date'])) ?></td>
                                </tr>
                                <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Xử lý lưu hồ sơ
    const formProfile = document.getElementById('formUpdateProfile');
    formProfile.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnSaveProfile');
        const msg = document.getElementById('profileMsg');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang lưu...';
        msg.innerHTML = '';

        const payload = {
            update_profile: true,
            full_name: formProfile.querySelector('[name="full_name"]').value,
            phone: formProfile.querySelector('[name="phone"]').value,
            birth_date: formProfile.querySelector('[name="birth_date"]').value,
            gender: formProfile.querySelector('[name="gender"]').value,
            address: formProfile.querySelector('[name="address"]').value
        };

        try {
            const res = await fetch('api/user/profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.success) {
                msg.innerHTML = `<div class="alert alert-success mt-2 py-2"><i class="fa-solid fa-circle-check me-2"></i>${result.message}</div>`;
                // Cập nhật tên trên header nếu có thay đổi
                const headName = document.querySelector('.head-name');
                if (headName) {
                    // Cập nhật text liên quan nếu cần
                }
            } else {
                msg.innerHTML = `<div class="alert alert-danger mt-2 py-2"><i class="fa-solid fa-circle-xmark me-2"></i>${result.message}</div>`;
            }
        } catch (err) {
            msg.innerHTML = `<div class="alert alert-danger mt-2 py-2"><i class="fa-solid fa-triangle-exclamation me-2"></i>Không thể kết nối tới máy chủ.</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Lưu thay đổi';
        }
    });

    // 2. Xử lý đổi mật khẩu
    const formPassword = document.getElementById('formChangePassword');
    formPassword.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnSavePassword');
        const msg = document.getElementById('passwordMsg');

        if (formPassword.querySelector('[name="new_password"]').value !== formPassword.querySelector('[name="confirm_password"]').value) {
            msg.innerHTML = `<div class="alert alert-danger mt-2 py-2">Xác nhận mật khẩu mới không khớp.</div>`;
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';
        msg.innerHTML = '';

        const payload = {
            change_password: true,
            old_password: formPassword.querySelector('[name="old_password"]').value,
            new_password: formPassword.querySelector('[name="new_password"]').value,
            confirm_password: formPassword.querySelector('[name="confirm_password"]').value
        };

        try {
            const res = await fetch('api/user/profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.success) {
                msg.innerHTML = `<div class="alert alert-success mt-2 py-2"><i class="fa-solid fa-circle-check me-2"></i>${result.message}</div>`;
                formPassword.reset();
            } else {
                msg.innerHTML = `<div class="alert alert-danger mt-2 py-2"><i class="fa-solid fa-circle-xmark me-2"></i>${result.message}</div>`;
            }
        } catch (err) {
            msg.innerHTML = `<div class="alert alert-danger mt-2 py-2"><i class="fa-solid fa-triangle-exclamation me-2"></i>Không thể kết nối tới máy chủ.</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-key me-1"></i>Đổi mật khẩu';
        }
    });
});
</script>
