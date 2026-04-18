<?php
requirePermission(AppPermission::MANAGE_ACCOUNTS);
?>
<div class="dash_board px-2">
    <h1 class="head-name">QUẢN LÝ TÀI KHOẢN HỆ THỐNG</h1>
    <div class="head-line"></div>
    <div class="container-fluid mt-3">
        <!-- Bảng điều khiển -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="text-muted"><i class="fa-solid fa-circle-info me-2"></i>Vì lý do bảo mật dữ liệu lịch sử, không hỗ trợ chức năng xóa (DELETE) tài khoản. Chỉ có thể Chuyển trạng thái Đang làm/Nghỉ làm.</span>
            </div>
            <?php if (currentRole() === AppRole::ADMIN || currentRole() === AppRole::MANAGER): ?>
            <button class="btn btn-success fw-bold" onclick="openAddModal()">
                <i class="fa-solid fa-user-plus me-1"></i> Thêm Tài Khoản Mới
            </button>
            <?php endif; ?>
        </div>

        <!-- Bảng hiển thị -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div style="max-height: 520px; overflow-y: auto; border-radius: 0 0 0.375rem 0.375rem;">
                    <table class="table table-hover table-striped mb-0 text-center align-middle">
                        <thead class="table-dark" style="position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th width="80">ID</th>
                                <th class="text-start">Họ tên & Email</th>
                                <th>Chức vụ</th>
                                <th>Trạng thái Nhân sự</th>
                                <th>Trạng thái Hệ thống</th>
                                <th width="150">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="accountsTableBody">
                            <tr><td colspan="6" class="text-center py-4">Đang tải dữ liệu...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm/Sửa Tài Khoản -->
<div class="modal fade" id="modalAccount" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAccount">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalAccountTitle">Thêm Tài Khoản Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ac_account_id" name="account_id" value="0">
                    
                    <!-- PHẦN THÔNG TIN CÁ NHÂN (Manager kiểm soát) -->
                    <div id="sectionPersonal">
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ac_full_name" required placeholder="Nguyễn Văn A">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="ac_email" required placeholder="nv@eldercoffee.com">
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold">Số điện thoại</label>
                                <input type="text" class="form-control" id="ac_phone" placeholder="090..." inputmode="numeric" pattern="[0-9]{10,}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold">Ngày sinh</label>
                                <input type="date" class="form-control" id="ac_birth_date">
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold">Giới tính</label>
                                <select class="form-select" id="ac_gender">
                                    <option value="nam">Nam</option>
                                    <option value="nữ">Nữ</option>
                                    <option value="khác">Khác</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-bold">Ngày vào làm</label>
                                <input type="date" class="form-control" id="ac_hire_date">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold">Địa chỉ</label>
                            <textarea class="form-control" id="ac_address" rows="2" placeholder="Số nhà, đường..."></textarea>
                        </div>
                    </div>

                    <!-- PHẦN PHÂN QUYỀN VÀ TÀI KHOẢN (Chức vụ quyết định quyền truy cập) -->
                    <div id="sectionAccount">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Chức vụ <span class="text-danger">*</span></label>
                            <select class="form-select" id="ac_position_id" required>
                                <!-- Rendered by JS -->
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mật khẩu <span id="pwdAsterisk" class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="ac_password" placeholder="Nhập mật khẩu">
                            <div class="form-text" id="ac_password_help">Để trống nếu không muốn đổi mật khẩu.</div>
                        </div>
                    </div>

                    <!-- PHẦN LỊCH SỬ CHỨC VỤ (Mới) -->
                    <div id="sectionHistory" style="display:none;" class="mt-4 border-top pt-3">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left me-2"></i>Lịch Sử Biến Động Chức Vụ</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Chức vụ</th>
                                        <th>Từ ngày</th>
                                        <th>Đến ngày</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody">
                                    <!-- Rendered by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveAccount">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu thông tin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xác Nhận Toggle Status -->
<div class="modal fade" id="modalToggleStatus" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header text-bg-warning">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Xác nhận</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="mb-0" id="toggleStatusMsg">Bạn có chắc chắn muốn thay đổi trạng thái?</p>
                <input type="hidden" id="toggle_account_id">
                <input type="hidden" id="toggle_target">
                <input type="hidden" id="toggle_new_status">
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-warning fw-bold" onclick="executeToggle()">Đồng ý thay đổi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thông báo kết quả -->
<div class="modal fade" id="modalResult" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" id="resultHeader">
                <h5 class="modal-title fw-bold" id="resultTitle">Thông báo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="fs-1 mb-3" id="resultIcon"></div>
                <h5 id="resultMsg" class="fw-bold mb-2"></h5>
                <p id="resultDetails" class="text-muted small mb-0"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Đã hiểu</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let accountsData = [];
let positionsData = [];
const currentUserId = <?= currentUserId() ?>;
const currentRole = '<?= currentRole() ?>';

var modalAccount = null;
var modalToggle = null;
var modalResult = null;
const apiUrl = 'api/admin/accounts.php';

// Format status badge
function renderHrStatus(status) {
    if (status === 'active') return `<span class="badge text-bg-success py-1"><i class="fa-solid fa-briefcase"></i> Đang làm</span>`;
    if (status === 'on_leave') return `<span class="badge text-bg-warning py-1"><i class="fa-solid fa-plane"></i> Nghỉ phép</span>`;
    return `<span class="badge text-bg-secondary py-1"><i class="fa-solid fa-door-open"></i> Đã nghỉ việc</span>`;
}

function renderSystemStatus(status) {
    if (status === 'active') return `<span class="badge text-bg-success py-1"><i class="fa-solid fa-check"></i> Hoạt động</span>`;
    if (status === 'pending') return `<span class="badge text-bg-info py-1 text-white"><i class="fa-solid fa-clock"></i> Chờ kích hoạt</span>`;
    if (status === 'locked') return `<span class="badge text-bg-danger py-1"><i class="fa-solid fa-lock"></i> Bị Khóa</span>`;
    return `<span class="badge text-bg-dark py-1"><i class="fa-solid fa-ban"></i> Vô hiệu hóa</span>`;
}

// Load danh sách
async function loadAccounts() {
    try {
        const res = await fetch(apiUrl);
        const data = await res.json();
        if (data.success) {
            accountsData = data.data;
            positionsData = data.positions;
            renderTable();
            renderPositionsOptions();
        } else {
            showResult(false, 'Lỗi tải dữ liệu', data.message);
        }
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

function renderTable() {
    const tbody = document.getElementById('accountsTableBody');
    tbody.innerHTML = '';
    
    if (accountsData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có tài khoản nào.</td></tr>`;
        return;
    }

    accountsData.forEach(acc => {
        const tr = document.createElement('tr');
        
        // Tìm tên chức vụ
        const pos = positionsData.find(p => parseInt(p.position_id) === parseInt(acc.position_id));
        const posName = pos ? pos.position_name : '<span class="text-muted">Chưa phân công</span>';
        
        // Nút Xem chi tiết
        const viewBtn = `<button class="btn btn-sm btn-outline-info" title="Xem chi tiết" onclick="viewAccountDetails(${acc.account_id})"><i class="fa-solid fa-eye"></i></button>`;

        // Nút Sửa
        let editBtn = '';
        if (parseInt(acc.account_id) !== currentUserId) {
            editBtn = `<button class="btn btn-sm btn-outline-primary" title="Sửa thông tin" onclick="openEditModal(${acc.account_id})"><i class="fa-solid fa-pen"></i></button>`;
        } else {
            editBtn = `<button class="btn btn-sm btn-secondary disabled" title="Không thể sửa tài khoản đang đăng nhập" disabled><i class="fa-solid fa-pen"></i></button>`;
        }
        
        // Xây dựng các nút thay đổi trạng thái
        let hrToggleBtn = '';
        let sysToggleBtn = '';

        if (currentRole === 'manager') {
            const nextHrStatus = (acc.hr_status === 'active') ? 'resigned' : 'active';
            const hrIcon = acc.hr_status === 'active' ? 'fa-toggle-on text-success' : 'fa-toggle-off text-muted';
            hrToggleBtn = `<button class="btn btn-sm btn-light border px-2" title="Đổi trạng thái HR" onclick="promptToggle(${acc.account_id}, 'hr', '${acc.hr_status}', '${acc.full_name}')"><i class="fa-solid ${hrIcon} fs-5 align-middle"></i></button>`;
        }
        
           // Nút Đổi chức vụ chỉ dành cho Quản lý nhân sự
        let posChangeBtn = '';
           if (currentRole === 'manager') {
             posChangeBtn = `<a href="user_page.php?chucvu&account_id=${acc.account_id}" class="btn btn-sm btn-outline-warning" title="Chuyển sang module Thay đổi chức vụ"><i class="fa-solid fa-user-tag"></i></a>`;
        }

        if (currentRole === 'admin') {
            if (parseInt(acc.account_id) === currentUserId) {
                sysToggleBtn = `<button class="btn btn-sm btn-light border px-2 disabled" title="Không thể tự khóa mình"><i class="fa-solid fa-shield text-secondary fs-5 align-middle opacity-50"></i></button>`;
            } else if (acc.role_id == 1) {
                sysToggleBtn = `<button class="btn btn-sm btn-light border px-2 disabled" title="Không thể khóa Admin khác"><i class="fa-solid fa-shield text-secondary fs-5 align-middle"></i></button>`;
            } else {
                if (acc.system_status === 'pending') {
                    sysToggleBtn = `<button class="btn btn-sm btn-primary fw-bold px-2" title="Duyệt tài khoản" onclick="promptToggle(${acc.account_id}, 'system', 'pending', '${acc.full_name}')">Duyệt</button>`;
                } else {
                    const sysIcon = acc.system_status === 'active' ? 'fa-toggle-on text-success' : 'fa-toggle-off text-danger';
                    sysToggleBtn = `<button class="btn btn-sm btn-light border px-2" title="Đổi trạng thái Hệ thống" onclick="promptToggle(${acc.account_id}, 'system', '${acc.system_status}', '${acc.full_name}')"><i class="fa-solid ${sysIcon} fs-5 align-middle"></i></button>`;
                }
            }
        }

        tr.innerHTML = `
            <td class="fw-bold text-muted">#${acc.account_id}</td>
            <td class="text-start">
                <div class="fw-bold text-dark">${acc.full_name}</div>
                <small class="text-muted">${acc.email}</small>
            </td>
            <td><span class="badge text-bg-info text-white">${posName}</span></td>
            <td>${renderHrStatus(acc.hr_status)}</td>
            <td>${renderSystemStatus(acc.system_status)}</td>
            <td>
                <div class="d-flex align-items-center justify-content-center gap-1">
                    ${viewBtn} ${editBtn} ${posChangeBtn} ${hrToggleBtn} ${sysToggleBtn}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}



function renderPositionsOptions() {
    const sel = document.getElementById('ac_position_id');
    sel.innerHTML = '<option value="">-- Chọn chức vụ --</option>';
    positionsData.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.position_id;
        opt.textContent = p.position_name;
        sel.appendChild(opt);
    });
}

// Logic form
function setModalReadOnly(isReadOnly) {
    const inputs = document.querySelectorAll('#modalAccount input, #modalAccount select, #modalAccount textarea');
    inputs.forEach(el => {
        if (el.id !== 'ac_account_id') el.disabled = isReadOnly;
    });
    document.getElementById('btnSaveAccount').style.display = isReadOnly ? 'none' : 'block';
}

function resetForm() {
    document.getElementById('formAccount').reset();
    document.getElementById('ac_account_id').value = 0; // Đảm bảo là 0 cho add mới
    document.getElementById('ac_position_id').disabled = false; // Mở lại khi add mới
    setModalReadOnly(false);
}

function openAddModal() {
    resetForm();
    document.getElementById('ac_account_id').value = 0;
    
    document.getElementById('modalAccountTitle').innerText = 'Thêm Tài Khoản Mới';

    // Hiện cả 2 phần khi thêm mới
    document.getElementById('sectionPersonal').style.display = 'block';
    document.getElementById('sectionAccount').style.display = 'block';

    // Yêu cầu nhập mật khẩu khi tạo mới
    document.getElementById('ac_password').required = true;
    document.getElementById('pwdAsterisk').style.display = 'inline';
    document.getElementById('ac_password_help').style.display = 'none';
    document.getElementById('ac_email').disabled = false;
    document.getElementById('ac_position_id').disabled = false;

    if (modalAccount) modalAccount.show();
}

function openEditModal(id) {
    const acc = accountsData.find(x => parseInt(x.account_id) === id);
    if (!acc) return;

    setModalReadOnly(false);
    document.getElementById('formAccount').reset();
    document.getElementById('ac_account_id').value = acc.account_id;
    document.getElementById('ac_full_name').value = acc.full_name;
    document.getElementById('ac_email').value = acc.email;
    document.getElementById('ac_phone').value = acc.phone || '';
    document.getElementById('ac_birth_date').value = acc.birth_date || '';
    document.getElementById('ac_gender').value = acc.gender || 'nam';
    document.getElementById('ac_hire_date').value = acc.hire_date || '';
    document.getElementById('ac_address').value = acc.address || '';
    
    const posSel = document.getElementById('ac_position_id');
    posSel.value = acc.position_id || '';
    posSel.disabled = true; // Chặn sửa position tại đây, phải dùng module chucvu
    
    document.getElementById('modalAccountTitle').innerText = 'Sửa Thông Tin Hồ Sơ: ' + acc.full_name;
    
    // Mật khẩu không bắt buộc khi sửa
    document.getElementById('ac_password').required = false;
    document.getElementById('pwdAsterisk').style.display = 'none';
    document.getElementById('ac_password_help').style.display = 'block';
    
    // Logic tách biệt: Manager sửa cá nhân, Admin sửa Role
    if (currentRole === 'admin') {
        document.getElementById('sectionPersonal').style.display = 'none';
        document.getElementById('sectionAccount').style.display = 'block';
    } else {
        document.getElementById('sectionPersonal').style.display = 'block';
        document.getElementById('sectionAccount').style.display = 'none';
    }

    // Email không cho đổi khi sửa
    document.getElementById('ac_email').disabled = true;

    if (modalAccount) modalAccount.show();
}

function viewAccountDetails(id) {
    const acc = accountsData.find(x => parseInt(x.account_id) === id);
    if (!acc) return;

    document.getElementById('formAccount').reset();
    document.getElementById('ac_full_name').value = acc.full_name;
    document.getElementById('ac_email').value = acc.email;
    document.getElementById('ac_phone').value = acc.phone || '';
    document.getElementById('ac_birth_date').value = acc.birth_date || '';
    document.getElementById('ac_gender').value = acc.gender || 'nam';
    document.getElementById('ac_hire_date').value = acc.hire_date || '';
    document.getElementById('ac_address').value = acc.address || '';
    document.getElementById('ac_position_id').value = acc.position_id || '';
    
    document.getElementById('modalAccountTitle').innerText = 'Chi tiết nhân sự: ' + acc.full_name;
    
    // Luôn hiện cả 2 phần để xem đầy đủ
    document.getElementById('sectionPersonal').style.display = 'block';
    document.getElementById('sectionAccount').style.display = 'block';
    document.getElementById('pwdAsterisk').style.display = 'none';
    document.getElementById('ac_password_help').style.display = 'none';
    document.getElementById('ac_password').parentElement.style.display = 'none'; // Ẩn hẳn ô password khi xem

    setModalReadOnly(true);
    if (modalAccount) modalAccount.show();

    // Fetch history
    fetchHistory(id);

    // Khôi phục lại hiển thị ô password khi đóng modal (sẽ reset ở các hàm open khác)
    const cleanup = () => {
        document.getElementById('ac_password').parentElement.style.display = 'block';
        document.getElementById('sectionHistory').style.display = 'none';
        document.getElementById('modalAccount').removeEventListener('hidden.bs.modal', cleanup);
    };
    document.getElementById('modalAccount').addEventListener('hidden.bs.modal', cleanup);
}

async function fetchHistory(id) {
    const historySection = document.getElementById('sectionHistory');
    const tbody = document.getElementById('historyTableBody');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center italic text-muted">Đang tải lịch sử...</td></tr>';
    historySection.style.display = 'block';

    try {
        const res = await fetch(`api/admin/history.php?account_id=${id}`);
        const data = await res.json();
        if (data.success && data.data) {
            tbody.innerHTML = '';
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Chưa có lịch sử biến động.</td></tr>';
            } else {
                data.data.forEach(h => {
                    const tr = document.createElement('tr');
                    const endDateStr = h.end_date ? formatDate(h.end_date) : '<span class="badge text-bg-success">...</span>';
                    tr.innerHTML = `
                        <td><b>${h.position_name}</b><br><small class="text-muted">${h.reason || ''}</small></td>
                        <td>${formatDate(h.start_date)}</td>
                        <td>${endDateStr}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Lỗi tải lịch sử.</td></tr>';
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('vi-VN');
}

function isValidPhone(phone) {
    return /^\d{10,}$/.test(phone);
}

function isValidEmail(email) {
    if (/\s/.test(email)) {
        return false;
    }
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

async function saveAccount(e) {
    e.preventDefault();
    
    const btn = document.getElementById('btnSaveAccount');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';

    const id = parseInt(document.getElementById('ac_account_id').value);
    const emailInput = document.getElementById('ac_email').value.trim();
    const phoneInput = document.getElementById('ac_phone').value.trim();

    if (!isValidEmail(emailInput)) {
        showResult(false, 'Thất bại', 'Email không đúng định dạng');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu thông tin';
        return;
    }

    if (phoneInput !== '' && !isValidPhone(phoneInput)) {
        showResult(false, 'Thất bại', 'Số điện thoại không đúng định dạng');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu thông tin';
        return;
    }

    const payload = {
        account_id: id,
        full_name: document.getElementById('ac_full_name').value,
        email: emailInput,
        password: document.getElementById('ac_password').value,
        phone: phoneInput,
        birth_date: document.getElementById('ac_birth_date').value,
        gender: document.getElementById('ac_gender').value,
        hire_date: document.getElementById('ac_hire_date').value,
        address: document.getElementById('ac_address').value,
        position_id: document.getElementById('ac_position_id').value
    };

    const method = id > 0 ? 'PUT' : 'POST';

    try {
        const res = await fetch(apiUrl, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        
        if (result.success) {
            if (modalAccount) modalAccount.hide();
            showResult(true, 'Thành công', id > 0 ? 'Cập nhật tài khoản thành công.' : 'Tạo tài khoản mới thành công.');
            await loadAccounts(); // Tải lại bảng
        } else {
            showResult(false, 'Thất bại', result.message || result.error);
        }
    } catch (err) {
        showResult(false, 'Lỗi kết nối', 'Không thể kết nối tới máy chủ.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu thông tin';
    }
}

// Logic đổi trạng thái
function promptToggle(id, target, currentStatus, name, role_name) {
    document.getElementById('toggle_account_id').value = id;
    document.getElementById('toggle_target').value = target;

    let nextStatus = '';
    let msg = '';
    if (target === 'hr') {
        nextStatus = currentStatus === 'active' ? 'resigned' : 'active';
        msg = `Đổi trạng thái nhân sự của <b>${name}</b> thành <b>${nextStatus === 'resigned' ? 'Nghỉ việc' : 'Đang làm'}</b>?`;
        
        // Tìm thông tin role của tài khoản này
        const acc = accountsData.find(x => x.account_id == id);
        if (nextStatus === 'resigned' && acc && acc.role_id != 1) {
            msg += `<br><small class="text-danger mt-2 d-block"><i class="fa-solid fa-circle-exclamation"></i> Tài khoản nội bộ này sẽ bị khóa quyền truy cập hệ thống tự động.</small>`;
        }
    } else {
        if (currentStatus === 'pending') {
            nextStatus = 'active';
            msg = `Kích hoạt và cho phép tài khoản <b>${name}</b> truy cập hệ thống?`;
        } else {
            nextStatus = currentStatus === 'active' ? 'locked' : 'active';
            msg = `Đổi trạng thái Hệ Thống của <b>${name}</b> thành <b>${nextStatus === 'locked' ? 'Khóa' : 'Kích hoạt'}</b>?`;
        }
    }

    document.getElementById('toggleStatusMsg').innerHTML = msg;
    document.getElementById('toggle_new_status').value = nextStatus;
    if (modalToggle) modalToggle.show();
}

async function executeToggle() {
    const id = document.getElementById('toggle_account_id').value;
    const target = document.getElementById('toggle_target').value;
    const nextStatus = document.getElementById('toggle_new_status').value;

    try {
        const res = await fetch(apiUrl, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ account_id: id, target: target, status: nextStatus })
        });
        const result = await res.json();
        
        if (result.success) {
            if (modalToggle) modalToggle.hide();
            showResult(true, 'Thành công', 'Đã thay đổi trạng thái thành công.');
            await loadAccounts();
        } else {
            showResult(false, 'Thất bại', result.message || result.error);
        }
    } catch (err) {
        showResult(false, 'Lỗi kết nối', 'Không thể kết nối tới máy chủ.');
    }
}

// Khởi tạo
document.addEventListener('DOMContentLoaded', () => {
    if (typeof bootstrap !== 'undefined') {
        modalAccount = new bootstrap.Modal(document.getElementById('modalAccount'));
        modalToggle = new bootstrap.Modal(document.getElementById('modalToggleStatus'));
        modalResult = new bootstrap.Modal(document.getElementById('modalResult'));
    }
    
    // Gán event listener cho form
    const formAccount = document.getElementById('formAccount');
    if (formAccount) {
        formAccount.addEventListener('submit', function(e) {
            e.preventDefault();
            saveAccount(e);
        });
    }
    
    loadAccounts();
});

function showResult(success, title, msg) {
    const header = document.getElementById('resultHeader');
    const titleEl = document.getElementById('resultTitle');
    const msgEl = document.getElementById('resultMsg');
    const iconEl = document.getElementById('resultIcon');

    if (success) {
        header.className = "modal-header text-bg-success border-0";
        titleEl.innerText = title;
        msgEl.innerText = msg;
        iconEl.innerHTML = '<i class="fa-solid fa-circle-check text-success"></i>';
    } else {
        header.className = "modal-header text-bg-danger border-0";
        titleEl.innerText = title;
        msgEl.innerText = msg;
        iconEl.innerHTML = '<i class="fa-solid fa-circle-xmark text-danger"></i>';
    }

    if (modalResult) modalResult.show();
}
</script>
</div>
</div>
</div>
</div>