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
            <button class="btn btn-success fw-bold" onclick="openAddModal()">
                <i class="fa-solid fa-user-plus me-1"></i> Thêm Tài Khoản Mới
            </button>
        </div>

        <!-- Bảng hiển thị -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped mb-0 text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th width="80">ID</th>
                            <th class="text-start">Họ tên nhân viên</th>
                            <th class="text-start">Email đăng nhập</th>
                            <th>Vai trò (Role)</th>
                            <th>Trạng thái</th>
                            <th width="180">Thao tác</th>
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
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ac_full_name" required placeholder="Nguyễn Văn A">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="ac_email" required placeholder="nv.a@eldercoffee.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Phân quyền (Role) <span class="text-danger">*</span></label>
                        <select class="form-select" id="ac_role_id" required>
                            <!-- Options rendered by JS -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mật khẩu <span id="pwdAsterisk" class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="ac_password" placeholder="Nhập mật khẩu">
                        <div class="form-text" id="ac_password_help">Để trống nếu không muốn đổi mật khẩu.</div>
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
let rolesData = [];
const currentUserId = <?= currentUserId() ?>;

const modalAccount = new bootstrap.Modal(document.getElementById('modalAccount'));
const modalToggle = new bootstrap.Modal(document.getElementById('modalToggleStatus'));
const apiUrl = 'api/admin/accounts.php';

// Format status badge
function renderStatus(status) {
    if (status === 'active') {
        return `<span class="badge text-bg-success py-2 w-100"><i class="fa-solid fa-user-check me-1"></i> Đang làm</span>`;
    }
    return `<span class="badge text-bg-secondary py-2 w-100"><i class="fa-solid fa-user-xmark me-1"></i> Nghỉ làm</span>`;
}

// Load danh sách
async function loadAccounts() {
    try {
        const res = await fetch(apiUrl);
        const data = await res.json();
        if (data.success) {
            accountsData = data.data;
            rolesData = data.roles;
            renderTable();
            renderRolesOptions();
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
        
        // Nút Sửa
        let editBtn = '';
        if (parseInt(acc.account_id) !== currentUserId) {
            editBtn = `<button class="btn btn-sm btn-outline-primary me-1" title="Sửa thông tin" onclick="openEditModal(${acc.account_id})"><i class="fa-solid fa-pen"></i></button>`;
        } else {
            editBtn = `<button class="btn btn-sm btn-secondary me-1 disabled" title="Không thể sửa tài khoản đang đăng nhập" disabled><i class="fa-solid fa-pen"></i></button>`;
        }
        
        // Nút Toggle Status
        let toggleBtn = '';
        if (parseInt(acc.account_id) !== currentUserId) {
            const nextStatus = (acc.status === 'active') ? 'inactive' : 'active';
            const icon = acc.status === 'active' ? 'fa-toggle-on text-success' : 'fa-toggle-off text-secondary';
            const title = acc.status === 'active' ? 'Đánh dấu Nghỉ làm' : 'Đánh dấu Đang làm';
            toggleBtn = `<button class="btn btn-sm btn-light border" title="${title}" onclick="promptToggle(${acc.account_id}, '${acc.status}', '${acc.full_name}')"><i class="fa-solid ${icon} fs-5 align-middle"></i></button>`;
        } else {
            toggleBtn = `<button class="btn btn-sm btn-light border disabled" title="Không thể tự khóa mình"><i class="fa-solid fa-toggle-on text-success fs-5 align-middle opacity-50"></i></button>`;
        }

        const roleLabelUI = `<span class="fw-medium">${acc.role_name}</span>`;

        tr.innerHTML = `
            <td class="fw-bold text-muted">#${acc.account_id}</td>
            <td class="text-start fw-bold text-dark">${acc.full_name}</td>
            <td class="text-start">${acc.email}</td>
            <td>${roleLabelUI}</td>
            <td style="width: 140px;">${renderStatus(acc.status)}</td>
            <td>${editBtn} ${toggleBtn}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderRolesOptions() {
    const sel = document.getElementById('ac_role_id');
    sel.innerHTML = '<option value="">-- Chọn phân quyền --</option>';
    rolesData.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.id;
        opt.textContent = r.name;
        sel.appendChild(opt);
    });
}

// Logic form
function openAddModal() {
    document.getElementById('formAccount').reset();
    document.getElementById('ac_account_id').value = 0;
    document.getElementById('modalAccountTitle').innerText = 'Thêm Tài Khoản Mới';
    
    // Yêu cầu nhập mật khẩu khi tạo mới
    document.getElementById('ac_password').required = true;
    document.getElementById('pwdAsterisk').style.display = 'inline';
    document.getElementById('ac_password_help').style.display = 'none';

    modalAccount.show();
}

function openEditModal(id) {
    const acc = accountsData.find(x => parseInt(x.account_id) === id);
    if (!acc) return;

    document.getElementById('formAccount').reset();
    document.getElementById('ac_account_id').value = acc.account_id;
    document.getElementById('ac_full_name').value = acc.full_name;
    document.getElementById('ac_email').value = acc.email;
    document.getElementById('ac_role_id').value = acc.role_id;
    
    document.getElementById('modalAccountTitle').innerText = 'Sửa Thông Tin Tài Khoản: ' + acc.full_name;
    
    // Mật khẩu không bắt buộc khi sửa
    document.getElementById('ac_password').required = false;
    document.getElementById('pwdAsterisk').style.display = 'none';
    document.getElementById('ac_password_help').style.display = 'block';

    modalAccount.show();
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
        role_id: document.getElementById('ac_role_id').value,
        password: document.getElementById('ac_password').value
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
function promptToggle(id, currentStatus, name) {
    const nextText = currentStatus === 'active' ? 'Ngừng hoạt động' : 'Kích hoạt lại làm việc';
    document.getElementById('toggleStatusMsg').innerHTML = `Đổi trạng thái tài khoản <b>${name}</b> thành <b>${nextText}</b>?`;
    document.getElementById('toggle_account_id').value = id;
    document.getElementById('toggle_new_status').value = currentStatus === 'active' ? 'inactive' : 'active';
    modalToggle.show();
}

async function executeToggle() {
    const id = document.getElementById('toggle_account_id').value;
    const nextStatus = document.getElementById('toggle_new_status').value;

    try {
        const res = await fetch(apiUrl, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ account_id: id, status: nextStatus })
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