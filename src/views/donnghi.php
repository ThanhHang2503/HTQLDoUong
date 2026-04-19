<?php
// Module đơn nghỉ phép - nhân viên gửi đơn, quản lý duyệt
global $conn;
$uid  = currentUserId();
$role = currentRole();
$isManager = $role === AppRole::MANAGER || $role === AppRole::ADMIN;

// Tháng hiện tại (dùng cho filter danh sách)
$cur_month = (int)date('n');
$cur_year  = (int)date('Y');

$msg_success = '';
$msg_error   = '';

// Xử lý gửi đơn (tất cả các vai trò)
if (isset($_POST['submit_leave'])) {
    $from_date  = trim($_POST['from_date'] ?? '');
    $to_date    = trim($_POST['to_date'] ?? '');
    $today      = date('Y-m-d');
    $tomorrow   = date('Y-m-d', strtotime('+1 day'));
    $leave_type = $_POST['leave_type'] ?? 'phép';
    $reason     = trim(mysqli_real_escape_string($conn, $_POST['reason'] ?? ''));
    $valid_types = ['phép','bệnh','thai sản','không lương','khác'];
    if (!in_array($leave_type, $valid_types)) $leave_type = 'phép';

    $from_valid = DateTime::createFromFormat('Y-m-d', $from_date) && DateTime::createFromFormat('Y-m-d', $from_date)->format('Y-m-d') === $from_date;
    $to_valid = DateTime::createFromFormat('Y-m-d', $to_date) && DateTime::createFromFormat('Y-m-d', $to_date)->format('Y-m-d') === $to_date;

    if (!$from_valid || !$to_valid) {
        $msg_error = 'Ngày nghỉ không hợp lệ. Vui lòng chọn đúng định dạng ngày.';
    } elseif ($from_date < $tomorrow) {
        $msg_error = 'Ngày bắt đầu nghỉ phép phải từ ngày mai trở đi.';
    } elseif ($to_date < $from_date) {
        $msg_error = 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.';
    } elseif ($reason === '') {
        $msg_error = 'Lý do không được để trống.';
    } else {
        $lt = mysqli_real_escape_string($conn, $leave_type);
        mysqli_query($conn, "INSERT INTO leave_requests (account_id, from_date, to_date, leave_type, reason)
                             VALUES ($uid, '$from_date', '$to_date', '$lt', '$reason')");
        $msg_success = 'Đã gửi đơn nghỉ phép. Vui lòng chờ duyệt.';
    }
}

// Hủy đơn của bản thân
if (isset($_POST['cancel_leave'])) {
    $lid = (int)($_POST['leave_id'] ?? 0);
    $chk = mysqli_query($conn, "SELECT leave_request_id FROM leave_requests WHERE leave_request_id=$lid AND account_id=$uid AND status='chờ duyệt'");
    if (mysqli_num_rows($chk) > 0) {
        mysqli_query($conn, "UPDATE leave_requests SET status='hủy' WHERE leave_request_id=$lid");
        $msg_success = 'Đã hủy đơn.';
    } else {
        $msg_error = 'Không thể hủy đơn này.';
    }
}

// Quản lý duyệt/từ chối
if ($isManager && isset($_POST['approve_leave'])) {
    $lid   = (int)($_POST['leave_id'] ?? 0);
    $action = $_POST['approve_action'] ?? '';
    $notes = trim(mysqli_real_escape_string($conn, $_POST['notes'] ?? ''));
    $new_status = $action === 'approve' ? 'chấp thuận' : 'từ chối';
    mysqli_query($conn, "UPDATE leave_requests SET status='$new_status', approved_by=$uid,
                          approved_at=NOW(), notes='$notes'
                          WHERE leave_request_id=$lid");
    $msg_success = 'Đã ' . ($action === 'approve' ? 'duyệt' : 'từ chối') . ' đơn nghỉ phép.';
}

// Lấy danh sách đơn
if ($isManager) {
    $filter_status = $_GET['filter_status'] ?? '';
    // Filter theo tháng hiện tại
    $where = "WHERE MONTH(lr.from_date) = $cur_month AND YEAR(lr.from_date) = $cur_year";
    if ($filter_status) {
        $where .= " AND lr.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
    }
    $list_sql = "SELECT lr.*, a.full_name, p.position_name,
                        ab.full_name AS approved_by_name
                 FROM leave_requests lr
                 JOIN accounts a ON a.account_id = lr.account_id AND a.role_id != 1
                 LEFT JOIN positions p ON p.position_id = a.position_id
                 LEFT JOIN accounts ab ON ab.account_id = lr.approved_by
                 $where
                 ORDER BY lr.status = 'chờ duyệt' DESC, lr.created_at DESC";
} else {
    $list_sql = "SELECT lr.*, a.full_name, p.position_name,
                        ab.full_name AS approved_by_name
                 FROM leave_requests lr
                 JOIN accounts a ON a.account_id = lr.account_id
                 LEFT JOIN positions p ON p.position_id = a.position_id
                 LEFT JOIN accounts ab ON ab.account_id = lr.approved_by
                 WHERE lr.account_id = $uid
                 ORDER BY lr.created_at DESC";
}
$list_result = mysqli_query($conn, $list_sql);
$leaves = $list_result ? mysqli_fetch_all($list_result, MYSQLI_ASSOC) : [];

// Đơn chờ duyệt (cho manager)
$pending_count = 0;
if ($isManager) {
    $pc = mysqli_query($conn, "SELECT COUNT(*) FROM leave_requests WHERE status='chờ duyệt'");
    $pending_count = $pc ? (int)mysqli_fetch_row($pc)[0] : 0;
}

$status_class = ['chờ duyệt'=>'warning','chấp thuận'=>'success','từ chối'=>'danger','hủy'=>'secondary'];
?>

<div class="dash_board px-2">
    <h1 class="head-name">ĐƠN NGHỈ PHÉP</h1>
    <div class="head-line"></div>

    <?php if ($msg_success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg_success) ?></div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($msg_error) ?></div>
    <?php endif; ?>

    <?php if ($isManager && $pending_count > 0): ?>
        <div class="alert alert-warning fw-bold">
            <i class="fa-solid fa-bell me-2"></i>Có <b><?= $pending_count ?></b> đơn nghỉ phép đang chờ duyệt!
        </div>
    <?php endif; ?>

    <div class="container-fluid">
        <div class="row g-3 mt-1">
            <!-- Form gửi đơn -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold bg-primary text-white">
                        <i class="fa-solid fa-paper-plane me-2"></i>Gửi Đơn Nghỉ
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Loại nghỉ</label>
                                <select class="form-select" name="leave_type">
                                    <option value="phép">Nghỉ phép năm</option>
                                    <option value="bệnh">Nghỉ ốm / bệnh</option>
                                    <option value="thai sản">Nghỉ thai sản</option>
                                    <option value="không lương">Nghỉ không lương</option>
                                    <option value="khác">Khác</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Từ ngày</label>
                                <input type="date" class="form-control" name="from_date"
                                       id="fromDateInput"
                                       value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Đến ngày</label>
                                <input type="date" class="form-control" name="to_date"
                                       id="toDateInput"
                                       value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Lý do</label>
                                <textarea class="form-control" name="reason" rows="3"
                                          placeholder="Nêu rõ lý do nghỉ..." required></textarea>
                            </div>
                            <button type="submit" name="submit_leave" class="btn btn-primary w-100 fw-bold">
                                <i class="fa-solid fa-paper-plane me-1"></i>Gửi đơn
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danh sách đơn -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-list me-2"></i><?= $isManager ? 'Tất cả đơn nghỉ phép' : 'Đơn của tôi' ?></span>
                        <?php if ($isManager): ?>
                        <form class="d-flex gap-2" method="GET" action="user_page.php">
                            <input type="hidden" name="donnghi" value="1">
                            <select class="form-select form-select-sm" name="filter_status" style="width:auto">
                                <option value="">Tất cả trạng thái</option>
                                <option value="chờ duyệt" <?= ($_GET['filter_status']??'')=='chờ duyệt'?'selected':'' ?>>Chờ duyệt</option>
                                <option value="chấp thuận" <?= ($_GET['filter_status']??'')=='chấp thuận'?'selected':'' ?>>Đã duyệt</option>
                                <option value="từ chối" <?= ($_GET['filter_status']??'')=='từ chối'?'selected':'' ?>>Từ chối</option>
                            </select>
                            <button class="btn btn-sm btn-outline-primary" type="submit">Lọc</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($leaves)): ?>
                            <p class="p-3 mb-0 text-muted">Không có đơn nào.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered mb-0 text-center table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <?php if ($isManager): ?><th>Nhân viên</th><?php endif; ?>
                                    <th>Loại nghỉ</th>
                                    <th>Từ ngày</th>
                                    <th>Đến ngày</th>
                                    <th>Số ngày</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaves as $lv):
                                    $days = (int)((strtotime($lv['to_date']) - strtotime($lv['from_date'])) / 86400) + 1;
                                    $sc = $status_class[$lv['status']] ?? 'secondary';
                                ?>
                                <tr>
                                    <?php if ($isManager): ?>
                                    <td class="text-start">
                                        <b><?= htmlspecialchars($lv['full_name']) ?></b><br>
                                        <small class="text-muted"><?= htmlspecialchars($lv['position_name'] ?? '') ?></small>
                                    </td>
                                    <?php endif; ?>
                                    <td><span class="badge text-bg-info"><?= htmlspecialchars($lv['leave_type']) ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($lv['from_date'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($lv['to_date'])) ?></td>
                                    <td><b><?= $days ?></b> ngày</td>
                                    <td><span class="badge text-bg-<?= $sc ?>"><?= $lv['status'] ?></span></td>
                                    <td>
                                        <!-- Tooltip lý do -->
                                        <span class="d-inline-block" tabindex="0" data-bs-toggle="popover"
                                              data-bs-trigger="hover focus" title="Lý do"
                                              data-bs-content="<?= htmlspecialchars($lv['reason']) ?>">
                                            <i class="fa-solid fa-circle-info text-info btn"></i>
                                        </span>

                                        <?php if ($lv['status'] === 'chờ duyệt' && !$isManager && $lv['account_id'] == $uid): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hủy đơn này?')">
                                            <input type="hidden" name="leave_id" value="<?= $lv['leave_request_id'] ?>">
                                            <button type="submit" name="cancel_leave" class="btn btn-sm btn-outline-danger">Hủy</button>
                                        </form>
                                        <?php endif; ?>

                                        <?php if ($isManager && $lv['status'] === 'chờ duyệt'): ?>
                                        <button class="btn btn-sm btn-success" onclick="showApproveModal(<?= $lv['leave_request_id'] ?>, 'approve')">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="showApproveModal(<?= $lv['leave_request_id'] ?>, 'reject')">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal duyệt đơn (cho manager) -->
<?php if ($isManager): ?>
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalTitle">Duyệt đơn nghỉ phép</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="leave_id" id="modalLeaveId">
                    <input type="hidden" name="approve_action" id="modalApproveAction">
                    <div class="mb-3">
                        <label class="form-label">Ghi chú (tùy chọn)</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Ghi chú cho nhân viên..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="approve_leave" class="btn btn-primary" id="approveBtn">Xác nhận</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fromInput = document.getElementById('fromDateInput');
    const toInput = document.getElementById('toDateInput');
    if (!fromInput || !toInput) {
        return;
    }

    const syncToDateMin = function () {
        const fromVal = fromInput.value;
        if (!fromVal) {
            return;
        }
        toInput.min = fromVal;
        if (toInput.value < fromVal) {
            toInput.value = fromVal;
        }
    };

    syncToDateMin();
    fromInput.addEventListener('change', syncToDateMin);
});

function showApproveModal(id, action) {
    document.getElementById('modalLeaveId').value = id;
    document.getElementById('modalApproveAction').value = action;
    const title = action === 'approve' ? 'Duyệt đơn nghỉ phép' : 'Từ chối đơn nghỉ phép';
    document.getElementById('approveModalTitle').textContent = title;
    document.getElementById('approveBtn').className = action === 'approve' ? 'btn btn-success' : 'btn btn-danger';
    document.getElementById('approveBtn').textContent = action === 'approve' ? 'Duyệt' : 'Từ chối';
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
// Popover init
document.addEventListener('DOMContentLoaded', function() {
    var pops = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    pops.forEach(function(el) { new bootstrap.Popover(el); });
});
</script>
<?php endif; ?>
</div>
</div>
</div>
</div>
