<?php
// Xử lý đơn nghỉ việc
global $conn;
$uid  = currentUserId();
$role = currentRole();
$isManager = $role === AppRole::MANAGER || $role === AppRole::ADMIN;

$msg_success = '';
$msg_error   = '';

// Gửi đơn nghỉ việc
if (isset($_POST['submit_resignation'])) {
    $effective_date = trim($_POST['effective_date'] ?? '');
    $reason = trim(mysqli_real_escape_string($conn, $_POST['reason'] ?? ''));
    $today = date('Y-m-d');

    if ($effective_date < $today) {
        $msg_error = 'Ngày nghỉ hiệu lực không được ở quá khứ.';
    } elseif ($reason === '') {
        $msg_error = 'Lý do không được để trống.';
    } else {
        // Kiểm tra đã có đơn chờ duyệt chưa
        $chk = mysqli_query($conn, "SELECT resignation_request_id FROM resignation_requests
                                    WHERE account_id=$uid AND status='chờ duyệt'");
        if (mysqli_num_rows($chk) > 0) {
            $msg_error = 'Bạn đã có đơn nghỉ việc đang chờ duyệt.';
        } else {
            mysqli_query($conn, "INSERT INTO resignation_requests (account_id, notice_date, effective_date, reason)
                                 VALUES ($uid, '$today', '$effective_date', '$reason')");
            $msg_success = 'Đã gửi đơn nghỉ việc. Vui lòng chờ quản lý xem xét.';
        }
    }
}

// Hủy đơn
if (isset($_POST['cancel_resignation'])) {
    $rid = (int)($_POST['resignation_id'] ?? 0);
    $chk = mysqli_query($conn, "SELECT resignation_request_id FROM resignation_requests
                                WHERE resignation_request_id=$rid AND account_id=$uid AND status='chờ duyệt'");
    if (mysqli_num_rows($chk) > 0) {
        mysqli_query($conn, "UPDATE resignation_requests SET status='hủy' WHERE resignation_request_id=$rid");
        $msg_success = 'Đã hủy đơn nghỉ việc.';
    }
}

// Manager duyệt/từ chối
if ($isManager && isset($_POST['approve_resignation'])) {
    $rid = (int)($_POST['resignation_id'] ?? 0);
    $action = $_POST['approve_action'] ?? '';
    $notes = trim(mysqli_real_escape_string($conn, $_POST['notes'] ?? ''));
    $new_status = $action === 'approve' ? 'chấp thuận' : 'từ chối';
    mysqli_query($conn, "UPDATE resignation_requests SET status='$new_status', approved_by=$uid,
                          approved_at=NOW(), notes='$notes'
                          WHERE resignation_request_id=$rid");
    // Nếu chấp thuận, khóa tài khoản và chuyển trạng thái nhân sự ngay lập tức
    if ($action === 'approve') {
        $acc_query = mysqli_query($conn, "SELECT account_id FROM resignation_requests WHERE resignation_request_id=$rid");
        if ($acc_row = mysqli_fetch_assoc($acc_query)) {
            $target_acc = (int)$acc_row['account_id'];
            // Cập nhật trạng thái nhân sự thành 'resigned' và khóa hệ thống
            mysqli_query($conn, "UPDATE accounts 
                                 SET hr_status = 'resigned', system_status = 'locked' 
                                 WHERE account_id = $target_acc");
        }
    }
    $msg_success = 'Đã ' . ($action === 'approve' ? 'chấp thuận' : 'từ chối') . ' đơn nghỉ việc.';
}

// Lấy danh sách
if ($isManager) {
    $list_sql = "SELECT rr.*, a.full_name, p.position_name, ab.full_name AS approved_by_name
                 FROM resignation_requests rr
                 JOIN accounts a ON a.account_id = rr.account_id
                 LEFT JOIN positions p ON p.position_id = a.position_id
                 LEFT JOIN accounts ab ON ab.account_id = rr.approved_by
                 ORDER BY rr.status='chờ duyệt' DESC, rr.created_at DESC";
} else {
    $list_sql = "SELECT rr.*, a.full_name, p.position_name, ab.full_name AS approved_by_name
                 FROM resignation_requests rr
                 JOIN accounts a ON a.account_id = rr.account_id
                 LEFT JOIN positions p ON p.position_id = a.position_id
                 LEFT JOIN accounts ab ON ab.account_id = rr.approved_by
                 WHERE rr.account_id = $uid
                 ORDER BY rr.created_at DESC";
}
$list_result = mysqli_query($conn, $list_sql);
$resignations = $list_result ? mysqli_fetch_all($list_result, MYSQLI_ASSOC) : [];
$status_class = ['chờ duyệt'=>'warning','chấp thuận'=>'success','từ chối'=>'danger','hủy'=>'secondary'];
?>

<div class="dash_board px-2">
    <h1 class="head-name">ĐƠN NGHỈ VIỆC</h1>
    <div class="head-line"></div>

    <?php if ($msg_success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg_success) ?></div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($msg_error) ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="fa-solid fa-circle-info me-2"></i>
        <b>Lưu ý:</b> Đơn nghỉ việc sau khi được phê duyệt sẽ <b>khóa tài khoản</b> và chấm dứt quyền truy cập hệ thống ngay lập tức.
    </div>

    <div class="container-fluid">
        <div class="row g-3 mt-1">
            <!-- Form gửi đơn -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-danger">
                    <div class="card-header fw-bold bg-danger text-white">
                        <i class="fa-solid fa-door-open me-2"></i>Gửi Đơn Nghỉ Việc
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" onsubmit="return confirm('Bạn chắc chắn muốn gửi đơn nghỉ việc?')">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ngày thông báo</label>
                                <input type="text" class="form-control" value="<?= date('d/m/Y') ?> (hôm nay)" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ngày nghỉ hiệu lực</label>
                                <input type="date" class="form-control" name="effective_date"
                                       min="<?= date('Y-m-d') ?>"
                                       value="<?= date('Y-m-d') ?>" required>
                                <small class="text-muted">Chọn ngày bắt đầu nghỉ việc</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Lý do nghỉ việc</label>
                                <textarea class="form-control" name="reason" rows="4"
                                          placeholder="Nêu rõ lý do..." required></textarea>
                            </div>
                            <button type="submit" name="submit_resignation" class="btn btn-danger w-100 fw-bold">
                                <i class="fa-solid fa-paper-plane me-1"></i>Gửi đơn
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danh sách đơn -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold">
                        <i class="fa-solid fa-list me-2"></i><?= $isManager ? 'Tất cả đơn nghỉ việc' : 'Đơn của tôi' ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($resignations)): ?>
                            <p class="p-3 mb-0 text-muted">Không có đơn nào.</p>
                        <?php else: ?>
                        <table class="table table-striped table-hover table-bordered mb-0 text-center table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <?php if ($isManager): ?><th>Nhân viên</th><?php endif; ?>
                                    <th>Ngày thông báo</th>
                                    <th>Ngày hiệu lực</th>
                                    <th>Lý do</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resignations as $rr):
                                    $sc = $status_class[$rr['status']] ?? 'secondary';
                                ?>
                                <tr>
                                    <?php if ($isManager): ?>
                                    <td class="text-start">
                                        <b><?= htmlspecialchars($rr['full_name']) ?></b><br>
                                        <small class="text-muted"><?= htmlspecialchars($rr['position_name'] ?? '') ?></small>
                                    </td>
                                    <?php endif; ?>
                                    <td><?= date('d/m/Y', strtotime($rr['notice_date'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($rr['effective_date'])) ?></td>
                                    <td class="text-start" style="max-width:200px">
                                        <span title="<?= htmlspecialchars($rr['reason']) ?>" style="cursor:help">
                                            <?= htmlspecialchars(mb_substr($rr['reason'], 0, 50)) ?>...
                                        </span>
                                    </td>
                                    <td><span class="badge text-bg-<?= $sc ?>"><?= htmlspecialchars($rr['status']) ?></span></td>
                                    <td>
                                        <?php if ($rr['status'] === 'chờ duyệt' && !$isManager && $rr['account_id'] == $uid): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hủy đơn nghỉ việc?')">
                                            <input type="hidden" name="resignation_id" value="<?= $rr['resignation_request_id'] ?>">
                                            <button type="submit" name="cancel_resignation" class="btn btn-sm btn-outline-warning">Hủy đơn</button>
                                        </form>
                                        <?php endif; ?>

                                        <?php if ($isManager && $rr['status'] === 'chờ duyệt'): ?>
                                        <button class="btn btn-sm btn-success" onclick="showResignModal(<?= $rr['resignation_request_id'] ?>, 'approve')">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="showResignModal(<?= $rr['resignation_request_id'] ?>, 'reject')">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                        <?php endif; ?>

                                        <?php if ($rr['status'] !== 'chờ duyệt' && $rr['approved_by_name']): ?>
                                        <small class="text-muted d-block">Bởi: <?= htmlspecialchars($rr['approved_by_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isManager): ?>
<div class="modal fade" id="resignModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resignModalTitle">Duyệt đơn nghỉ việc</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="resignation_id" id="modalResignId">
                    <input type="hidden" name="approve_action" id="modalResignAction">
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Ghi chú..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="approve_resignation" class="btn" id="resignBtn">Xác nhận</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
function showResignModal(id, action) {
    document.getElementById('modalResignId').value = id;
    document.getElementById('modalResignAction').value = action;
    const isApprove = action === 'approve';
    document.getElementById('resignModalTitle').textContent = isApprove ? 'Chấp thuận nghỉ việc' : 'Từ chối đơn nghỉ việc';
    document.getElementById('resignBtn').className = 'btn ' + (isApprove ? 'btn-success' : 'btn-danger');
    document.getElementById('resignBtn').textContent = isApprove ? 'Chấp thuận' : 'Từ chối';
    new bootstrap.Modal(document.getElementById('resignModal')).show();
}
</script>
<?php endif; ?>
</div>
</div>
</div>
</div>
