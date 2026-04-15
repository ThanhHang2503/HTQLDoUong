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
<div class="modal fade" id="modalAccount" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAccount" onsubmit="saveAccount(event)">
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
                                <input type="text" class="form-control" id="ac_phone" placeholder="090...">
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
<div class="modal fade" id="modalToggleStatus" tabindex="-1" aria-hidden="true">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let accountsData = [];
let positionsData = [];
const currentUserId = <?= currentUserId() ?>;
const currentRole = '<?= currentRole() ?>';

const modalAccount = new bootstrap.Modal(document.getElementById('modalAccount'));
const modalToggle = new bootstrap.Modal(document.getElementById('modalToggleStatus'));
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
            alert('Lỗi tải dữ liệu: ' + data.message);
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
                    ${viewBtn} ${editBtn} ${hrToggleBtn} ${sysToggleBtn}
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

function openAddModal() {
    document.getElementById('formAccount').reset();
    document.getElementById('ac_account_id').value = 0;
    
    setModalReadOnly(false);
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

    modalAccount.show();
}

function openEditModal(id) {
    const acc = accountsData.find(x => parseInt(x.account_id) === id);
    if (!acc) return;

    setModalReadOnly(false);
    document.getElementById('formAccount').reset();
    document.getElementById('ac_full_name').value = acc.full_name;
    document.getElementById('ac_email').value = acc.email;
    document.getElementById('ac_phone').value = acc.phone || '';
    document.getElementById('ac_birth_date').value = acc.birth_date || '';
    document.getElementById('ac_gender').value = acc.gender || 'nam';
    document.getElementById('ac_hire_date').value = acc.hire_date || '';
    document.getElementById('ac_address').value = acc.address || '';
    document.getElementById('ac_position_id').value = acc.position_id || '';
    
    document.getElementById('modalAccountTitle').innerText = (currentRole === 'admin') ? 'Quản lý phân quyền: ' + acc.full_name : 'Cập Nhật Thông Tin: ' + acc.full_name;
    
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
    document.getElementById('ac_position_id').disabled = false;

    modalAccount.show();
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
    modalAccount.show();

    // Khôi phục lại hiển thị ô password khi đóng modal (sẽ reset ở các hàm open khác)
    const cleanup = () => {
        document.getElementById('ac_password').parentElement.style.display = 'block';
        document.getElementById('modalAccount').removeEventListener('hidden.bs.modal', cleanup);
    };
    document.getElementById('modalAccount').addEventListener('hidden.bs.modal', cleanup);
}

async function saveAccount(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveAccount');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';

    const id = parseInt(document.getElementById('ac_account_id').value);
    const payload = {
        account_id: id,
        full_name: document.getElementById('ac_full_name').value,
        email: document.getElementById('ac_email').value,
        password: document.getElementById('ac_password').value,
        phone: document.getElementById('ac_phone').value,
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
            modalAccount.hide();
            await loadAccounts(); // Tải lại bảng
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (err) {
        alert('Lỗi kết nối mạng: ' + err);
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
    modalToggle.show();
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
            modalToggle.hide();
            await loadAccounts();
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (err) {
        alert('Lỗi kết nối mạng: ' + err);
    }
}

// Khởi tạo
document.addEventListener('DOMContentLoaded', () => {
    loadAccounts();
});
</script>
</div>
</div>
</div>
</div>